<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Texte;

use App\Infrastructure\Texte\DétecterUnMot;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DétecterUnMotTest extends TestCase
{
    #[DataProvider('textes')]
    public function test_invoke(string $mot, string $texte, bool $contient): void
    {
        self::assertSame($contient, new DétecterUnMot()($texte, $mot));
    }

    /** @return array{string, string, bool}[] */
    public static function textes(): array
    {
        return [
            'mot présent' => ['DECATHLON', 'PAIEMENT CB 1209 DECATHLON NANTES', true],
            'casse différente' => ['decathlon', 'PAIEMENT CB 1209 DECATHLON NANTES', true],
            'mot au début' => ['DECATHLON', 'DECATHLON NANTES', true],
            'mot à la fin' => ['DECATHLON', 'PAIEMENT CB 1209 DECATHLON', true],
            'délimité par une ponctuation' => ['SNCF', 'PAIEMENT CB 1209 SNCF-VOYAGES', true],
            'plusieurs mots' => ['ORANGE SA', 'PRLV SEPA ORANGE SA 123456', true],
            'mot absent' => ['DECATHLON', 'PAIEMENT CB 1209 INTERSPORT', false],
            "partie d'un mot plus long" => ['EDF', 'PAIEMENT CB 1209 MEDFORD', false],
            'mots séparés autrement' => ['ORANGE SA', 'PRLV SEPA ORANGE-SA', false],
            'texte vide' => ['DECATHLON', '', false],
            'point' => ['S.A', 'PRLV SEPA ORANGE S.A 123456', true],
            "point qui n'est pas un joker" => ['S.A', 'PRLV SEPA ORANGE SXA 123456', false],
            'parenthèse' => ['C(A', 'PAIEMENT CB 1209 C(A NANTES', true],
            'barre oblique' => ['A/B', 'PAIEMENT CB 1209 A/B NANTES', true],
            'barre oblique inversée' => ['A\\B', 'PAIEMENT CB 1209 A\\B NANTES', true],
        ];
    }
}
