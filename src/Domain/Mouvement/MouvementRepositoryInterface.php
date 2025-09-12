<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\Categorie\CategorieIdCollection;
use App\Domain\Compte\CompteId;
use App\Domain\DataStructure\Maybe;

interface MouvementRepositoryInterface
{
    public function find(MouvementId $mouvementId): ?Mouvement;

    public function findAll(): MouvementCollection;

    /**
     * @param Maybe<CategorieIdCollection|null> $maybeCategoriesIds
     * @param Maybe<CompteId>                   $maybeCompteId
     * @param Maybe<\DateTimeImmutable>         $maybeDateStart     Date de début, incluse
     * @param Maybe<\DateTimeImmutable>         $maybeDateEnd       Date de fin, incluse
     * @param Maybe<Montant>                    $maybeMontant
     */
    public function findBy(
        Maybe $maybeCategoriesIds,
        Maybe $maybeCompteId,
        Maybe $maybeDateStart,
        Maybe $maybeDateEnd,
        Maybe $maybeMontant,
    ): MouvementCollection;

    /**
     * Récupère le mouvement le plus ancien.
     */
    public function findFirstOne(?CompteId $compteId = null): ?Mouvement;

    /**
     * Récupère le mouvement le plus récent.
     */
    public function findLatestOne(): ?Mouvement;

    public function save(Mouvement ...$mouvements): void;

    public function delete(MouvementId ...$ids): void;
}
