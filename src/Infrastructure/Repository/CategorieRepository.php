<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieParId;
use App\Domain\Compte\Compte;
use App\Infrastructure\Denormalizer\CategorieDenormalizer;
use Doctrine\DBAL\ArrayParameterType;
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

    public function find(int $categorieId): ?Categorie
    {
        $row = $this->getBaseQueryBuilder()
            ->where('categorie.id = :categorie_id')
            ->setParameter('categorie_id', $categorieId)
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
     * @param int       $categorieId L'identifiant de la catégorie
     * @param \DateTime $dateStart   date de début, incluse
     * @param \DateTime $dateEnd     date de fin, incluse
     * @param ?int      $compteId    L'identifiant d'un compte, facultatif
     */
    public function getMontantTotalByDate(
        int $categorieId,
        \DateTime $dateStart,
        \DateTime $dateEnd,
        ?int $compteId = null,
    ): float {
        $categoriesIds = array_merge(
            [$categorieId],
            $this->getCategoriesFillesRecursive($categorieId),
        );

        $wheres[] = 'categorie_id IN (:categories_ids)';
        $wheres[] = 'date >= :date_start AND date <= :date_end';
        $params = [
            'categories_ids' => $categoriesIds,
            'date_start' => $dateStart->format('Y-m-d'),
            'date_end' => $dateEnd->format('Y-m-d'),
        ];

        if (is_int($compteId)) {
            $wheres[] = 'compte_id = :compte_id';
            $params['compte_id'] = $compteId;
        }

        $sql = 'SELECT SUM(montant) FROM mouvements WHERE '.implode(' AND ', $wheres).';';

        return (float) $this->connection->fetchOne(
            $sql,
            $params,
            [
                'categories_ids' => ArrayParameterType::INTEGER,
            ]
        );
    }

    /**
     * @return int[]
     */
    public function getCategoriesFillesRecursive(int $categorieId): array
    {
        $filles = $this->connection->fetchFirstColumn(
            'SELECT id FROM categories WHERE categorie_parente_id = :categorie_id;',
            ['categorie_id' => $categorieId]
        );

        $ids = [];

        /** @var int $fille */
        foreach ($filles as $fille) {
            $ids[] = $fille;
            $ids = array_merge($ids, $this->getCategoriesFillesRecursive($fille));
        }

        return array_unique($ids);
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
