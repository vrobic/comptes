<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementCollection;
use App\Infrastructure\Denormalizer\MouvementDenormalizer;
use Doctrine\DBAL\Connection;

final readonly class MouvementRepository
{
    private const WHERE_PLACEHOLDER = '%WHERE%';

    /**
     * On ne peut pas utiliser de query builder à cause du LEFT JOIN qui calcule le solde des comptes.
     */
    private const SELECT = <<<SQL
        SELECT
            mouvement.id,
            mouvement.date,
            mouvement.montant,
            mouvement.description,
            categorie.id AS categorie_id,
            categorie.nom AS categorie_nom,
            categorie.rang AS categorie_rang,
            categorie.categorie_parente_id AS categorie_categorie_parente_id,
            GROUP_CONCAT(DISTINCT categories_filles.id) AS categorie_categories_filles,
            compte.id AS compte_id,
            compte.nom AS compte_nom,
            compte.numero AS compte_numero,
            compte.banque AS compte_banque,
            compte.plafond AS compte_plafond,
            compte.solde_initial AS compte_solde_initial,
            compte.solde_initial + compte_solde.solde AS compte_solde,
            compte.rang AS compte_rang,
            compte.date_ouverture AS compte_date_ouverture,
            compte.date_fermeture AS compte_date_fermeture
        FROM mouvements mouvement
        JOIN comptes compte ON mouvement.compte_id = compte.id
        LEFT JOIN categories categorie ON mouvement.categorie_id = categorie.id
        LEFT JOIN categories categories_filles ON categorie.id = categories_filles.categorie_parente_id
        LEFT JOIN (
            SELECT compte_id, SUM(montant) AS solde
            FROM mouvements
            GROUP BY compte_id
        ) compte_solde ON compte.id = compte_solde.compte_id
        %WHERE%
        GROUP BY mouvement.id
        ORDER BY mouvement.date ASC;
    SQL;

    public function __construct(
        private Connection $connection,
        private MouvementDenormalizer $mouvementDenormalizer,
    ) {
    }

    public function findAll(): MouvementCollection
    {
        $rows = $this->connection->fetchAllAssociative(
            str_replace(
                self::WHERE_PLACEHOLDER,
                '',
                self::SELECT
            )
        );

        return MouvementCollection::from(
            ...array_map(
                fn (array $row): Mouvement => $this->mouvementDenormalizer->denormalize($row),
                $this->préparerPourDenormalizer($rows)
            )
        );
    }

    public function findByCompteAndDate(
        int $compteId,
        \DateTime $dateStart,
        \DateTime $dateEnd,
    ): MouvementCollection {
        $rows = $this->connection->fetchAllAssociative(
            str_replace(
                self::WHERE_PLACEHOLDER,
                <<<SQL
                WHERE
                        mouvement.compte_id = :compte_id AND
                        mouvement.date BETWEEN :date_start AND :date_end
                SQL,
                self::SELECT,
            ),
            [
                'compte_id' => $compteId,
                'date_start' => $dateStart->format('Y-m-d'),
                'date_end' => $dateEnd->format('Y-m-d'),
            ]
        );

        return MouvementCollection::from(
            ...array_map(
                fn (array $row): Mouvement => $this->mouvementDenormalizer->denormalize($row),
                $this->préparerPourDenormalizer($rows)
            )
        );
    }

    private function préparerPourDenormalizer(array $rows): array
    {
        foreach ($rows as &$row) {
            $row = array_reduce(
                array_keys($row),
                static function (array $carry, $key) use ($row) {
                    if (str_starts_with($key, 'categorie_')) {
                        $carry['categorie'][substr($key, 10)] = $row[$key];
                    } elseif (str_starts_with($key, 'compte_')) {
                        $carry['compte'][substr($key, 7)] = $row[$key];
                    } else {
                        $carry[$key] = $row[$key];
                    }

                    return $carry;
                },
                []
            );

            // Nettoyage
            $row = array_map(
                static fn ($item) => is_array($item) && null === $item['id'] ? null : $item,
                $row,
            );
        }

        return $rows;
    }
}
