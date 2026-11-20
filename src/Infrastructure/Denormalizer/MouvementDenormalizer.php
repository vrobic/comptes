<?php

declare(strict_types=1);

namespace App\Infrastructure\Denormalizer;

use App\Domain\Mouvement\Montant;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementId;
use App\Domain\Temps\Date;

final readonly class MouvementDenormalizer implements Denormalizer
{
    public function __construct(
        private CategorieDenormalizer $categorieDenormalizer,
        private CompteDenormalizer $compteDenormalizer,
    ) {
    }

    public function denormalize(array $data): Mouvement
    {
        $date = Date::créer('Y-m-d H:i:s', "{$data['date']} 00:00:00");

        return new Mouvement(
            new MouvementId((string) $data['id']),
            $date,
            is_array($data['categorie']) ? $this->categorieDenormalizer->denormalize($data['categorie']) : null,
            $this->compteDenormalizer->denormalize($data['compte']),
            new Montant((float) $data['montant']),
            (string) $data['description'],
        );
    }
}
