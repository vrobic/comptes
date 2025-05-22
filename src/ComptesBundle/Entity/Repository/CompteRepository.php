<?php

namespace ComptesBundle\Entity\Repository;

use ComptesBundle\Entity\Compte;
use ComptesBundle\Entity\Mouvement;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityRepository;

/**
 * Repository des comptes bancaires.
 */
class CompteRepository extends EntityRepository
{
    /**
     * @return Compte[]
     */
    public function findAll(): array
    {
        return $this->findBy([], [
            'rang' => 'ASC',
            'dateFermeture' => 'DESC',
        ]);
    }

    /**
     * Calcule la balance (débit/crédit) d'une liste de mouvements.
     *
     * @todo : typer $mouvements directement dans le code
     *
     * @param Mouvement[] $mouvements
     */
    public function getBalanceByMouvements(array $mouvements): float
    {
        $balance = 0;

        foreach ($mouvements as $mouvement) {
            $montant = $mouvement->getMontant();
            $balance += $montant;
        }

        return $balance;
    }
}
