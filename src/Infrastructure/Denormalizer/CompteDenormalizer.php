<?php

declare(strict_types=1);

namespace App\Infrastructure\Denormalizer;

use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteId;
use App\Domain\Compte\Plafond;
use App\Domain\Compte\Solde;
use App\Domain\Temps\Date;

final readonly class CompteDenormalizer implements Denormalizer
{
    public function denormalize(array $data): Compte
    {
        $dateOuverture = Date::créer('Y-m-d H:i:s', "{$data['date_ouverture']} 00:00:00");

        $dateFermeture = null;
        if (is_string($data['date_fermeture'])) {
            $dateFermeture = Date::créer('Y-m-d H:i:s', "{$data['date_fermeture']} 00:00:00");
        }

        return new Compte(
            new CompteId((string) $data['id']),
            (string) $data['nom'],
            (string) $data['numero'],
            (string) $data['banque'],
            is_int($data['plafond']) ? new Plafond((float) $data['plafond']) : null,
            new Solde((float) $data['solde_initial']),
            new Solde((float) $data['solde']),
            $dateOuverture,
            $dateFermeture,
            is_int($data['rang']) ? $data['rang'] : null,
        );
    }
}
