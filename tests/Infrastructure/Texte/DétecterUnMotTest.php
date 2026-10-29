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
            'mot au début' => ['DECATHLON', 'DECATHLON NANTES', true],
            'mot à la fin' => ['DECATHLON', 'PAIEMENT CB 1209 DECATHLON', true],
            'plusieurs mots' => ['ORANGE SA', 'PRLV SEPA ORANGE SA 123456', true],
            'mot absent' => ['DECATHLON', 'PAIEMENT CB 1209 INTERSPORT', false],
            'texte vide' => ['DECATHLON', '', false],
            "texte qui n'est pas en UTF-8" => ['DECATHLON', "PAIEMENT CB 1209 CAF\xC9 DECATHLON", false],
            'casse différente' => ['decathlon', 'PAIEMENT CB 1209 DECATHLON NANTES', true],
            'accent en début de mot' => ['ÉLAN', 'PAIEMENT CB 1209 ÉLAN NANTES', true],
            'accent en fin de mot' => ['CAFÉ', 'PAIEMENT CB 1209 CAFÉ DU PORT', true],
            'casse différente sur une lettre accentuée' => ['noël', 'PAIEMENT CB 1209 NOËL NANTES', true],
            'délimité par une ponctuation' => ['SNCF', 'PAIEMENT CB 1209 SNCF-VOYAGES', true],
            "partie d'un mot plus long" => ['EDF', 'PAIEMENT CB 1209 MEDFORD', false],
            "début d'un mot accentué" => ['CAF', 'PAIEMENT CB 1209 CAFÉ DU PORT', false],
            "fin d'un mot accentué" => ['LAN', 'PAIEMENT CB 1209 ÉLAN NANTES', false],
            'accent en début de mot, dans un mot plus long' => ['ÉLAN', 'PAIEMENT CB 1209 DÉLAN NANTES', false],
            'accent en fin de mot, dans un mot plus long' => ['CAFÉ', 'PAIEMENT CB 1209 CAFÉS DU PORT', false],
            "fin d'un mot à l'accent décomposé" => ['LAN', "PAIEMENT CB 1209 E\u{0301}LAN NANTES", false],
            'accent décomposé en fin de mot, dans un mot plus long' => ["CAFE\u{0301}", "PAIEMENT CB 1209 CAFE\u{0301}S DU PORT", false],
            'symbole et marques collés au début du mot' => ['MAMAN', "VIR LYDIA #\u{FE0F}\u{20E3}MAMAN", true],
            'mots séparés autrement' => ['ORANGE SA', 'PRLV SEPA ORANGE-SA', false],
            'point' => ['S.A', 'PRLV SEPA ORANGE S.A 123456', true],
            "point qui n'est pas un joker" => ['S.A', 'PRLV SEPA ORANGE SXA 123456', false],
            'parenthèse' => ['C(A', 'PAIEMENT CB 1209 C(A NANTES', true],
            'barre oblique' => ['A/B', 'PAIEMENT CB 1209 A/B NANTES', true],
            'barre oblique inversée' => ['A\\B', 'PAIEMENT CB 1209 A\\B NANTES', true],
        ];
    }
}
