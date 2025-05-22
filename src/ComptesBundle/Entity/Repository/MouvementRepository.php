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
     * @return Mouvement[]
     */
    public function findAll(): array
    {
        return $this->findBy([], [
            'date' => 'DESC',
        ]);
    }

    /**
     * Calcule le montant cumulé de tous les mouvements entre deux dates.
     *
     * @todo : $order peut venir une enum
     *
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd   Date de fin, incluse.
     * @param string    $order     'ASC' (par défaut) ou 'DESC'.
     * @param ?Compte   $compte    Un compte, facultatif.
     */
    public function getMontantTotalByDate(\DateTime $dateStart, \DateTime $dateEnd, string $order = 'ASC', ?Compte $compte = null): float
    {
        // Calcul du montant total des mouvements entre deux dates
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($expressionBuilder->gte('m.date', ':date_start'));
        $and->add($expressionBuilder->lte('m.date', ':date_end'));

        if ($compte instanceof Compte) {
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

        $total = (float) ($result['total'] ?? 0);

        return $total;
    }

    /**
     * Récupère les mouvements d'un compte entre deux dates.
     *
     * @todo : $order peut venir une enum
     *
     * @param Compte    $compte    Le compte bancaire.
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd   Date de fin, incluse.
     * @param string    $order     'ASC' (par défaut) ou 'DESC'.
     *
     * @return Mouvement[]
     */
    public function findByCompteAndDate(Compte $compte, \DateTime $dateStart, \DateTime $dateEnd, string $order = 'ASC'): array
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
     * Récupère les mouvements entre deux dates.
     *
     * @todo : $order peut venir une enum
     *
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd   Date de fin, incluse.
     * @param string    $order     'ASC' (par défaut) ou 'DESC'.
     * @param ?Compte   $compte    Un compte, facultatif.
     *
     * @return Mouvement[]
     */
    public function findByDate(\DateTime $dateStart, \DateTime $dateEnd, string $order = 'ASC', ?Compte $compte = null): array
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($expressionBuilder->gte('m.date', ':date_start'));
        $and->add($expressionBuilder->lte('m.date', ':date_end'));

        if ($compte instanceof Compte) {
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
     * @todo : $order peut venir une enum
     *
     * @param ?Categorie $categorie La catégorie.
     * @param \DateTime  $dateStart Date de début, incluse.
     * @param \DateTime  $dateEnd   Date de fin, incluse.
     * @param string     $order     'ASC' (par défaut) ou 'DESC'.
     * @param ?Compte    $compte    Un compte, facultatif.
     *
     * @return Mouvement[]
     */
    public function findByDateAndCategorie(?Categorie $categorie, \DateTime $dateStart, \DateTime $dateEnd, string $order = 'ASC', ?Compte $compte = null): array
    {
        $queryBuilder = $this->createQueryBuilder('m');
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($expressionBuilder->gte('m.date', ':date_start'));
        $and->add($expressionBuilder->lte('m.date', ':date_end'));

        if ($compte instanceof Compte) {
            $and->add($expressionBuilder->eq('m.compte', ':compte'));
            $queryBuilder->setParameter('compte', $compte);
        }

        if ($categorie instanceof Categorie) {
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
     * @param ?Compte $compte Un compte, facultatif.
     */
    public function findFirstOne(?Compte $compte = null): ?Mouvement
    {
        $queryBuilder = $this->createQueryBuilder('m');

        if ($compte instanceof Compte) {
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
     * @param ?Compte $compte Un compte, facultatif.
     */
    public function findLatestOne(?Compte $compte = null): ?Mouvement
    {
        $queryBuilder = $this->createQueryBuilder('m');

        if ($compte instanceof Compte) {
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
     * Récupère les mouvements du montant donné entre deux dates.
     *
     * @param float     $montant
     * @param \DateTime $dateStart
     * @param \DateTime $dateEnd
     *
     * @return Mouvement[]
     */
    public function findByMontantBetweenDates(float $montant, \DateTime $dateStart, \DateTime $dateEnd): array
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
