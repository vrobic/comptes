<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieParId;
use App\Infrastructure\Denormalizer\CategorieDenormalizer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

final readonly class CategorieRepository
{
    public function __construct(
        private Connection $connection,
        private CategorieDenormalizer $categorieDenormalizer,
    ) {
    }

    public function findAll(): CategorieParId
    {
        $rows = $this->getBaseQueryBuilder()
            ->orderBy('categorie.rang', 'ASC')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_reduce(
            $rows,
            fn (CategorieParId $map, array $row): CategorieParId => $map->add(
                (int) $row['id'],
                $this->categorieDenormalizer->denormalize($row)
            ),
            new CategorieParId()
        );
    }

    public function find(int $categorieId): Categorie
    {
        $row = $this->getBaseQueryBuilder()
            ->where('categorie.id = :categorie_id')
            ->setParameter('categorie_id', $categorieId)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            throw new \Exception(); // @todo
        }

        return $this->categorieDenormalizer->denormalize($row);
    }

    private function getBaseQueryBuilder(): QueryBuilder
    {
        return $this->connection->createQueryBuilder()
            ->select(
                'categorie.*',
                'GROUP_CONCAT(DISTINCT categorie_fille.id) AS categories_filles',
            )
            ->from('categories', 'categorie')
            ->leftJoin('categorie', 'categories', 'categorie_fille', 'categorie_fille.categorie_parente_id = categorie.id')
            ->groupBy('categorie.id');
    }
}
