<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteCollection;
use App\Infrastructure\Denormalizer\CompteDenormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

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
            ->addGroupBy('compte.id')
            ->addOrderBy('compte.rang', 'ASC')
            ->addOrderBy('compte.date_fermeture', 'DESC')
            ->executeQuery()
            ->fetchAllAssociative();

        return CompteCollection::from(
            ...array_map(
                fn (array $row): Compte => $this->compteDenormalizer->denormalize($row),
                $rows
            )
        );
    }

    public function find(int $compteId): Compte
    {
        $row = $this->getBaseQueryBuilder()
            ->where('compte.id = :compte_id')
            ->setParameter('compte_id', $compteId)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw new \Exception(); // @todo
        }

        return $this->compteDenormalizer->denormalize($row);
    }

    /**
     * Le solde à date correspond au solde juste avant la date,
     * pour ne pas comptabiliser les mouvements ayant eu lieu à cette date.
     * C'est cette règle qui est appliquée sur les relevés bancaires.
     */
    public function getSoldeÀDate(int $compteId, \DateTime $date): float
    {
        return (float) $this->getBaseQueryBuilder()
            ->select('compte.solde_initial + SUM(mouvement.montant) AS solde')
            ->andWhere('compte.id = :compte_id')
            ->andWhere('mouvement.date < :date')
            ->setParameter('compte_id', $compteId)
            ->setParameter('date', $date->format('Y-m-d'))
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
            ->leftJoin('compte', 'mouvements', 'mouvement', 'mouvement.compte_id = compte.id');
    }
}
