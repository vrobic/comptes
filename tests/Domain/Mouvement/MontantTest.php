<?php

declare(strict_types=1);

namespace App\Tests\Domain\Mouvement;

use App\Domain\Mouvement\Montant;
use PHPUnit\Framework\TestCase;

final class MontantTest extends TestCase
{
    public function test_to_string(): void
    {
        self::assertSame(
            '3 000,51 €',
            (string) new Montant(3000.51)
        );
    }
}
