<?php

namespace ComptesBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use ComptesBundle\Entity\Categorie;

/**
 * Repository des catégories de mouvements bancaires.
 */
class CategorieRepository extends EntityRepository
{
    /**
     * {@inheritdoc}
     */
    public function findAll()
    {
        return $this->findBy(array(), array('rang' => 'ASC'));
    }

    /**
     * Calcul le montant cumulé des mouvements d'une catégorie.
     *
     * @param Categorie $categorie La catégorie.
     *
     * @return float
     */
    public function getMontantTotal(Categorie $categorie)
    {
        // Calcul du montant total des mouvements de la catégorie
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        // La liste des catégories de mouvements
        $categorieID = $categorie->getId();
        $categories = array($categorieID);
        $categoriesFilles = $categorie->getCategoriesFillesRecursive();

        foreach ($categoriesFilles as $categorieFille) {
            $categories[] = $categorieFille->getId();
        }

        $queryBuilder
            ->select('SUM(m.montant) AS total')
            ->from('ComptesBundle:Mouvement', 'm')
            ->where('m.categorie in (:categories)')
            ->setParameter('categories', $categories);

        $result = $queryBuilder->getQuery()->getSingleResult();

        $montant = $result['total'] !== null ? $result['total'] : 0;

        return $montant;
    }

    /**
     * Calcul le montant cumulé des mouvements d'une catégorie, entre deux dates.
     *
     * @param Categorie   $categorie La catégorie.
     * @param \DateTime   $dateStart Date de début, incluse.
     * @param \DateTime   $dateEnd   Date de fin, incluse.
     * @param string      $order     'ASC' (par défaut) ou 'DESC'.
     * @param Compte|null $compte    Un compte, facultatif.
     *
     * @return float
     */
    public function getMontantTotalByDate(Categorie $categorie, \DateTime $dateStart, \DateTime $dateEnd, $order = 'ASC', $compte = null)
    {
        // Calcul du montant total des mouvements de la catégorie
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($expressionBuilder->in('m.categorie', ':categories'));
        $and->add($expressionBuilder->gte('m.date', ':date_start'));
        $and->add($expressionBuilder->lte('m.date', ':date_end'));

        if ($compte !== null) {
            $and->add($expressionBuilder->eq('m.compte', ':compte'));
            $queryBuilder->setParameter('compte', $compte);
        }

        // La liste des catégories de mouvements
        $categorieID = $categorie->getId();
        $categories = array($categorieID);
        $categoriesFilles = $categorie->getCategoriesFillesRecursive();

        foreach ($categoriesFilles as $categorieFille) {
            $categories[] = $categorieFille->getId();
        }

        $queryBuilder
            ->select('SUM(m.montant) AS total')
            ->from('ComptesBundle:Mouvement', 'm')
            ->where($and)
            ->setParameter('categories', $categories)
            ->setParameter('date_start', $dateStart)
            ->setParameter('date_end', $dateEnd)
            ->orderBy('m.date', $order);

        $result = $queryBuilder->getQuery()->getSingleResult();

        $montant = $result['total'] !== null ? $result['total'] : 0;

        return $montant;
    }
}
