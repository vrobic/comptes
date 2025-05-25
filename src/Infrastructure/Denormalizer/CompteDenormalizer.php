<?php

declare(strict_types=1);

namespace App\Infrastructure\Denormalizer;

use App\Domain\Compte\Compte;

final readonly class CompteDenormalizer implements Denormalizer
{
    public function denormalize(array $data): Compte
    {
        return new Compte(
            (int) $data['id'],
            (string) $data['nom'],
            (string) $data['numero'],
            (string) $data['banque'],
            (int) $data['plafond'],
            (float) $data['solde_initial'],
            (float) $data['solde'],
            \DateTime::createFromFormat('Y-m-d H:i:s', "{$data['date_ouverture']} 00:00:00"),
            is_string($data['date_fermeture']) ? \DateTime::createFromFormat('Y-m-d H:i:s', "{$data['date_fermeture']} 00:00:00") : null,
            is_int($data['rang']) ? $data['rang'] : null,
        );
    }
}
