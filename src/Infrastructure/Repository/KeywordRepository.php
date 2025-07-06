<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Keyword\Keyword;
use App\Domain\Keyword\KeywordCollection;
use App\Domain\Keyword\KeywordId;
use App\Infrastructure\Denormalizer\KeywordDenormalizer;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

final readonly class KeywordRepository
{
    use UpsertTrait;

    public function __construct(
        private Connection $connection,
        private KeywordDenormalizer $keywordDenormalizer,
    ) {
    }

    public function find(string $word): ?Keyword
    {
        $row = $this->getBaseQueryBuilder()
            ->where('keyword.word = :word')
            ->setParameter('word', $word)
            ->executeQuery()
            ->fetchAssociative();

        if (false === $row) {
            return null;
        }

        return $this->keywordDenormalizer->denormalize(
            $this->préparerRowPourDenormalizer($row)
        );
    }

    public function findAll(): KeywordCollection
    {
        $rows = $this->getBaseQueryBuilder()
            ->executeQuery()
            ->fetchAllAssociative();

        return KeywordCollection::from(
            ...array_map(
                fn (array $row): Keyword => $this->keywordDenormalizer->denormalize($row),
                $this->préparerRowsPourDenormalizer($rows)
            )
        );
    }

    public function save(Keyword ...$keywords): void
    {
        foreach ($keywords as $keyword) {
            $data = [
                'categorie_id' => (string) $keyword->getCategorie()->getId(),
                'word' => $keyword->getWord(),
            ];

            $this->upsert(
                $this->connection,
                'keywords',
                array_merge(
                    ['id' => (string) $keyword->getId()],
                    $data,
                ),
                $data,
            );
        }
    }

    public function delete(KeywordId ...$ids): void
    {
        $this->connection->executeStatement(
            'DELETE FROM keywords WHERE id IN (:ids);',
            [
                'ids' => array_map(
                    static fn (KeywordId $id): string => (string) $id,
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
                'keyword.id',
                'keyword.word',
                'categorie.id AS categorie_id',
                'categorie.nom AS categorie_nom',
                'categorie.rang AS categorie_rang',
                'categorie.categorie_parente_id AS categorie_categorie_parente_id',
                'GROUP_CONCAT(DISTINCT categories_filles.id) AS categorie_categories_filles',
            )
            ->from('keywords', 'keyword')
            ->leftJoin('keyword', 'categories', 'categorie', 'categorie.id = keyword.categorie_id')
            ->leftJoin('categorie', 'categories', 'categories_filles', 'categories_filles.categorie_parente_id = categorie.id')
            ->groupBy('keyword.id')
            ->orderBy('keyword.word', 'ASC');
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
