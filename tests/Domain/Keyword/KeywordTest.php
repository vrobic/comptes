<?php

declare(strict_types=1);

namespace App\Tests\Domain\Keyword;

use App\Tests\GenererInstance;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class KeywordTest extends TestCase
{
    #[DataProvider('descriptions')]
    public function test_est_présent_dans(string $word, string $description, bool $estPrésent): void
    {
        $keyword = GenererInstance::keyword(word: $word);

        self::assertSame($estPrésent, $keyword->estPrésentDans($description));
    }

    /** @return array{string, string, bool}[] */
    public static function descriptions(): array
    {
        return [
            'mot présent' => ['DECATHLON', 'PAIEMENT CB 1209 DECATHLON NANTES', true],
            'casse différente' => ['decathlon', 'PAIEMENT CB 1209 DECATHLON NANTES', true],
            'début de description' => ['DECATHLON', 'DECATHLON NANTES', true],
            'fin de description' => ['DECATHLON', 'PAIEMENT CB 1209 DECATHLON', true],
            'délimité par une ponctuation' => ['SNCF', 'PAIEMENT CB 1209 SNCF-VOYAGES', true],
            'plusieurs mots' => ['ORANGE SA', 'PRLV SEPA ORANGE SA 123456', true],
            'mot absent' => ['DECATHLON', 'PAIEMENT CB 1209 INTERSPORT', false],
            "partie d'un mot plus long" => ['EDF', 'PAIEMENT CB 1209 MEDFORD', false],
            'mots séparés autrement' => ['ORANGE SA', 'PRLV SEPA ORANGE-SA', false],
            'description vide' => ['DECATHLON', '', false],
        ];
    }

    public function test_to_string(): void
    {
        self::assertSame('DECATHLON', (string) GenererInstance::keyword(word: 'DECATHLON'));
    }
}
