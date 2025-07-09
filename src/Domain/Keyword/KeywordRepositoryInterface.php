<?php

declare(strict_types=1);

namespace App\Domain\Keyword;

interface KeywordRepositoryInterface
{
    public function find(string $word): ?Keyword;

    public function findAll(): KeywordCollection;

    public function save(Keyword ...$keywords): void;

    public function delete(KeywordId ...$ids): void;
}
