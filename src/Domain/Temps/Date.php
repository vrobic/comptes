<?php

declare(strict_types=1);

namespace App\Domain\Temps;

final class Date extends \DateTimeImmutable
{
    /**
     * Une date se crée par créer() : le constructeur hérité ferait du month shifting.
     */
    private function __construct()
    {
    }

    /**
     * Crée une date stricte, sans month shifting : createFromFormat décale les dates impossibles (31-02 devient 03-03)
     * et lit 26 comme l'an 26, la date n'est donc valide que si, relue au même format, elle redonne la chaîne reçue.
     *
     * @throws DateInvalideException
     */
    public static function créer(string $format, string $date): self
    {
        $dateTime = self::createFromFormat($format, $date);

        if (!$dateTime instanceof self || $dateTime->format($format) !== $date) {
            throw new DateInvalideException(sprintf('Date [%s] invalide au format [%s].', $date, $format));
        }

        return $dateTime;
    }
}
