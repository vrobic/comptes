<?php

namespace ComptesBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use ComptesBundle\Entity\Plein;

/**
 * Repository des pleins de carburant.
 */
class PleinRepository extends EntityRepository
{
    /**
     * @return Plein[]
     */
    public function findAll(): array
    {
        return $this->findBy([], [
            'date' => 'DESC',
        ]);
    }

    /**
     * Récupère les pleins entre deux dates.
     *
     * @todo : $order peut venir une enum
     *
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd   Date de fin, incluse.
     * @param string    $order     'ASC' (par défaut) ou 'DESC'.
     *
     * @return Plein[]
     */
    public function findByDate(\DateTime $dateStart, \DateTime $dateEnd, string $order = 'ASC'): array
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
     */
    public function findLatestOne(): ?Plein
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
