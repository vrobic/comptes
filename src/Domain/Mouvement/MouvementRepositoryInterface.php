<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\Categorie\CategorieIdCollection;
use App\Domain\Compte\CompteId;
use App\Domain\DataStructure\Maybe;
use App\Domain\Temps\Periode;

interface MouvementRepositoryInterface
{
    public function find(MouvementId $mouvementId): ?Mouvement;

    public function findAll(): MouvementCollection;

    /**
     * @param Maybe<CategorieIdCollection|null> $categoriesIds
     * @param Maybe<CompteId>                   $compteId
     * @param Maybe<\DateTimeImmutable>         $dateStart     Date de début, incluse
     * @param Maybe<\DateTimeImmutable>         $dateEnd       Date de fin, incluse
     * @param Maybe<float>                      $montant
     */
    public function findBy(
        Maybe $categoriesIds,
        Maybe $compteId,
        Maybe $dateStart,
        Maybe $dateEnd,
        Maybe $montant,
    ): MouvementCollection;

    /**
     * Récupère le mouvement le plus ancien.
     */
    public function findFirstOne(?CompteId $compteId = null): ?Mouvement;

    /**
     * Récupère le mouvement le plus récent.
     */
    public function findLatestOne(): ?Mouvement;

    public function balancePériodique(
        Periode $période,
        ?CompteId $compteId = null,
    ): float;

    public function save(Mouvement ...$mouvements): void;

    public function delete(MouvementId ...$ids): void;
}
