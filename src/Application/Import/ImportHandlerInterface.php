<?php

declare(strict_types=1);

namespace App\Application\Import;

interface ImportHandlerInterface
{
    /**
     * @todo documenter
     */
    public function supports(string $handlerId): bool;

    /**
     * @todo documenter
     */
    public function parse(\SplFileObject $file): void;
}
