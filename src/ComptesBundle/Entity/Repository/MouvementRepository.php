<?php

namespace ComptesBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use ComptesBundle\Entity\Mouvement;
use ComptesBundle\Entity\Compte;
use ComptesBundle\Entity\Categorie;

/**
 * Repository des mouvements bancaires.
 */
class MouvementRepository extends EntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->findBy([], [
            'date' => 'DESC',
        ]);
    }

    /**
     * Calcule le montant cumulé de tous les mouvements.
     *
     * @return float
     */
    public function getMontantTotal()
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $queryBuilder
            ->select('SUM(m.montant) AS total')
            ->from('ComptesBundle:Mouvement', 'm');

        $result = $queryBuilder->getQuery()->getSingleResult();

        $total = $result['total'] !== null ? $result['total'] : 0;

        return $total;
    }

    /**
     * Calcule le montant cumulé de tous les mouvements entre deux dates.
     *
     * @param \DateTime   $dateStart Date de début, incluse.
     * @param \DateTime   $dateEnd   Date de fin, incluse.
     * @param string      $order     'ASC' (par défaut) ou 'DESC'.
     * @param Compte|null $compte    Un compte, facultatif.
     *
     * @return float
     */
    public function getMontantTotalByDate(\DateTime $dateStart, \DateTime $dateEnd, $order = 'ASC', $compte = null)
    {
        // Calcul du montant total des mouvements entre deux dates
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($expressionBuilder->gte('m.date', ':date_start'));
        $and->add($expressionBuilder->lte('m.date', ':date_end'));

        if (null !== $compte) {
            $and->add($expressionBuilder->eq('m.compte', ':compte'));
            $queryBuilder->setParameter('compte', $compte);
        }

        $queryBuilder
            ->select('SUM(m.montant) AS total')
            ->from('ComptesBundle:Mouvement', 'm')
            ->where($and)
            ->setParameter('date_start', $dateStart)
            ->setParameter('date_end', $dateEnd)
            ->orderBy('m.date', $order);

        $result = $queryBuilder->getQuery()->getSingleResult();

        $total = $result['total'] !== null ? $result['total'] : 0;

        return $total;
    }

    /**
     * Récupère les mouvements d'un compte.
     *
     * @param Compte $compte
     * @param string $order  'ASC' (par défaut) ou 'DESC'.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findByCompte(Compte $compte, $order = 'ASC')
    {
        $queryBuilder = $this->createQueryBuilder('m');

        $queryBuilder
            ->where('m.compte = :compte')
            ->orderBy('m.date', $order)
            ->setParameter(':compte', $compte);

        $mouvements = $queryBuilder->getQuery()->getResult();

        return $mouvements;
    }

    /**
     * Récupère les mouvements d'un compte,
     * depuis le début jusqu'à une date donnée (incluse).
     *
     * @todo Mutualiser avec self->findByCompteSinceDate()
     *
     * @param Compte    $compte
     * @param \DateTime $date
     * @param string    $order  'ASC' (par défaut) ou 'DESC'.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findByCompteUntilDate(Compte $compte, \DateTime $date, $order = 'ASC')
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($expressionBuilder->eq('m.compte', ':compte'));
        $and->add($expressionBuilder->lte('m.date', ':date'));

        $queryBuilder
            ->where($and)
            ->orderBy('m.date', $order)
            ->setParameter(':compte', $compte)
            ->setParameter(':date', $date);

        $mouvements = $queryBuilder->getQuery()->getResult();

        return $mouvements;
    }

    /**
     * Récupère les mouvements d'un compte,
     * depuis une date donnée jusqu'à aujourd'hui (inclus).
     *
     * @todo Mutualiser avec self->findByCompteUntilDate()
     *
     * @param Compte    $compte
     * @param \DateTime $date
     * @param string    $order  'ASC' (par défaut) ou 'DESC'.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findByCompteSinceDate(Compte $compte, \DateTime $date, $order = 'ASC')
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($expressionBuilder->eq('m.compte', ':compte'));
        $and->add($expressionBuilder->gte('m.date', ':date'));

        $queryBuilder
            ->where($and)
            ->orderBy('m.date', $order)
            ->setParameter(':compte', $compte)
            ->setParameter(':date', $date);

        $mouvements = $queryBuilder->getQuery()->getResult();

        return $mouvements;
    }

    /**
     * Récupère les mouvements d'un compte entre deux dates.
     *
     * @param Compte    $compte    Le compte bancaire.
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd   Date de fin, incluse.
     * @param string    $order     'ASC' (par défaut) ou 'DESC'.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findByCompteAndDate(Compte $compte, \DateTime $dateStart, \DateTime $dateEnd, $order = 'ASC')
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($queryBuilder->expr()->eq('m.compte', ':compte'));
        $and->add($expressionBuilder->gte('m.date', ':date_start'));
        $and->add($expressionBuilder->lte('m.date', ':date_end'));

        $queryBuilder
            ->where($and)
            ->setParameter('compte', $compte)
            ->setParameter('date_start', $dateStart)
            ->setParameter('date_end', $dateEnd)
            ->orderBy('m.date', $order);

        $mouvements = $queryBuilder->getQuery()->getResult();

        return $mouvements;
    }

    /**
     * Récupère les mouvements,
     * depuis le début jusqu'à une date donnée (incluse).
     *
     * @todo Mutualiser avec self->findSinceDate()
     *
     * @param \DateTime $date
     * @param string    $order 'ASC' (par défaut) ou 'DESC'.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findUntilDate(\DateTime $date, $order = 'ASC')
    {
        $queryBuilder = $this->createQueryBuilder('m');

        $queryBuilder
            ->where('m.date <= :date')
            ->orderBy('m.date', $order)
            ->setParameter(':date', $date);

        $mouvements = $queryBuilder->getQuery()->getResult();

        return $mouvements;
    }

    /**
     * Récupère les mouvements,
     * depuis une date donnée jusqu'à aujourd'hui (inclus).
     *
     * @todo Mutualiser avec self->findUntilDate()
     *
     * @param \DateTime $date
     * @param string    $order 'ASC' (par défaut) ou 'DESC'.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findSinceDate(\DateTime $date, $order = 'ASC')
    {
        $queryBuilder = $this->createQueryBuilder('m');

        $queryBuilder
            ->where('m.date >= :date')
            ->orderBy('m.date', $order)
            ->setParameter(':date', $date);

        $mouvements = $queryBuilder->getQuery()->getResult();

        return $mouvements;
    }

    /**
     * Récupère les mouvements entre deux dates.
     *
     * @param \DateTime   $dateStart Date de début, incluse.
     * @param \DateTime   $dateEnd   Date de fin, incluse.
     * @param string      $order     'ASC' (par défaut) ou 'DESC'.
     * @param Compte|null $compte    Un compte, facultatif.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findByDate(\DateTime $dateStart, \DateTime $dateEnd, $order = 'ASC', $compte = null)
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($expressionBuilder->gte('m.date', ':date_start'));
        $and->add($expressionBuilder->lte('m.date', ':date_end'));

        if (null !== $compte) {
            $and->add($expressionBuilder->eq('m.compte', ':compte'));
            $queryBuilder->setParameter('compte', $compte);
        }

        $queryBuilder
            ->where($and)
            ->setParameter('date_start', $dateStart)
            ->setParameter('date_end', $dateEnd)
            ->orderBy('m.date', $order);

        $mouvements = $queryBuilder->getQuery()->getResult();

        return $mouvements;
    }

    /**
     * Récupère les mouvements d'une catégorie, entre deux dates.
     *
     * @param Categorie|null $categorie La catégorie.
     * @param \DateTime      $dateStart Date de début, incluse.
     * @param \DateTime      $dateEnd   Date de fin, incluse.
     * @param string         $order     'ASC' (par défaut) ou 'DESC'.
     * @param Compte|null    $compte    Un compte, facultatif.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findByDateAndCategorie($categorie, \DateTime $dateStart, \DateTime $dateEnd, $order = 'ASC', $compte = null)
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($expressionBuilder->gte('m.date', ':date_start'));
        $and->add($expressionBuilder->lte('m.date', ':date_end'));

        if (null !== $compte) {
            $and->add($expressionBuilder->eq('m.compte', ':compte'));
            $queryBuilder->setParameter('compte', $compte);
        }

        if (null !== $categorie) {
            // La liste des catégories de mouvements
            $categories = [$categorie];
            $categoriesFilles = $categorie->getCategoriesFillesRecursive();

            foreach ($categoriesFilles as $categorieFille) {
                $categories[] = $categorieFille;
            }

            $and->add($expressionBuilder->in('m.categorie', ':categories'));
            $queryBuilder
                ->where($and)
                ->setParameter('categories', $categories);
        } else {
            $and->add($expressionBuilder->isNull('m.categorie'));
            $queryBuilder->where($and);
        }

        $queryBuilder
            ->setParameter('date_start', $dateStart)
            ->setParameter('date_end', $dateEnd)
            ->orderBy('m.date', $order);

        $mouvements = $queryBuilder->getQuery()->getResult();

        return $mouvements;
    }

    /**
     * Récupère le mouvement le plus ancien.
     *
     * @todo Mutualiser avec self->findLatestOne()
     *
     * @param Compte|null $compte Un compte, facultatif.
     *
     * @return Mouvement
     */
    public function findFirstOne($compte = null)
    {
        $queryBuilder = $this->createQueryBuilder('m');

        if (null !== $compte) {
            $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();
            $queryBuilder
                ->where($expressionBuilder->eq('m.compte', ':compte'))
                ->setParameter('compte', $compte);
        }

        $queryBuilder
            ->orderBy('m.date', 'ASC')
            ->setMaxResults(1);

        try {
            $mouvement = $queryBuilder->getQuery()->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $mouvement = null;
        }

        return $mouvement;
    }

    /**
     * Récupère le mouvement le plus récent.
     *
     * @todo Mutualiser avec self->findFirstOne()
     *
     * @param Compte|null $compte Un compte, facultatif.
     *
     * @return Mouvement
     */
    public function findLatestOne($compte = null)
    {
        $queryBuilder = $this->createQueryBuilder('m');

        if (null !== $compte) {
            $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();
            $queryBuilder
                ->where($expressionBuilder->eq('m.compte', ':compte'))
                ->setParameter('compte', $compte);
        }

        $queryBuilder
            ->orderBy('m.date', 'DESC')
            ->setMaxResults(1);

        try {
            $mouvement = $queryBuilder->getQuery()->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            $mouvement = null;
        }

        return $mouvement;
    }

    /**
     * Récupère les mouvements d'une catégorie.
     *
     * @param Categorie|null $categorie La catégorie.
     * @param string         $order     'ASC' (par défaut) ou 'DESC'.
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findByCategorie($categorie, $order = 'ASC')
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        if (null !== $categorie) {
            // La liste des catégories de mouvements
            $categories = [$categorie];
            $categoriesFilles = $categorie->getCategoriesFillesRecursive();

            foreach ($categoriesFilles as $categorieFille) {
                $categories[] = $categorieFille;
            }

            $queryBuilder
                ->where($expressionBuilder->in('m.categorie', ':categories'))
                ->setParameter('categories', $categories);
        } else {
            $queryBuilder->where($expressionBuilder->isNull('m.categorie'));
        }

        $queryBuilder->orderBy('m.date', $order);

        $mouvements = $queryBuilder->getQuery()->getResult();

        return $mouvements;
    }

    /**
     * Récupère les mouvements du montant donné entre deux dates.
     *
     * @param float     $montant
     * @param \DateTime $dateStart
     * @param \DateTime $dateEnd
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function findByMontantBetweenDates($montant, $dateStart, $dateEnd)
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($expressionBuilder->eq('m.montant', ':montant'));
        $and->add($expressionBuilder->gte('m.date', ':date_start'));
        $and->add($expressionBuilder->lte('m.date', ':date_end'));

        $queryBuilder
            ->where($and)
            ->setParameter(':montant', $montant)
            ->setParameter(':date_start', $dateStart)
            ->setParameter(':date_end', $dateEnd);

        $mouvements = $queryBuilder->getQuery()->getResult();

        return $mouvements;
    }
}
