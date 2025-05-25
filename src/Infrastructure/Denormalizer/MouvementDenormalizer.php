<?php

declare(strict_types=1);

namespace App\Infrastructure\Denormalizer;

use App\Domain\Mouvement\Mouvement;

final readonly class MouvementDenormalizer implements Denormalizer
{
    public function __construct(
        private CategorieDenormalizer $categorieDenormalizer,
        private CompteDenormalizer $compteDenormalizer,
    ) {
    }

    public function denormalize(array $data): Mouvement
    {
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', "{$data['date']} 00:00:00");
        if (!($date instanceof \DateTime)) {
            throw new \Exception("Date du mouvement invalide : {$data['date']}");
        }

        return new Mouvement(
            (int) $data['id'],
            $date,
            is_array($data['categorie']) ? $this->categorieDenormalizer->denormalize($data['categorie']) : null,
            $this->compteDenormalizer->denormalize($data['compte']),
            (float) $data['montant'],
            (string) $data['description'],
        );
    }
}
