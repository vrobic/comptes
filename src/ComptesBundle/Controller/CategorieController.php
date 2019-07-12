<?php

namespace ComptesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use ComptesBundle\Entity\Categorie;
use ComptesBundle\Entity\Keyword;

/**
 * Contrôleur des catégories de mouvements bancaires.
 */
class CategorieController extends Controller
{
    /**
     * Liste des catégories.
     *
     * @todo Optimiser la vitesse de calcul des statistiques en limitant le nombre de requêtes.
     * @todo Refactorer le nom des variables passées au template.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        // Repositories
        $doctrine = $this->getDoctrine();
        $categorieRepository = $doctrine->getRepository('ComptesBundle:Categorie');
        $compteRepository = $doctrine->getRepository('ComptesBundle:Compte');
        $mouvementRepository = $doctrine->getRepository('ComptesBundle:Mouvement');

        // Fournisseur de statistiques
        $statsProvider = $this->container->get('comptes_bundle.stats.provider');

        // Toutes les catégories
        $categories = $categorieRepository->findAll();

        // Tous les comptes
        $comptes = $compteRepository->findAll();

        // Filtre sur le compte
        if ($request->get('compte_id')) {
            $compteID = $request->get('compte_id');
            $compte = $compteRepository->find($compteID);
            if (!$compte) {
                throw $this->createNotFoundException("Le compte bancaire $compteID n'existe pas.");
            }
        } else {
            $compte = null;
        }

        // Filtre sur la période
        if ($request->get('date_filter')) {
            $dateFilterString = $request->get('date_filter');
            $dateStartString = $dateFilterString['start'];
            $dateEndString = $dateFilterString['end'];

            $dateStart = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateStartString 00:00:00");
            $dateEnd = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateEndString 23:59:59");
        } else { // Par défaut, depuis un an et jusqu'à la fin du mois
            list ($year, $month, $lastDayOfMonth) = explode('-', date('Y-n-t'));

            $month = (int) $month;
            $year = (int) $year;
            $lastDayOfMonth = (int) $lastDayOfMonth;

            $dateStart = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-1 00:00:00");
            $dateStart->modify('-1 year')->setTime(0, 0); // Depuis un an
            $dateEnd = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 23:59:59");
        }

        if (!$dateStart || !$dateEnd || $dateStart > $dateEnd) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("La période de dates est invalide.");
        }

        $dateFilter = array(
            'start' => $dateStart,
            'end' => $dateEnd,
        );

        // Années de début et de fin pour les classements par années
        $yearStart = (int) date('Y');
        $yearEnd = $yearStart;
        $firstMouvement = $mouvementRepository->findFirstOne($compte);
        if (null !== $firstMouvement) {
            $firstMouvementDate = $firstMouvement->getDate();
            $yearStart = (int) $firstMouvementDate->format('Y');
        }

        // Montant total des mouvements, toutes catégories confondues
        $yearlyMontants = $statsProvider->getYearlyMontants($yearStart, $yearEnd, $compte);

        // Montant total des mouvements par catégorie
        $montants = array(); // @todo : expliciter le nom de la variable

        // Montant cumulé de tous les mouvements, et des mouvements catégorisés sur la période donnée
        $montantTotalPeriode = $mouvementRepository->getMontantTotalByDate($dateFilter['start'], $dateFilter['end'], 'ASC', $compte);
        $montantTotalPeriodeCategorise = 0;

        foreach ($categories as $categorie) {
            $categorieID = $categorie->getId();

            // Montant cumulé des mouvements de la catégorie sur la période donnée
            $montantTotalPeriodeCategorie = $categorieRepository->getMontantTotalByDate($categorie, $dateFilter['start'], $dateFilter['end'], 'ASC', $compte);

            // Si la catégorie est de premier niveau, on la prend en compte dans le calcul du total des mouvements catégorisés
            if ($categorie->getCategorieParente() === null) {
                $montantTotalPeriodeCategorise += $montantTotalPeriodeCategorie;
            }

            // Montant cumulé des mouvements de la catégorie, année par année
            $montantsAnnuelsCategorie = $statsProvider->getYearlyMontantsByCategorie($categorie, $yearStart, $yearEnd, $compte);

            // Montant mensuel moyen des mouvements de la catégorie
            $average = $statsProvider->getAverageMonthlyMontantsByCategorie($categorie, $dateFilter['start'], $dateFilter['end'], $compte);

            $montants[$categorieID] = array(
                'period' => $montantTotalPeriodeCategorie,
                'yearly' => $montantsAnnuelsCategorie,
                'average' => $average,
            );
        }

        // Montant total des mouvements non catégorisés
        $montantTotalPeriodeNonCategorise = $montantTotalPeriode - $montantTotalPeriodeCategorise;

        return $this->render(
            'ComptesBundle:Categorie:index.html.twig',
            array(
                'categories' => $categories,
                'comptes' => $comptes,
                'compte_filter' => $compte,
                'date_filter' => $dateFilter,
                'montants' => $montants,
                'montant_total' => $montantTotalPeriode, // Sur la période
                'montant_total_non_categorise' => $montantTotalPeriodeNonCategorise, // Sur la période
                'yearly_montants' => $yearlyMontants, // Depuis toujours
            )
        );
    }

    /**
     * Affichage d'une catégorie.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function showAction(Request $request)
    {
        // Repositories
        $doctrine = $this->getDoctrine();
        $categorieRepository = $doctrine->getRepository('ComptesBundle:Categorie');
        $compteRepository = $doctrine->getRepository('ComptesBundle:Compte');
        $mouvementRepository = $doctrine->getRepository('ComptesBundle:Mouvement');

        // La catégorie
        $categorieID = $request->get('categorie_id');

        if ($categorieID > 0) {
            $categorie = $categorieRepository->find($categorieID);
            if (!$categorie) {
                throw $this->createNotFoundException("La catégorie $categorieID n'existe pas.");
            }
        } else {
            $categorie = null;
        }

        // Toutes les catégories
        $categories = $categorieRepository->findAll();

        // Tous les comptes
        $comptes = $compteRepository->findAll();

        // Filtre sur le compte
        if ($request->get('compte_id')) {
            $compteID = $request->get('compte_id');
            $compte = $compteRepository->find($compteID);
            if (!$compte) {
                throw $this->createNotFoundException("Le compte bancaire $compteID n'existe pas.");
            }
        } else {
            $compte = null;
        }

        // Filtre sur la période
        if ($request->get('date_filter')) {
            $dateFilterString = $request->get('date_filter');
            $dateStartString = $dateFilterString['start'];
            $dateEndString = $dateFilterString['end'];

            $dateStart = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateStartString 00:00:00");
            $dateEnd = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateEndString 23:59:59");
        } else { // Par défaut, depuis un an et jusqu'à la fin du mois
            list($year, $month, $lastDayOfMonth) = explode('-', date('Y-n-t'));

            $month = (int) $month;
            $year = (int) $year;
            $lastDayOfMonth = (int) $lastDayOfMonth;

            $dateStart = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-1 00:00:00");
            $dateStart->modify('-1 year')->setTime(0, 0); // Depuis un an
            $dateEnd = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 23:59:59");
        }

        if (!$dateStart || !$dateEnd || $dateStart > $dateEnd) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("La période de dates est invalide.");
        }

        $dateFilter = array(
            'start' => $dateStart,
            'end' => $dateEnd,
        );

        // Tous les mouvements de la catégorie sur la période donnée
        $mouvements = $mouvementRepository->findByDateAndCategorie($categorie, $dateFilter['start'], $dateFilter['end'], 'ASC', $compte);

        // Montant total et mensuel moyen de la catégorie
        $total = 0;
        $average = 0;

        // Total des mouvements par mois
        $monthlyMontants = array();

        // Total des mouvements par catégorie (la courante et ses filles éventuelles)
        $montants = array(); // @todo : expliciter le nom de la variable

        if ($mouvements) {
            foreach ($mouvements as $mouvement) {
                $montant = $mouvement->getMontant();
                $total += $montant;
            }

            $firstMouvement = reset($mouvements);
            $lastMouvement = end($mouvements);
            $firstMouvementDate = $firstMouvement->getDate();
            $lastMouvementDate = $lastMouvement->getDate();

            $statsProvider = $this->container->get('comptes_bundle.stats.provider');
            $monthlyMontants = $statsProvider->getMonthlyMontantsByCategorie($categorie, $firstMouvementDate, $lastMouvementDate, $compte);
            $average = $statsProvider->getAverageMonthlyMontantsByCategorie($categorie, $dateFilter['start'], $dateFilter['end'], $compte);

            // Le total des mouvements de la catégorie
            if ($categorieID > 0) {
                $montants[$categorieID] = $total;
            }

            // Le total des mouvements des catégories filles
            if (null !== $categorie) {
                foreach ($categorie->getCategoriesFilles() as $categorieFille) {
                    $categorieFilleID = $categorieFille->getId();
                    $montants[$categorieFilleID] = $categorieRepository->getMontantTotalByDate($categorieFille, $dateFilter['start'], $dateFilter['end'], 'ASC', $compte);
                }
            }
        }

        return $this->render(
            'ComptesBundle:Categorie:show.html.twig',
            array(
                'categorie' => $categorie,
                'categories' => $categories,
                'comptes' => $comptes,
                'compte_filter' => $compte,
                'date_filter' => $dateFilter,
                'mouvements' => $mouvements,
                'total' => $total,
                'average' => $average,
                'monthly_montants' => $monthlyMontants,
                'montants' => $montants,
            )
        );
    }

    /**
     * Édition de catégories par lots.
     *
     * @todo Utiliser un formulaire Symfony.
     *
     * @param Request $request
     *
     * @return Response
     */
    public function editAction(Request $request)
    {
        // Entity manager et repositories
        $doctrine = $this->getDoctrine();
        $manager = $doctrine->getManager();
        $categorieRepository = $doctrine->getRepository('ComptesBundle:Categorie');
        $keywordRepository = $doctrine->getRepository('ComptesBundle:Keyword');

        // Tous les mots-clés, classés par catégories
        $keywords = $keywordRepository->findAllSortedByCategories();

        // Valeurs postées
        $action = $request->get('action');
        $batchArray = $request->get('batch', array());
        $categoriesArray = $request->get('categories', array());

        foreach ($batchArray as $categorieID) {
            if (isset($categoriesArray[$categorieID])) {
                $categorieArray = $categoriesArray[$categorieID];
                $categorie = $categorieID > 0 ? $categorieRepository->find($categorieID) : new Categorie();

                switch ($action) {
                    case 'save': // Création et édition
                        // Nom
                        if (isset($categorieArray['nom'])) {
                            $nom = $categorieArray['nom'];
                            $categorie->setNom($nom);
                        }

                        // Catégorie parente
                        if (isset($categorieArray['categorieParente'])) {
                            $categorieParenteID = $categorieArray['categorieParente'];

                            if ($categorieParenteID === $categorieID) {
                                throw new \ComptesBundle\Exception\MerIlEtFouException("Référence circulaire. Tu veux tomber dans l'hyper espace ?");
                            }

                            $categorieParente = $categorieParenteID !== '' ? $categorieRepository->find($categorieParenteID) : null;
                            $categorie->setCategorieParente($categorieParente);
                        }

                        // Mots-clés
                        if (isset($categorieArray['keywords'])) {
                            $words = array_diff(explode('|', $categorieArray['keywords']), array(''));
                            $keywords = $categorie->getKeywords();

                            // Supprime les mots-clés qui ne sont plus sélectionnés
                            foreach ($keywords as $keyword) {
                                $word = $keyword->getWord();

                                if (!in_array($word, $words)) {
                                    $categorie->removeKeyword($keyword);
                                    $manager->remove($keyword);
                                }
                            }

                            // Ajoute les mots-clés sélectionnés
                            foreach ($words as $word) {
                                // Ce mot-clé existe-il déjà ?
                                $keyword = $keywordRepository->findOneBy(array('word' => $word));

                                if (null === $keyword) { // Si non, on le crée
                                    $keyword = new Keyword();
                                    $keyword->setWord($word);
                                    $keyword->setCategorie($categorie);
                                } else { // Si oui, on vérifie qu'il n'est pas déjà affecté à une autre catégorie
                                    $keywordCategorie = $keyword->getCategorie();
                                    $keywordCategorieID = $keywordCategorie->getId();

                                    if ($keywordCategorieID !== $categorieID) {
                                        throw new \Exception("Le mot-clé [$keyword] ne peut pas être ajouté à la catégorie [$categorie] puisqu'il est déjà affecté à [$keywordCategorie].");
                                    }
                                }

                                $categorie->addKeyword($keyword);
                            }
                        }

                        // Rang
                        if (isset($categorieArray['rang'])) {
                            $rang = $categorieArray['rang'] !== '' ? (int) $categorieArray['rang'] : null;
                            $categorie->setRang($rang);
                        }

                        $manager->persist($categorie);

                        break;

                    case 'delete': // Suppression
                        // Décroche tous les mouvements liés à cette catégorie
                        $mouvements = $categorie->getMouvements();

                        foreach ($mouvements as $mouvement) {
                            $mouvement->setCategorie(null);
                            $manager->persist($mouvement);
                        }

                        $manager->remove($categorie);

                        break;
                }
            }
        }

        $manager->flush();

        // URL de redirection
        $redirectURL = $request->get('redirect_url', null);

        if (null !== $redirectURL) {
            return $this->redirect($redirectURL);
        }

        $categories = $categorieRepository->findAll();

        return $this->render(
            'ComptesBundle:Categorie:edit.html.twig',
            array(
                'categories' => $categories,
                'keywords' => $keywords,
            )
        );
    }
}
