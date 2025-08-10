<?php

declare(strict_types=1);

namespace App\Domain\Temps;

final readonly class Periode
{
    public function __construct(
        public \DateTimeImmutable $début,
        public \DateTimeImmutable $fin,
    ) {
        if ($début > $fin) {
            throw new \LogicException();
        }
    }

    /** @return int[] */
    public function années(): array
    {
        $années = [];

        $i = $this->début->modify('first day of this year');

        while ($i <= $this->fin) {
            $années[] = (int) $i->format('Y');

            $i = $i->modify('+1 year');
        }

        return $années;
    }

    /** @return array<int, int[]> */
    public function mois(): array
    {
        $moisParAnnée = [];

        $i = $this->début->modify('first day of this month');

        while ($i <= $this->fin) {
            $année = (int) $i->format('Y');
            $mois = (int) $i->format('m');

            $moisParAnnée[$année][] = $mois;

            $i = $i->modify('+1 month');
        }

        return $moisParAnnée;
    }
}
