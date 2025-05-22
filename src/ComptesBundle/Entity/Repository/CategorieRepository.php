<?php

namespace ComptesBundle\Entity\Repository;

use ComptesBundle\Entity\Compte;
use Doctrine\ORM\EntityRepository;
use ComptesBundle\Entity\Categorie;

/**
 * Repository des catégories de mouvements bancaires.
 */
class CategorieRepository extends EntityRepository
{
    /**
     * @return Categorie[]
     */
    public function findAll(): array
    {
        return $this->findBy([], [
            'rang' => 'ASC',
        ]);
    }

    /**
     * Calcul le montant cumulé des mouvements d'une catégorie, entre deux dates.
     *
     * @todo : $order peut venir une enum
     *
     * @param Categorie $categorie La catégorie.
     * @param \DateTime $dateStart Date de début, incluse.
     * @param \DateTime $dateEnd   Date de fin, incluse.
     * @param string    $order     'ASC' (par défaut) ou 'DESC'.
     * @param ?Compte   $compte    Un compte, facultatif.
     */
    public function getMontantTotalByDate(Categorie $categorie, \DateTime $dateStart, \DateTime $dateEnd, string $order = 'ASC', ?Compte $compte = null): float
    {
        // Calcul du montant total des mouvements de la catégorie
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $expressionBuilder = $this->getEntityManager()->getExpressionBuilder();

        $and = $expressionBuilder->andX();
        $and->add($expressionBuilder->in('m.categorie', ':categories'));
        $and->add($expressionBuilder->gte('m.date', ':date_start'));
        $and->add($expressionBuilder->lte('m.date', ':date_end'));

        if ($compte instanceof Compte) {
            $and->add($expressionBuilder->eq('m.compte', ':compte'));
            $queryBuilder->setParameter('compte', $compte);
        }

        // La liste des catégories de mouvements
        $categories = [$categorie];
        $categoriesFilles = $categorie->getCategoriesFillesRecursive();

        foreach ($categoriesFilles as $categorieFille) {
            $categories[] = $categorieFille;
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

        $montant = (float) ($result['total'] ?? 0);

        return $montant;
    }
}
