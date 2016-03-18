<?php

namespace ComptesBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use ComptesBundle\Entity\Plein;
use ComptesBundle\Entity\Vehicule;

/**
 * Repository des pleins de carburant.
 */
class PleinRepository extends EntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->findBy(array(), array('date' => 'DESC'));
    }

    /**
     * Récupère les pleins d'un véhicule.
     *
     * @param Vehicule $vehicule
     * @param string   $order    'ASC' (par défaut) ou 'DESC'.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findByVehicule(Vehicule $vehicule, $order = 'ASC')
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder
            ->where('p.vehicule = :vehicule')
            ->orderBy('p.date', $order)
            ->setParameter(':vehicule', $vehicule);

        $pleins = $queryBuilder->getQuery()->getResult();

        return $pleins;
    }

    /**
     * Récupère les pleins entre deux dates.
     *
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd   Date de fin, incluse.
     * @param string    $order     'ASC' (par défaut) ou 'DESC'.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findByDate(\DateTime $dateStart, \DateTime $dateEnd, $order = 'ASC')
    {
        $queryBuilder = $this->createQueryBuilder('p');
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($expressionBuilder->gte('p.date', ':date_start'));
        $and->add($expressionBuilder->lte('p.date', ':date_end'));

        $queryBuilder
            ->where($and)
            ->setParameter('date_start', $dateStart)
            ->setParameter('date_end', $dateEnd)
            ->orderBy('p.date', $order);

        $pleins = $queryBuilder->getQuery()->getResult();

        return $pleins;
    }

    /**
     * Récupère le plein le plus récent.
     *
     * @return Plein
     */
    public function findLatestOne()
    {
        $queryBuilder = $this->createQueryBuilder('p');

        $queryBuilder
            ->orderBy('p.date', 'DESC')
            ->setMaxResults(1);

        try {
            $plein = $queryBuilder->getQuery()->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $plein = null;
        }

        return $plein;
    }
}
