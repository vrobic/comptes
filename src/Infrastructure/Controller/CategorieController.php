<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\StatsProvider;
use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieId;
use App\Domain\Categorie\CategorieIdCollection;
use App\Domain\Categorie\CategorieRepositoryInterface;
use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteId;
use App\Domain\Compte\CompteRepositoryInterface;
use App\Domain\DataStructure\Maybe;
use App\Domain\Id\IdGeneratorInterface;
use App\Domain\Keyword\Keyword;
use App\Domain\Keyword\KeywordId;
use App\Domain\Keyword\KeywordRepositoryInterface;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Domain\Temps\Periode;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CategorieController extends AbstractController
{
    public function __construct(
        private readonly CategorieRepositoryInterface $categorieRepository,
        private readonly CompteRepositoryInterface $compteRepository,
        private readonly MouvementRepositoryInterface $mouvementRepository,
        private readonly KeywordRepositoryInterface $keywordRepository,
        private readonly StatsProvider $statsProvider,
        private readonly IdGeneratorInterface $idGenerator,
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
        $compte = $this->getCompte($request);

        // Filtre sur la période
        $période = $this->getPériode($request);

        // Années de début et de fin pour les classements par années
        $firstMouvement = $this->mouvementRepository->findFirstOne($compte?->id);
        $yearStart = (int) ($firstMouvement instanceof Mouvement ? $firstMouvement->date : $période->début)->format('Y');
        $yearEnd = (int) $période->fin->format('Y');

        $balancePériodique = $this->mouvementRepository->balancePériodique($période, $compte?->id);
        $balancePériodiqueDesMouvementsCategorisés = 0;
        $balanceAnnuelle = $this->statsProvider->balanceAnnuelle(
            $yearStart,
            $yearEnd,
            Maybe::nothing(), // toutes catégories confondues
            $compte instanceof Compte ? Maybe::from($compte) : Maybe::nothing(),
        );
        $balanceParCatégorie = [];

        /** @var Categorie $categorie */
        foreach ($categories as $categorie) {
            $categorieId = $categorie->id;

            $balancePériodiqueDeLaCatégorie = $this->categorieRepository->balancePériodique($categorieId, $période, $compte?->id);

            $balanceAnnuelleDeLaCatégorie = $this->statsProvider->balanceAnnuelle(
                $yearStart,
                $yearEnd,
                Maybe::from($categorie),
                $compte instanceof Compte ? Maybe::from($compte) : Maybe::nothing(),
            );

            $balanceMensuelleMoyenneDeLaCatégorie = $this->statsProvider->balanceMensuelleMoyenne(
                $période,
                Maybe::from($categorie),
                $compte instanceof Compte ? Maybe::from($compte) : Maybe::nothing(),
            );

            // Si la catégorie est de premier niveau, on la prend en compte dans le calcul de la balance des mouvements catégorisés
            if (null === $categorie->categorieParente) {
                $balancePériodiqueDesMouvementsCategorisés += $balancePériodiqueDeLaCatégorie;
            }

            $balanceParCatégorie[(string) $categorieId] = [
                'periodique' => $balancePériodiqueDeLaCatégorie,
                'annuelle' => $balanceAnnuelleDeLaCatégorie,
                'mensuelle_moyenne' => $balanceMensuelleMoyenneDeLaCatégorie,
            ];
        }

        $balancePériodiqueDesMouvementsNonCatégorisés = $balancePériodique - $balancePériodiqueDesMouvementsCategorisés;

        return $this->render(
            'Categorie/index.html.twig',
            [
                'categories' => $categories->toAssociativeArray(),
                'comptes' => $comptes,
                'compte_filter' => $compte,
                'date_filter' => [
                    'start' => $période->début,
                    'end' => $période->fin,
                ],
                'balance_periodique' => $balancePériodique,
                'balance_periodique_mouvements_non_categorises' => $balancePériodiqueDesMouvementsNonCatégorisés,
                'balance_annuelle' => $balanceAnnuelle,
                'balance_par_categorie' => $balanceParCatégorie,
            ]
        );
    }

    #[Route('/categorie/{categorieId}', name: 'categories_categorie')]
    public function détail(
        Request $request,
        string $categorieId, // @todo : utiliser un param converter
    ): Response {
        $categorieId = CategorieId::estValide($categorieId) ?
            new CategorieId($categorieId) :
            null;

        if ($categorieId instanceof CategorieId) {
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
        $compte = $this->getCompte($request);

        // Filtre sur la période
        $période = $this->getPériode($request);

        // Tous les mouvements de la catégorie sur la période donnée
        $mouvements = $this->mouvementRepository->findBy(
            categoriesIds: Maybe::from(
                $categorie instanceof Categorie ?
                    $this->categorieRepository
                        ->getCategoriesFillesRecursive($categorie->id)
                        ->add($categorie->id) :
                    null
            ),
            compteId: $compte instanceof Compte ? Maybe::from($compte->id) : Maybe::nothing(),
            dateStart: Maybe::from($période->début),
            dateEnd: Maybe::from($période->fin),
            montant: Maybe::nothing(),
        );

        $balancePériodique = $mouvements->balance($période);
        $balancePériodiqueMensuelle = [];
        $balancePériodiqueMensuelleMoyenne = 0;
        $balancePériodiqueMensuelleContientUniquementDesDébits = true;
        $balancePériodiqueMensuelleContentUniquementDesCrédits = true;
        $balancePéridioqueParCatégorie = [];

        if (!$mouvements->isEmpty()) {
            // @todo : déduire ça de la collection de mouvements
            $balancePériodiqueMensuelle = $this->statsProvider->balanceMensuelle(
                $période,
                Maybe::from($categorie),
                $compte instanceof Compte ? Maybe::from($compte) : Maybe::nothing(),
            );

            foreach ($balancePériodiqueMensuelle as $months) {
                foreach ($months as $montant) {
                    if ($montant > 0) {
                        $balancePériodiqueMensuelleContientUniquementDesDébits = false;
                    } elseif ($montant < 0) {
                        $balancePériodiqueMensuelleContentUniquementDesCrédits = false;
                    }
                }
            }

            // @todo : déduire ça de la collection de mouvements
            $balancePériodiqueMensuelleMoyenne = $this->statsProvider->balanceMensuelleMoyenne(
                $période,
                Maybe::from($categorie),
                $compte instanceof Compte ? Maybe::from($compte) : Maybe::nothing(),
            );

            if ($categorie instanceof Categorie) {
                // La balance de la catégorie
                $balancePéridioqueParCatégorie[(string) $categorie->id] = $balancePériodique;

                // La balance des catégories filles
                /** @var CategorieId $categorieFilleId */
                foreach ($categorie->categoriesFilles as $categorieFilleId) {
                    $balancePéridioqueParCatégorie[(string) $categorieFilleId] = $this->categorieRepository->balancePériodique(
                        categorieId: $categorieFilleId,
                        période: $période,
                        compteId: $compte?->id,
                    );
                }
            }
        }

        return $this->render(
            'Categorie/show.html.twig',
            [
                'categorie' => $categorie,
                'categories' => $categories->toAssociativeArray(),
                'comptes' => $comptes,
                'compte_filter' => $compte,
                'date_filter' => [
                    'start' => $période->début,
                    'end' => $période->fin,
                ],
                'mouvements' => $mouvements,
                'balance_periodique' => $balancePériodique,
                'balance_periodique_mensuelle' => $balancePériodiqueMensuelle,
                'balance_periodique_mensuelle_contient_uniquement_des_debits' => $balancePériodiqueMensuelleContientUniquementDesDébits,
                'balance_periodique_mensuelle_contient_uniquement_des_credits' => $balancePériodiqueMensuelleContentUniquementDesCrédits,
                'balance_periodique_mensuelle_moyenne' => $balancePériodiqueMensuelleMoyenne,
                'balance_periodique_par_categorie' => $balancePéridioqueParCatégorie,
            ]
        );
    }

    #[Route('/categories/edit', name: 'categories_edit')]
    public function edit(Request $request): Response
    {
        $keywords = $this->keywordRepository->findAll();
        $keywordsParCatégorieId = $keywords->trierParCatégorie();

        // Valeurs postées
        $action = $request->get('action');
        $batchArray = $request->get('batch', []);
        $categoriesArray = $request->get('categories', []);

        /** @var string $categorieId */
        foreach ($batchArray as $categorieId) {
            if (isset($categoriesArray[$categorieId])) {
                $categorieArray = $categoriesArray[$categorieId];

                $categorieId = CategorieId::estValide($categorieId) ?
                    new CategorieId($categorieId) :
                    null;

                switch ($action) {
                    case 'save': // Création et édition
                        // Nom
                        if (isset($categorieArray['nom'])) {
                            $nom = $categorieArray['nom'];
                        }

                        // Catégorie parente
                        if (isset($categorieArray['categorieParente'])) {
                            $categorieParenteId = CategorieId::estValide($categorieArray['categorieParente']) ?
                                new CategorieId((string) $categorieArray['categorieParente']) :
                                null;

                            if (
                                $categorieParenteId instanceof CategorieId
                                && $categorieId instanceof CategorieId
                                && $categorieParenteId->estÉgalÀ($categorieId)
                            ) {
                                throw new BadRequestHttpException("Impossible de définir la catégorie $categorieParenteId comme parente de $categorieId (référence circulaire)");
                            }

                            if ($categorieParenteId instanceof CategorieId) {
                                $categorieParente = $this->categorieRepository->find($categorieParenteId);

                                if (!($categorieParente instanceof Categorie)) {
                                    throw new BadRequestHttpException("Catégorie $categorieId introuvable");
                                }
                            } else {
                                $categorieParente = null;
                            }
                        }

                        // Rang
                        $rang = isset($categorieArray['rang']) && '' !== $categorieArray['rang'] ? (int) $categorieArray['rang'] : null;

                        $variablesDéfinies = get_defined_vars();

                        if ($categorieId instanceof CategorieId) { // Édition
                            $categorie = $this->categorieRepository->find($categorieId);

                            if (!($categorie instanceof Categorie)) {
                                throw new BadRequestHttpException("Catégorie $categorieId introuvable");
                            }

                            if (array_key_exists('nom', $variablesDéfinies)) {
                                $categorie->nom = $nom;
                            }
                            if (array_key_exists('categorieParente', $variablesDéfinies)) {
                                $categorie->categorieParente = $categorieParente->id;
                            }
                            if (array_key_exists('rang', $variablesDéfinies)) {
                                $categorie->rang = $rang;
                            }
                        } else { // Création
                            if (
                                !array_key_exists('nom', $variablesDéfinies)
                                || !array_key_exists('categorieParente', $variablesDéfinies)
                                || !array_key_exists('rang', $variablesDéfinies)
                            ) {
                                throw new BadRequestHttpException("Les valeurs nécessaires à la création d'une catégorie ne sont pas toutes postées.");
                            }

                            $categorieId = new CategorieId((string) $this->idGenerator->générer());

                            $categorie = new Categorie(
                                $categorieId,
                                $nom,
                                $categorieParente?->id,
                                new CategorieIdCollection(),
                                $rang
                            );
                        }

                        $this->categorieRepository->save($categorie);

                        // Mots-clés
                        if (isset($categorieArray['keywords'])) {
                            /** @var string[] $motsAvant */
                            $motsAvant = $keywordsParCatégorieId->has((string) $categorieId) ?
                                $keywordsParCatégorieId->get((string) $categorieId)->toArray(
                                    static fn (Keyword $keyword): string => $keyword->word
                                ) :
                                [];
                            $motsAprès = explode('|', $categorieArray['keywords']);

                            $noEmpty = static fn (string $word): bool => '' !== trim($word);
                            /** @var string[] $motsSupprimés */
                            $motsSupprimés = array_filter(
                                array_diff($motsAvant, $motsAprès),
                                $noEmpty
                            );
                            /** @var string[] $motsAjoutés */
                            $motsAjoutés = array_filter(
                                array_diff($motsAprès, $motsAvant),
                                $noEmpty
                            );

                            // Ajoute les mots-clés sélectionnés
                            foreach ($motsAjoutés as $motAjouté) {
                                // Ce mot-clé existe-il déjà ?
                                $keyword = $keywords->findFirst(
                                    static fn (Keyword $keyword): bool => $keyword->word === $motAjouté,
                                );

                                if (!($keyword instanceof Keyword)) { // Si non, on le crée
                                    $keyword = new Keyword(
                                        new KeywordId((string) $this->idGenerator->générer()),
                                        $motAjouté,
                                        $categorie
                                    );
                                } else { // Si oui, on vérifie qu'il n'est pas déjà affecté à une autre catégorie
                                    $keywordCategorie = $keyword->categorie;
                                    $keywordCategorieId = $keywordCategorie->id;

                                    if (!$keywordCategorieId->estÉgalÀ($categorieId)) {
                                        throw new BadRequestHttpException("Le mot-clé \"$keyword\" ne peut pas être ajouté à la catégorie \"$categorie\" puisqu'il est déjà affecté à \"$keywordCategorie\".");
                                    }

                                    $keyword->categorie = $categorie;
                                }

                                $this->keywordRepository->save($keyword);
                            }

                            // Supprime les mots-clés qui ne sont plus sélectionnés
                            foreach ($motsSupprimés as $motSupprimé) {
                                $keyword = $keywords->findFirst(
                                    static fn (Keyword $keyword): bool => $keyword->word === $motSupprimé,
                                );

                                if (!($keyword instanceof Keyword)) {
                                    continue;
                                }

                                // Pas besoin de vider la catégorie du mot-clé, il n'a plus de raison d'être donc on le supprime directement
                                $this->keywordRepository->delete($keyword->id);
                            }
                        }

                        break;

                    case 'delete': // Suppression
                        if ($categorieId instanceof CategorieId) {
                            $mouvementsDeLaCatégorie = $this->mouvementRepository->findBy(
                                categoriesIds: Maybe::from(new CategorieIdCollection()->add($categorieId)),
                                compteId: Maybe::nothing(),
                                dateStart: Maybe::nothing(),
                                dateEnd: Maybe::nothing(),
                                montant: Maybe::nothing(),
                            );

                            if (!$mouvementsDeLaCatégorie->isEmpty()) {
                                throw new BadRequestHttpException("La catégorie $categorieId ne peut pas être supprimée car elle est utilisée par {$mouvementsDeLaCatégorie->count()} mouvements.");
                            }

                            if ($keywordsParCatégorieId->has((string) $categorieId)) {
                                $this->keywordRepository->delete(
                                    ...$keywordsParCatégorieId->get((string) $categorieId)->toArray(
                                        static fn (Keyword $keyword): KeywordId => $keyword->id
                                    )
                                );
                            }

                            $this->categorieRepository->delete($categorieId);
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
                'categories' => $categories->toAssociativeArray(),
                'keywords' => $keywordsParCatégorieId->toAssociativeArray(),
            ]
        );
    }

    // @todo : utiliser un param converter
    private function getCompte(Request $request): ?Compte
    {
        if ($request->get('compte_id')) {
            if (CompteId::estValide((string) $request->get('compte_id'))) {
                $compteId = new CompteId((string) $request->get('compte_id'));
                $compte = $this->compteRepository->find($compteId);

                if (!($compte instanceof Compte)) {
                    throw new NotFoundHttpException("Le compte bancaire $compteId n'existe pas.");
                }

                return $compte;
            }
        }

        return null;
    }

    // @todo : utiliser un param converter
    private function getPériode(Request $request): Periode
    {
        if ($request->get('date_filter')) {
            $dateFilterString = $request->get('date_filter');
            $dateStartString = $dateFilterString['start'];
            $dateEndString = $dateFilterString['end'];

            $dateStart = \DateTimeImmutable::createFromFormat('d-m-Y H:i:s', "$dateStartString 00:00:00");
            $dateEnd = \DateTimeImmutable::createFromFormat('d-m-Y H:i:s', "$dateEndString 23:59:59");
        } else { // Par défaut, depuis un an et jusqu'à la fin du mois
            list($year, $month, $lastDayOfMonth) = explode('-', date('Y-n-t'));

            $month = (int) $month;
            $year = (int) $year;
            $lastDayOfMonth = (int) $lastDayOfMonth;

            $dateStart = \DateTimeImmutable::createFromFormat('Y-n-j H:i:s', "$year-$month-1 00:00:00");
            if ($dateStart instanceof \DateTimeImmutable) {
                $dateStart = $dateStart->modify('-1 year')->setTime(0, 0); // Depuis un an
            }
            $dateEnd = \DateTimeImmutable::createFromFormat('Y-n-j H:i:s', "$year-$month-$lastDayOfMonth 23:59:59");
        }

        if (!($dateStart instanceof \DateTimeImmutable) || !($dateEnd instanceof \DateTimeImmutable) || $dateStart > $dateEnd) {
            throw new BadRequestHttpException('La période de dates est invalide.');
        }

        return new Periode($dateStart, $dateEnd);
    }
}
