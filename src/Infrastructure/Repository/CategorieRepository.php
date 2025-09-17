<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieCollection;
use App\Domain\Categorie\CategorieId;
use App\Domain\Categorie\CategorieIdCollection;
use App\Domain\Categorie\CategorieRepositoryInterface;
use App\Infrastructure\Denormalizer\CategorieDenormalizer;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

final readonly class CategorieRepository implements CategorieRepositoryInterface
{
    use UpsertTrait;

    public function __construct(
        private Connection $connection,
        private CategorieDenormalizer $categorieDenormalizer,
    ) {
    }

    public function findAll(): CategorieCollection
    {
        $rows = $this->getBaseQueryBuilder()
            ->executeQuery()
            ->fetchAllAssociative();

        return array_reduce(
            $rows,
            fn (CategorieCollection $catégories, array $row): CategorieCollection => $catégories->add($this->categorieDenormalizer->denormalize($row)),
            new CategorieCollection()
        );
    }

    public function find(CategorieId $categorieId): ?Categorie
    {
        $row = $this->getBaseQueryBuilder()
            ->where('categorie.id = :categorie_id')
            ->setParameter('categorie_id', (string) $categorieId)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return $this->categorieDenormalizer->denormalize($row);
    }

    public function getCategoriesFillesRecursive(CategorieId $categorieId): CategorieIdCollection
    {
        $results = $this->connection->fetchFirstColumn(
            'SELECT id FROM categories WHERE categorie_parente_id = :categorie_id;',
            ['categorie_id' => (string) $categorieId]
        );

        return array_reduce(
            $results,
            function (CategorieIdCollection $ids, string $result): CategorieIdCollection {
                $id = new CategorieId($result);

                return $ids->add($id, ...$this->getCategoriesFillesRecursive($id));
            },
            new CategorieIdCollection()
        );
    }

    public function save(Categorie ...$categories): void
    {
        foreach ($categories as $categorie) {
            $data = [
                'categorie_parente_id' => $categorie->categorieParente?->__toString(),
                'nom' => $categorie->nom,
                'rang' => $categorie->rang,
            ];

            $this->upsert(
                $this->connection,
                'categories',
                array_merge(
                    ['id' => (string) $categorie->id],
                    $data,
                ),
                $data,
            );
        }
    }

    public function delete(CategorieId ...$ids): void
    {
        $this->connection->executeStatement(
            'DELETE FROM categories WHERE id IN (:ids);',
            [
                'ids' => array_map(
                    static fn (CategorieId $id): string => (string) $id,
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
                'categorie.*',
                'GROUP_CONCAT(DISTINCT categorie_fille.id) AS categories_filles',
            )
            ->from('categories', 'categorie')
            ->leftJoin('categorie', 'categories', 'categorie_fille', 'categorie_fille.categorie_parente_id = categorie.id')
            ->groupBy('categorie.id')
            ->orderBy('categorie.rang', 'ASC');
    }
}
