<?php

declare(strict_types=1);

namespace App\Domain\Compte;

interface CompteRepositoryInterface
{
    public function findAll(): CompteCollection;

    public function find(CompteId $compteId): ?Compte;

    /**
     * Le solde à date correspond au solde juste avant la date,
     * pour ne pas comptabiliser les mouvements ayant eu lieu à cette date.
     * C'est cette règle qui est appliquée sur les relevés bancaires.
     */
    public function getSoldeÀDate(
        CompteId $compteId,
        \DateTimeImmutable $date,
    ): Solde;
}
