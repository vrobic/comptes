<?php

declare(strict_types=1);

namespace App\Infrastructure\Denormalizer;

use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteId;

final readonly class CompteDenormalizer implements Denormalizer
{
    public function denormalize(array $data): Compte
    {
        $dateOuverture = \DateTime::createFromFormat('Y-m-d H:i:s', "{$data['date_ouverture']} 00:00:00");
        if (!($dateOuverture instanceof \DateTime)) {
            throw new \Exception("Date d'ouverture du compte invalide : {$data['date_ouverture']}");
        }

        $dateFermeture = null;
        if (is_string($data['date_fermeture'])) {
            $dateFermeture = \DateTime::createFromFormat('Y-m-d H:i:s', "{$data['date_fermeture']} 00:00:00");
            if (!($dateFermeture instanceof \DateTime)) {
                throw new \Exception("Date de fermeture du compte invalide : {$data['date_fermeture']}");
            }
        }

        return new Compte(
            new CompteId((string) $data['id']),
            (string) $data['nom'],
            (string) $data['numero'],
            (string) $data['banque'],
            (int) $data['plafond'],
            (float) $data['solde_initial'],
            (float) $data['solde'],
            $dateOuverture,
            $dateFermeture,
            is_int($data['rang']) ? $data['rang'] : null,
        );
    }
}
