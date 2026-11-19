<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteCollection;
use App\Domain\Compte\CompteId;
use App\Domain\Compte\CompteRepositoryInterface;
use App\Domain\Compte\Solde;
use App\Domain\Temps\Date;
use App\Infrastructure\Denormalizer\CompteDenormalizer;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;

final readonly class CompteRepository implements CompteRepositoryInterface
{
    use UpsertTrait;

    public function __construct(
        private Connection $connection,
        private CompteDenormalizer $compteDenormalizer,
    ) {
    }

    public function findAll(): CompteCollection
    {
        $rows = $this->getBaseQueryBuilder()
            ->executeQuery()
            ->fetchAllAssociative();

        return new CompteCollection()->add(
            ...array_map(
                fn (array $row): Compte => $this->compteDenormalizer->denormalize($row),
                $rows
            )
        );
    }

    public function find(CompteId $compteId): ?Compte
    {
        $row = $this->getBaseQueryBuilder()
            ->where('compte.id = :compte_id')
            ->setParameter('compte_id', (string) $compteId)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return $this->compteDenormalizer->denormalize($row);
    }

    public function getSoldeÀDate(
        CompteId $compteId,
        Date $date,
    ): Solde {
        return new Solde(
            (float) $this->connection->createQueryBuilder()
                ->select('compte.solde_initial + COALESCE(SUM(mouvement.montant), 0) AS solde')
                ->from('comptes', 'compte')
                ->leftJoin('compte', 'mouvements', 'mouvement', 'mouvement.compte_id = compte.id AND mouvement.date < :date')
                ->where('compte.id = :compte_id')
                ->groupBy('compte.id')
                ->setParameter('compte_id', (string) $compteId)
                ->setParameter('date', $date, Types::DATETIME_IMMUTABLE)
                ->executeQuery()
                ->fetchOne()
        );
    }

    public function save(Compte ...$comptes): void
    {
        foreach ($comptes as $compte) {
            $data = [
                'nom' => $compte->nom,
                'numero' => $compte->numero,
                'banque' => $compte->banque,
                'plafond' => $compte->plafond?->montant,
                'solde_initial' => $compte->soldeInitial->montant,
                'rang' => $compte->rang,
                'date_ouverture' => $compte->dateOuverture,
                'date_fermeture' => $compte->dateFermeture,
            ];

            $this->upsert(
                $this->connection,
                'comptes',
                array_merge(
                    ['id' => (string) $compte->id],
                    $data,
                ),
                $data,
                [
                    'date_ouverture' => Types::DATE_IMMUTABLE,
                    'date_fermeture' => Types::DATE_IMMUTABLE,
                ]
            );
        }
    }

    public function delete(CompteId ...$ids): void
    {
        $this->connection->executeStatement(
            'DELETE FROM comptes WHERE id IN (:ids);',
            [
                'ids' => array_map(
                    static fn (CompteId $id): string => (string) $id,
                    $ids
                ),
            ],
            ['ids' => ArrayParameterType::STRING]
        );
    }

    private function getBaseQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select(
                'compte.*',
                'compte.solde_initial + COALESCE(SUM(mouvement.montant), 0) AS solde',
            )
            ->from('comptes', 'compte')
            ->leftJoin('compte', 'mouvements', 'mouvement', 'mouvement.compte_id = compte.id')
            ->addGroupBy('compte.id')
            ->addOrderBy('compte.rang', 'ASC')
            ->addOrderBy('compte.date_fermeture', 'DESC')
            ->addOrderBy('compte.id', 'ASC');
    }
}
