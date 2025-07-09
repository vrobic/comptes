<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteCollection;
use App\Domain\Compte\CompteId;
use App\Infrastructure\Denormalizer\CompteDenormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;

final readonly class CompteRepository
{
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

        return CompteCollection::from(
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

    /**
     * Le solde à date correspond au solde juste avant la date,
     * pour ne pas comptabiliser les mouvements ayant eu lieu à cette date.
     * C'est cette règle qui est appliquée sur les relevés bancaires.
     */
    public function getSoldeÀDate(
        CompteId $compteId,
        \DateTimeImmutable $date,
    ): float {
        return (float) $this->getBaseQueryBuilder()
            ->select('compte.solde_initial + SUM(mouvement.montant) AS solde')
            ->andWhere('compte.id = :compte_id')
            ->andWhere('mouvement.date < :date')
            ->setParameter('compte_id', (string) $compteId)
            ->setParameter('date', $date, Types::DATETIME_IMMUTABLE)
            ->executeQuery()
            ->fetchOne();
    }

    private function getBaseQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select(
                'compte.*',
                'compte.solde_initial + SUM(mouvement.montant) AS solde',
            )
            ->from('comptes', 'compte')
            ->leftJoin('compte', 'mouvements', 'mouvement', 'mouvement.compte_id = compte.id')
            ->addGroupBy('compte.id')
            ->addOrderBy('compte.rang', 'ASC')
            ->addOrderBy('compte.date_fermeture', 'DESC');
    }
}
