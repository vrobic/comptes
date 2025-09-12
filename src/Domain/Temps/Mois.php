<?php

declare(strict_types=1);

namespace App\Domain\Temps;

final readonly class Mois
{
    public function __construct(
        public int $année,
        public int $mois,
    ) {
        if ($mois < 1 || $mois > 12) {
            throw new \LogicException();
        }
    }

    public static function fromDate(\DateTimeImmutable $date): self
    {
        return new self(
            (int) $date->format('Y'),
            (int) $date->format('m')
        );
    }

    public function __toString(): string
    {
        return sprintf(
            '%s %s',
            $this->nom(),
            $this->année,
        );
    }

    public function début(): \DateTimeImmutable
    {
        return new \DateTimeImmutable()
            ->setDate($this->année, $this->mois, 1)
            ->setTime(0, 0, 0, 0);
    }

    public function fin(): \DateTimeImmutable
    {
        return $this->début()
            ->modify('last day of this month')
            ->setTime(23, 59, 59, 999999);
    }

    private function nom(): string
    {
        return match ($this->mois) {
            1 => 'janvier',
            2 => 'février',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
            6 => 'juin',
            7 => 'juillet',
            8 => 'août',
            9 => 'septembre',
            10 => 'octobre',
            11 => 'novembre',
            12 => 'décembre',
            default => throw new \RuntimeException(),
        };
    }
}
