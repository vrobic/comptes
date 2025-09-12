<?php

declare(strict_types=1);

namespace App\Infrastructure\Denormalizer;

use App\Domain\Mouvement\Montant;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementId;

final readonly class MouvementDenormalizer implements Denormalizer
{
    public function __construct(
        private CategorieDenormalizer $categorieDenormalizer,
        private CompteDenormalizer $compteDenormalizer,
    ) {
    }

    public function denormalize(array $data): Mouvement
    {
        $date = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', "{$data['date']} 00:00:00");
        if (!($date instanceof \DateTimeImmutable)) {
            throw new \Exception("Date du mouvement invalide : {$data['date']}");
        }

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
