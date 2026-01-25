<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Categorie\CategorieId;
use App\Domain\Categorie\CategorieIdCollection;
use App\Domain\Compte\CompteId;
use App\Domain\DataStructure\Maybe;
use App\Domain\Mouvement\Montant;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementCollection;
use App\Domain\Mouvement\MouvementId;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Infrastructure\Denormalizer\MouvementDenormalizer;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;

/**
 * On ne peut pas utiliser de query builder pour récupérer des mouvements,
 * à cause du LEFT JOIN qui calcule le solde des comptes.
 */
final readonly class MouvementRepository implements MouvementRepositoryInterface
{
    use UpsertTrait;

    public function __construct(
        private Connection $connection,
        private MouvementDenormalizer $mouvementDenormalizer,
    ) {
    }

    public function find(MouvementId $mouvementId): ?Mouvement
    {
        $row = $this->connection->fetchAssociative(
            <<<SQL
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
                    WHERE
                        mouvement.id = :mouvement_id;
                SQL,
            [
                'mouvement_id' => (string) $mouvementId,
            ]
        );

        if (false === $row) {
            return null;
        }

        return $this->mouvementDenormalizer->denormalize(
            $this->préparerRowPourDenormalizer($row)
        );
    }

    public function findAll(): MouvementCollection
    {
        return $this->findBy(
            maybeCategoriesIds: Maybe::nothing(),
            maybeCompteId: Maybe::nothing(),
            maybeDateStart: Maybe::nothing(),
            maybeDateEnd: Maybe::nothing(),
            maybeMontant: Maybe::nothing(),
        );
    }

    public function findBy(
        Maybe $maybeCategoriesIds,
        Maybe $maybeCompteId,
        Maybe $maybeDateStart,
        Maybe $maybeDateEnd,
        Maybe $maybeMontant,
    ): MouvementCollection {
        $wheres = [];
        $params = [];
        $types = [];

        if ($maybeCompteId->estDéfini) {
            $wheres[] = 'mouvement.compte_id = :compte_id';
            /** @var CompteId $compteId */
            $compteId = $maybeCompteId->getValeur();
            $params['compte_id'] = (string) $compteId;
        }
        if ($maybeCategoriesIds->estDéfini) {
            /** @var ?CategorieIdCollection $categoriesIds */
            $categoriesIds = $maybeCategoriesIds->getValeur();
            if ($categoriesIds instanceof CategorieIdCollection) {
                $wheres[] = 'mouvement.categorie_id IN (:categories_ids)';
                $params['categories_ids'] = $categoriesIds->toArray(
                    static fn (CategorieId $id): string => (string) $id
                );
                $types['categories_ids'] = ArrayParameterType::STRING;
            } elseif (is_null($categoriesIds)) {
                $wheres[] = 'mouvement.categorie_id IS NULL';
            } else {
                throw new \RuntimeException();
            }
        }
        if ($maybeDateStart->estDéfini) {
            $wheres[] = 'mouvement.date >= :date_start';
            /** @var \DateTimeImmutable $dateStart */
            $dateStart = $maybeDateStart->getValeur();
            $params['date_start'] = $dateStart;
            $types['date_start'] = Types::DATETIME_IMMUTABLE;
        }
        if ($maybeDateEnd->estDéfini) {
            $wheres[] = 'mouvement.date <= :date_end';
            /** @var \DateTimeImmutable $dateEnd */
            $dateEnd = $maybeDateEnd->getValeur();
            $params['date_end'] = $dateEnd;
            $types['date_end'] = Types::DATETIME_IMMUTABLE;
        }
        if ($maybeMontant->estDéfini) {
            $wheres[] = 'mouvement.montant = :montant';
            /** @var Montant $montant */
            $montant = $maybeMontant->getValeur();
            $params['montant'] = $montant->montant;
        }

        $sql = <<<SQL
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

        SQL;

        if (count($wheres) > 0) {
            $sql .= "\tWHERE ".implode(' AND ', $wheres)."\n";
        }

        $sql .= <<<SQL
            GROUP BY mouvement.id
            ORDER BY mouvement.date ASC;
        SQL;

        $rows = $this->connection->fetchAllAssociative(
            $sql,
            $params,
            $types
        );

        return new MouvementCollection()->add(
            ...array_map(
                fn (array $row): Mouvement => $this->mouvementDenormalizer->denormalize($row),
                $this->préparerRowsPourDenormalizer($rows)
            )
        );
    }

    public function findFirstOne(?CompteId $compteId = null): ?Mouvement
    {
        $row = $compteId instanceof CompteId ?
            $this->connection->fetchAssociative(
                <<<SQL
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
                    WHERE
                        mouvement.compte_id = :compte_id
                    GROUP BY mouvement.id
                    ORDER BY mouvement.date ASC
                    LIMIT 1;
                SQL,
                [
                    'compte_id' => (string) $compteId,
                ]
            ) :
            $this->connection->fetchAssociative(
                <<<SQL
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
                    GROUP BY mouvement.id
                    ORDER BY mouvement.date ASC
                    LIMIT 1;
                SQL
            )
        ;

        if (false === $row) {
            return null;
        }

        return $this->mouvementDenormalizer->denormalize(
            $this->préparerRowPourDenormalizer($row)
        );
    }

    public function findLatestOne(): ?Mouvement
    {
        $row = $this->connection->fetchAssociative(
            <<<SQL
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
                GROUP BY mouvement.id
                ORDER BY mouvement.date DESC
                LIMIT 1;
            SQL
        );

        if (false === $row) {
            return null;
        }

        return $this->mouvementDenormalizer->denormalize(
            $this->préparerRowPourDenormalizer($row)
        );
    }

    public function save(Mouvement ...$mouvements): void
    {
        foreach ($mouvements as $mouvement) {
            $data = [
                'categorie_id' => $mouvement->categorie?->id->__toString(),
                'compte_id' => $mouvement->compte->id,
                'date' => $mouvement->date,
                'montant' => $mouvement->montant->montant,
                'description' => $mouvement->description,
            ];

            $this->upsert(
                $this->connection,
                'mouvements',
                array_merge(
                    ['id' => (string) $mouvement->id],
                    $data,
                ),
                $data,
                [
                    'date' => Types::DATETIME_IMMUTABLE,
                ]
            );
        }
    }

    public function delete(MouvementId ...$ids): void
    {
        $this->connection->executeStatement(
            'DELETE FROM mouvements WHERE id IN (:ids);',
            [
                'ids' => array_map(
                    static fn (MouvementId $id): string => (string) $id,
                    $ids
                ),
            ],
            ['ids' => ArrayParameterType::STRING]
        );
    }

    private function préparerRowsPourDenormalizer(array $rows): array
    {
        return array_map(
            fn (array $row): array => $this->préparerRowPourDenormalizer($row),
            $rows
        );
    }

    private function préparerRowPourDenormalizer(array $row): array
    {
        $row = array_reduce(
            array_keys($row),
            static function (array $carry, string|int $key) use ($row): array {
                if (is_string($key) && str_starts_with($key, 'categorie_')) {
                    $carry['categorie'][substr($key, 10)] = $row[$key];
                } elseif (is_string($key) && str_starts_with($key, 'compte_')) {
                    $carry['compte'][substr($key, 7)] = $row[$key];
                } else {
                    $carry[$key] = $row[$key];
                }

                return $carry;
            },
            []
        );

        // Nettoyage
        return array_map(
            static fn ($item) => is_array($item) && null === $item['id'] ? null : $item,
            $row,
        );
    }
}
