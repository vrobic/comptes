<?php

namespace ComptesBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Repository des comptes bancaires.
 */
class CompteRepository extends EntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->findBy(
            array(),
            array(
                'rang' => 'ASC',
                'dateFermeture' => 'DESC',
            )
        );
    }

    /**
     * Calcule la balance (débit/crédit) d'une liste de mouvements.
     *
     * @param Mouvement[] $mouvements
     *
     * @return float
     */
    public function getBalanceByMouvements($mouvements)
    {
        $balance = 0;

        foreach ($mouvements as $mouvement) {
            $montant = $mouvement->getMontant();
            $balance += $montant;
        }

        return $balance;
    }

    /**
     * Calcule la balance (débit/crédit) de comptes bancaires cumulés.
     *
     * @param Compte[]  $comptes
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd   Date de fin, incluse.
     *
     * @return float
     */
    public function getBalanceByComptes($comptes, \DateTime $dateStart = null, \DateTime $dateEnd = null)
    {
        $balance = 0;

        $mouvementRepository = $this->getEntityManager()->getRepository('ComptesBundle:Mouvement');

        foreach ($comptes as $compte) {

            if ($dateStart !== null && $dateEnd !== null) {
                $mouvements = $mouvementRepository->findByCompteAndDate($compte, $dateStart, $dateEnd);
            } elseif ($dateStart !== null) {
                $mouvements = $mouvementRepository->findByCompteSinceDate($compte, $dateStart);
            } elseif ($dateEnd !== null) {
                $mouvements = $mouvementRepository->findByCompteUntilDate($compte, $dateEnd);
            } else {
                $mouvements = $mouvementRepository->findByCompte($compte);
            }

            $balance += $this->getBalanceByMouvements($compte);
        }

        return $balance;
    }
}
