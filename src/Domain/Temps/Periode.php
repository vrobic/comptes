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

    public function étendre(self $période): self
    {
        $dates = [
            $this->début,
            $this->fin,
            $période->début,
            $période->fin,
        ];

        return new self(min($dates), max($dates));
    }

    public function années(): AnneeCollection
    {
        $années = new AnneeCollection();

        // Premier jour de l'année de début
        $i = $this->début->setDate(
            (int) $this->début->format('Y'),
            1,
            1
        );

        while ($i <= $this->fin) {
            $années = $années->add(Annee::fromDate($i));

            $i = $i->modify('+1 year');
        }

        return $années;
    }

    public function mois(): MoisCollection
    {
        $mois = new MoisCollection();

        // Premier jour du mois de début
        $i = $this->début->setDate(
            (int) $this->début->format('Y'),
            (int) $this->début->format('n'),
            1,
        );

        while ($i <= $this->fin) {
            $mois = $mois->add(Mois::fromDate($i));

            $i = $i->modify('+1 month');
        }

        return $mois;
    }
}
