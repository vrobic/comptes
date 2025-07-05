<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\StatsProvider;
use App\Domain\Categorie\Categorie;
use App\Domain\Compte\Compte;
use App\Domain\DataStructure\Maybe;
use App\Domain\Keyword\Keyword;
use App\Domain\Keyword\KeywordCollection;
use App\Domain\Mouvement\Mouvement;
use App\Infrastructure\Repository\CategorieRepository;
use App\Infrastructure\Repository\CompteRepository;
use App\Infrastructure\Repository\KeywordRepository;
use App\Infrastructure\Repository\MouvementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CategorieController extends AbstractController
{
    public function __construct(
        private readonly CategorieRepository $categorieRepository,
        private readonly CompteRepository $compteRepository,
        private readonly MouvementRepository $mouvementRepository,
        private readonly KeywordRepository $keywordRepository,
        private readonly StatsProvider $statsProvider,
    ) {
    }

    #[Route('/categories', name: 'categories_categories')]
    public function liste(Request $request): Response
    {
        // Toutes les catégories
        $categories = $this->categorieRepository->findAll();

        // Tous les comptes
        $comptes = $this->compteRepository->findAll();

        // Filtre sur le compte
        if ($request->get('compte_id')) {
            $compteID = (int) $request->get('compte_id');
            $compte = $this->compteRepository->find($compteID);
            if (!($compte instanceof Compte)) {
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
            if ($dateStart instanceof \DateTime) {
                $dateStart->modify('-1 year')->setTime(0, 0); // Depuis un an
            }
            $dateEnd = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 23:59:59");
        }

        if (!($dateStart instanceof \DateTime) || !($dateEnd instanceof \DateTime) || $dateStart > $dateEnd) {
            throw new BadRequestHttpException('La période de dates est invalide.');
        }

        // Années de début et de fin pour les classements par années
        $firstMouvement = $this->mouvementRepository->findFirstOne($compte?->getId());
        $yearStart = (int) ($firstMouvement instanceof Mouvement ? $firstMouvement->getDate() : $dateStart)->format('Y');
        $yearEnd = (int) $dateEnd->format('Y');

        // Montant total des mouvements, toutes catégories confondues
        $yearlyMontants = $this->statsProvider->getYearlyMontants(
            $yearStart,
            $yearEnd,
            Maybe::nothing(),
            $compte instanceof Compte ? Maybe::from($compte) : Maybe::nothing(),
        );

        // Montant total des mouvements par catégorie
        $montants = []; // @todo : tous ces montants devraient plutôt s'appeler balance ?

        // Montant cumulé de tous les mouvements, et des mouvements catégorisés sur la période donnée
        $montantTotalPeriode = $this->mouvementRepository->getMontantTotalByDate($dateStart, $dateEnd, $compte?->getId());
        $montantTotalPeriodeCategorise = 0;

        foreach ($categories as $categorie) {
            $categorieID = $categorie->getId();

            // Montant cumulé des mouvements de la catégorie sur la période donnée
            $montantTotalPeriodeCategorie = $this->categorieRepository->getMontantTotalByDate($categorieID, $dateStart, $dateEnd, $compte?->getId());

            // Si la catégorie est de premier niveau, on la prend en compte dans le calcul du total des mouvements catégorisés
            if (null === $categorie->getCategorieParente()) {
                $montantTotalPeriodeCategorise += $montantTotalPeriodeCategorie;
            }

            // Montant cumulé des mouvements de la catégorie, année par année
            $montantsAnnuelsCategorie = $this->statsProvider->getYearlyMontants(
                $yearStart,
                $yearEnd,
                Maybe::from($categorie),
                $compte instanceof Compte ? Maybe::from($compte) : Maybe::nothing(),
            );

            // Montant mensuel moyen des mouvements de la catégorie
            $average = $this->statsProvider->getAverageMonthlyMontants(
                $dateStart,
                $dateEnd,
                Maybe::from($categorie),
                $compte instanceof Compte ? Maybe::from($compte) : Maybe::nothing(),
            );

            $montants[$categorieID] = [
                'period' => $montantTotalPeriodeCategorie,
                'yearly' => $montantsAnnuelsCategorie,
                'average' => $average,
            ];
        }

        // Montant total des mouvements non catégorisés
        $montantTotalPeriodeNonCategorise = $montantTotalPeriode - $montantTotalPeriodeCategorise;

        return $this->render(
            'Categorie/index.html.twig',
            [
                'categories' => $categories->toArray(
                    static fn (int $categorieId): int => $categorieId,
                    static fn (Categorie $categorie): Categorie => $categorie
                ),
                'comptes' => $comptes,
                'compte_filter' => $compte,
                'date_filter' => [
                    'start' => $dateStart,
                    'end' => $dateEnd,
                ],
                'montants' => $montants,
                'montant_total' => $montantTotalPeriode, // Sur la période
                'montant_total_non_categorise' => $montantTotalPeriodeNonCategorise, // Sur la période
                'yearly_montants' => $yearlyMontants, // Depuis toujours
            ]
        );
    }

    #[Route('/categorie/{categorieId}', name: 'categories_categorie')]
    public function détail(
        Request $request,
        int $categorieId,
    ): Response {
        if ($categorieId > 0) {
            $categorie = $this->categorieRepository->find($categorieId);
            if (!($categorie instanceof Categorie)) {
                throw new NotFoundHttpException("La catégorie $categorieId n'existe pas.");
            }
        } else {
            $categorie = null;
        }

        // Toutes les catégories
        $categories = $this->categorieRepository->findAll();

        // Tous les comptes
        $comptes = $this->compteRepository->findAll();

        // Filtre sur le compte
        if ($request->get('compte_id')) {
            $compteID = (int) $request->get('compte_id');
            $compte = $this->compteRepository->find($compteID);
            if (!($compte instanceof Compte)) {
                throw new NotFoundHttpException("Le compte bancaire $compteID n'existe pas.");
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
            if ($dateStart instanceof \DateTime) {
                $dateStart->modify('-1 year')->setTime(0, 0); // Depuis un an
            }
            $dateEnd = \DateTime::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 23:59:59");
        }

        if (!($dateStart instanceof \DateTime) || !($dateEnd instanceof \DateTime) || $dateStart > $dateEnd) {
            throw new BadRequestHttpException('La période de dates est invalide.');
        }

        // Tous les mouvements de la catégorie sur la période donnée
        $mouvements = $this->mouvementRepository->findBy(
            categoriesIds: Maybe::from(
                $categorie instanceof Categorie ?
                    array_merge([$categorie->getId()], $this->categorieRepository->getCategoriesFillesRecursive($categorie->getId())) :
                    null
            ),
            compteId: $compte instanceof Compte ? Maybe::from($compte->getId()) : Maybe::nothing(),
            dateStart: Maybe::from($dateStart),
            dateEnd: Maybe::from($dateEnd),
            montant: Maybe::nothing(),
        );

        // Montant total et mensuel moyen de la catégorie
        $total = 0;
        $average = 0;

        // Total des mouvements par mois
        $monthlyMontants = [];
        $uniquementDesDebits = true;
        $uniquementDesCredits = true;

        // Total des mouvements par catégorie (la courante et ses filles éventuelles)
        $montants = []; // @todo : expliciter le nom de la variable

        if (!$mouvements->isEmpty()) {
            foreach ($mouvements as $mouvement) {
                $montant = $mouvement->getMontant();
                $total += $montant;
            }

            $monthlyMontants = $this->statsProvider->getMonthlyMontants(
                $dateStart,
                $dateEnd,
                Maybe::from($categorie),
                $compte instanceof Compte ? Maybe::from($compte) : Maybe::nothing(),
            );

            foreach ($monthlyMontants as $months) {
                foreach ($months as $montant) {
                    if ($montant > 0) {
                        $uniquementDesDebits = false;
                    } elseif ($montant < 0) {
                        $uniquementDesCredits = false;
                    }
                }
            }

            $average = $this->statsProvider->getAverageMonthlyMontants(
                $dateStart,
                $dateEnd,
                Maybe::from($categorie),
                $compte instanceof Compte ? Maybe::from($compte) : Maybe::nothing(),
            );

            // Le total des mouvements de la catégorie
            if ($categorieId > 0) {
                $montants[$categorieId] = $total;
            }

            // Le total des mouvements des catégories filles
            if ($categorie instanceof Categorie) {
                foreach ($categorie->getCategoriesFilles() as $categorieFilleID) {
                    $montants[$categorieFilleID] = $this->categorieRepository->getMontantTotalByDate(
                        categorieId: $categorieFilleID,
                        dateStart: $dateStart,
                        dateEnd: $dateEnd,
                        compteId: $compte?->getId(),
                    );
                }
            }
        }

        return $this->render(
            'Categorie/show.html.twig',
            [
                'categorie' => $categorie,
                'categories' => $categories->toArray(
                    static fn (int $categorieId): int => $categorieId,
                    static fn (Categorie $categorie): Categorie => $categorie
                ),
                'comptes' => $comptes,
                'compte_filter' => $compte,
                'date_filter' => [
                    'start' => $dateStart,
                    'end' => $dateEnd,
                ],
                'mouvements' => $mouvements,
                'total' => $total,
                'average' => $average,
                'monthly_montants' => $monthlyMontants,
                'montants' => $montants,
                'uniquement_des_debits' => $uniquementDesDebits,
                'uniquement_des_credits' => $uniquementDesCredits,
            ]
        );
    }

    #[Route('/categories/edit', name: 'categories_edit')]
    public function edit(Request $request): Response
    {
        $keywords = $this->keywordRepository->findAll();
        $keywordsParCatégorie = $keywords->trierParCatégorie();

        // Valeurs postées
        $action = $request->get('action');
        $batchArray = $request->get('batch', []);
        $categoriesArray = $request->get('categories', []);

        foreach ($batchArray as $categorieID) {
            $categorieID = (int) $categorieID;

            if (isset($categoriesArray[$categorieID])) {
                $categorieArray = $categoriesArray[$categorieID];

                switch ($action) {
                    case 'save': // Création et édition
                        // Nom
                        if (isset($categorieArray['nom'])) {
                            $nom = $categorieArray['nom'];
                        }

                        // Catégorie parente
                        if (isset($categorieArray['categorieParente'])) {
                            $categorieParenteID = (int) $categorieArray['categorieParente'];

                            if ($categorieParenteID === $categorieID) {
                                throw new BadRequestHttpException("Impossible de définir la catégorie $categorieParenteID comme parente de $categorieID (référence circulaire)");
                            }

                            if ($categorieParenteID > 0) {
                                $categorieParente = $this->categorieRepository->find($categorieParenteID);

                                if (!($categorieParente instanceof Categorie)) {
                                    throw new BadRequestHttpException("Catégorie $categorieID introuvable");
                                }
                            }
                        }

                        // Rang
                        if (isset($categorieArray['rang'])) {
                            $rang = '' !== $categorieArray['rang'] ? (int) $categorieArray['rang'] : null;
                        }

                        $variablesDéfinies = get_defined_vars();

                        if ($categorieID > 0) { // Édition
                            $categorie = $this->categorieRepository->find($categorieID);

                            if (!($categorie instanceof Categorie)) {
                                throw new BadRequestHttpException("Catégorie $categorieID introuvable");
                            }

                            if (array_key_exists('nom', $variablesDéfinies)) {
                                $categorie->setNom($nom);
                            }
                            if (array_key_exists('categorieParente', $variablesDéfinies)) {
                                $categorie->setCategorieParente($categorieParente->getId());
                            }
                            if (array_key_exists('rang', $variablesDéfinies)) {
                                $categorie->setRang($rang);
                            }
                        } else { // Création
                            if (
                                !array_key_exists('nom', $variablesDéfinies)
                                || !array_key_exists('categorieParente', $variablesDéfinies)
                                || !array_key_exists('rang', $variablesDéfinies)
                            ) {
                                throw new BadRequestHttpException("Les valeurs nécessaires à la création d'une catégorie ne sont pas toutes postées.");
                            }

                            $categorie = new Categorie(
                                null,
                                $nom,
                                $categorieParente->getId(),
                                [],
                                $rang
                            );
                        }

                        $this->categorieRepository->save($categorie);

                        // @todo : définir l'ID de la catégorie pour que $categorie->getId() le renvoie
                        // @todo : sans ça, la création d'une catégorie plante au moment d'ajouter des mots-clés

                        // Mots-clés
                        if (isset($categorieArray['keywords'])) {
                            $avant = $keywordsParCatégorie->has($categorieID) ?
                                $keywordsParCatégorie->get($categorieID)->toArray(
                                    static fn (Keyword $keyword): string => $keyword->getWord()
                                ) :
                                [];
                            $après = explode('|', $categorieArray['keywords']);

                            $noEmpty = static fn (string $word): bool => trim($word) !== '';
                            $supprimés = array_filter(
                                array_diff($avant, $après),
                                $noEmpty
                            );
                            $ajoutés = array_filter(
                                array_diff($après, $avant),
                                $noEmpty
                            );

                            // Ajoute les mots-clés sélectionnés
                            foreach ($ajoutés as $ajouté) {
                                // Ce mot-clé existe-il déjà ?
                                $keyword = $keywords->findFirst(
                                    static fn (Keyword $keyword): bool => $keyword->getWord() === $ajouté,
                                );

                                if (!($keyword instanceof Keyword)) { // Si non, on le crée
                                    $keyword = new Keyword(
                                        null,
                                        $ajouté,
                                        $categorie
                                    );
                                } else { // Si oui, on vérifie qu'il n'est pas déjà affecté à une autre catégorie
                                    $keywordCategorie = $keyword->getCategorie();
                                    $keywordCategorieID = $keywordCategorie->getId();

                                    if ($keywordCategorieID !== $categorieID) {
                                        throw new BadRequestHttpException("Le mot-clé \"$keyword\" ne peut pas être ajouté à la catégorie \"$categorie\" puisqu'il est déjà affecté à \"$keywordCategorie\".");
                                    }

                                    $keyword->setCategorie($categorie);
                                }

                                $this->keywordRepository->save($keyword);
                            }

                            // Supprime les mots-clés qui ne sont plus sélectionnés
                            foreach ($supprimés as $supprimé) {
                                $keyword = $keywords->findFirst(
                                    static fn (Keyword $keyword): bool => $keyword->getWord() === $supprimé,
                                );

                                if (!($keyword instanceof Keyword)) {
                                    continue;
                                }

                                // Pas besoin de vider la catégorie du mot-clé, il n'a plus de raison d'être donc on le supprime directement
                                $this->keywordRepository->delete($keyword->getId());
                            }
                        }

                        break;

                    case 'delete': // Suppression
                        if ($categorieID > 0) {
                            $mouvementsDeLaCatégorie = $this->mouvementRepository->findBy(
                                categoriesIds: Maybe::from([$categorieID]),
                                compteId: Maybe::nothing(),
                                dateStart: Maybe::nothing(),
                                dateEnd: Maybe::nothing(),
                                montant: Maybe::nothing(),
                            );

                            if (!$mouvementsDeLaCatégorie->isEmpty()) {
                                throw new BadRequestHttpException("La catégorie $categorieID ne peut pas être supprimée car elle est utilisée par {$mouvementsDeLaCatégorie->count()} mouvements.");
                            }

                            if ($keywordsParCatégorie->has($categorieID)) {
                                $this->keywordRepository->delete(
                                    ...$keywordsParCatégorie->get($categorieID)->toArray(
                                        static fn (Keyword $keyword): int => $keyword->getId()
                                    )
                                );
                            }

                            $this->categorieRepository->delete($categorieID);
                        }

                        break;
                }
            }
        }

        // URL de redirection
        $redirectURL = $request->get('redirect_url');

        if (is_string($redirectURL)) {
            return $this->redirect($redirectURL);
        }

        $categories = $this->categorieRepository->findAll();

        return $this->render(
            'Categorie/edit.html.twig',
            [
                'categories' => $categories->toArray(
                    static fn (int $categorieId): int => $categorieId,
                    static fn (Categorie $categorie): Categorie => $categorie
                ),
                'keywords' => $keywordsParCatégorie->toArray(
                    static fn (int $categorieId): int => $categorieId,
                    static fn (KeywordCollection $keywords): array => $keywords->toArray(
                        static fn (Keyword $keyword): string => $keyword->getWord()
                    )
                ),
            ]
        );
    }
}
