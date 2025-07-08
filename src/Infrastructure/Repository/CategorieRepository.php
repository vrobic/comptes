<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieId;
use App\Domain\Categorie\CategorieIdCollection;
use App\Domain\Categorie\CategorieParCategorieIdMap;
use App\Domain\Compte\CompteId;
use App\Infrastructure\Denormalizer\CategorieDenormalizer;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

final readonly class CategorieRepository
{
    use UpsertTrait;

    public function __construct(
        private Connection $connection,
        private CategorieDenormalizer $categorieDenormalizer,
    ) {
    }

    // @todo : ajouter du cache ?
    public function findAll(): CategorieParCategorieIdMap
    {
        $rows = $this->getBaseQueryBuilder()
            ->executeQuery()
            ->fetchAllAssociative();

        return array_reduce(
            $rows,
            fn (CategorieParCategorieIdMap $map, array $row): CategorieParCategorieIdMap => $map->add(
                (string) $row['id'],
                $this->categorieDenormalizer->denormalize($row)
            ),
            new CategorieParCategorieIdMap()
        );
    }

    // @todo : ajouter du cache ?
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

    /**
     * Calcule le montant cumulé des mouvements d'une catégorie, entre deux dates.
     *
     * @param \DateTimeImmutable $dateStart Date de début, incluse
     * @param \DateTimeImmutable $dateEnd   Date de fin, incluse
     */
    public function getMontantTotalByDate(
        CategorieId $categorieId,
        \DateTimeImmutable $dateStart,
        \DateTimeImmutable $dateEnd,
        ?CompteId $compteId = null,
    ): float {
        $categoriesIds = $this->getCategoriesFillesRecursive($categorieId)->add($categorieId);

        $wheres[] = 'categorie_id IN (:categories_ids)';
        $wheres[] = 'date >= :date_start AND date <= :date_end';
        $params = [
            'categories_ids' => $categoriesIds->toArray(
                static fn (CategorieId $id): string => (string) $id
            ),
            'date_start' => $dateStart->format('Y-m-d'),
            'date_end' => $dateEnd->format('Y-m-d'),
        ];

        if ($compteId instanceof CompteId) {
            $wheres[] = 'compte_id = :compte_id';
            $params['compte_id'] = (string) $compteId;
        }

        $sql = 'SELECT SUM(montant) FROM mouvements WHERE '.implode(' AND ', $wheres).';';

        return (float) $this->connection->fetchOne(
            $sql,
            $params,
            [
                'categories_ids' => ArrayParameterType::STRING,
            ]
        );
    }

    // @todo : ajouter du cache ?
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
