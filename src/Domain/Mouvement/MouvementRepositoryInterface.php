<?php

declare(strict_types=1);

namespace App\Domain\Mouvement;

use App\Domain\Categorie\CategorieIdCollection;
use App\Domain\Compte\CompteId;
use App\Domain\DataStructure\Maybe;
use App\Domain\Temps\Date;
use App\Domain\Temps\Periode;

interface MouvementRepositoryInterface
{
    public function find(MouvementId $mouvementId): ?Mouvement;

    public function findAll(): MouvementCollection;

    /**
     * @param Maybe<CategorieIdCollection|null> $maybeCategoriesIds
     * @param Maybe<CompteId>                   $maybeCompteId
     * @param Maybe<Date>                       $maybeDateStart     Date de début, incluse
     * @param Maybe<Date>                       $maybeDateEnd       Date de fin, incluse
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
     * Récupère le mouvement le plus récent de chaque compte. Un compte sans mouvement en est absent.
     */
    public function trouverLesDerniersParCompte(): MouvementParCompteIdMap;

    public function aDesMouvements(CompteId $compteId): bool;

    /**
     * Période couverte par les mouvements du compte, de la date du premier à celle du dernier, ou null s'il n'en a aucun.
     */
    public function getPériodeDesMouvements(CompteId $compteId): ?Periode;

    /**
     * Parmi les mouvements donnés, renvoie ceux dont un mouvement de même compte, date et montant existe déjà.
     */
    public function trouverLesDoublons(MouvementCollection $mouvements): MouvementCollection;

    public function save(Mouvement ...$mouvements): void;

    public function delete(MouvementId ...$ids): void;
}
