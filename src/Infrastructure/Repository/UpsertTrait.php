<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use Doctrine\DBAL\Connection;

trait UpsertTrait
{
    /**
     * @param array<string, mixed>  $dataToInsert Parce qu'on ne souhaite pas toujours insert les mêmes champs qu'à l'update
     * @param array<string, mixed>  $dataToUpdate Parce qu'on ne souhaite pas toujours update les mêmes champs qu'à l'insert
     * @param array<string, string> $types
     */
    private function upsert(
        Connection $connection,
        string $table,
        array $dataToInsert,
        array $dataToUpdate,
        array $types = [],
    ): void {
        $keysToInsert = array_keys($dataToInsert);
        $keysToUpdate = array_keys($dataToUpdate);

        $sql = sprintf(
            <<<SQL
            INSERT INTO %s (
              %s
            ) VALUES (
              %s
            ) ON DUPLICATE KEY UPDATE %s;
            SQL,
            $connection->quoteIdentifier($table),
            implode(
                ",\n  ",
                array_map(
                    static fn (string $key): string => $connection->quoteIdentifier($key),
                    $keysToInsert
                )
            ),
            implode(
                ",\n  ",
                array_map(
                    static fn (string $key): string => ":$key",
                    $keysToInsert
                )
            ),
            implode(
                ",\n  ",
                array_map(
                    static fn (string $key): string => sprintf('%s = :%s', $connection->quoteIdentifier($key), $key),
                    $keysToUpdate,
                )
            ),
        );

        $connection->executeStatement(
            $sql,
            array_merge($dataToInsert, $dataToUpdate),
            $types
        );
    }

    /**
     * @param array<string, mixed>  $data
     * @param array<string, string> $types
     */
    private function insertIgnore(
        Connection $connection,
        string $table,
        array $data,
        array $types = [],
    ): void {
        $keys = array_keys($data);

        $sql = sprintf(
            <<<SQL
            INSERT IGNORE INTO %s (
              %s
            ) VALUES (
              %s
            );
            SQL,
            $connection->quoteIdentifier($table),
            implode(
                ",\n  ",
                array_map(
                    static fn (string $key): string => $connection->quoteIdentifier($key),
                    $keys
                )
            ),
            implode(
                ",\n  ",
                array_map(
                    static fn (string $key): string => ":$key",
                    $keys
                )
            ),
        );

        $connection->executeStatement(
            $sql,
            $data,
            $types
        );
    }
}
