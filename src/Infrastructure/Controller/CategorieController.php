<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Domain\BalanceAnnuelle;
use App\Domain\BalanceMensuelle;
use App\Domain\Categorie\BalanceParCategorie;
use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieId;
use App\Domain\Categorie\CategorieIdCollection;
use App\Domain\Categorie\CategorieRepositoryInterface;
use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteRepositoryInterface;
use App\Domain\DataStructure\Maybe;
use App\Domain\Id\IdGeneratorInterface;
use App\Domain\Keyword\Keyword;
use App\Domain\Keyword\KeywordId;
use App\Domain\Keyword\KeywordRepositoryInterface;
use App\Domain\Mouvement\Balance;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Domain\Temps\Annee;
use App\Domain\Temps\Depuis;
use App\Domain\Temps\Periode;
use App\Infrastructure\ValueResolver\PeriodeParDefautAttribute;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class CategorieController extends AbstractController
{
    public function __construct(
        private readonly CategorieRepositoryInterface $categorieRepository,
        private readonly CompteRepositoryInterface $compteRepository,
        private readonly MouvementRepositoryInterface $mouvementRepository,
        private readonly KeywordRepositoryInterface $keywordRepository,
        private readonly IdGeneratorInterface $idGenerator,
    ) {
    }

    #[Route('/categories', name: 'categories_categories')]
    public function liste(
        ?Compte $compte,
        #[PeriodeParDefautAttribute(Depuis::UN_AN)]
        Periode $période,
    ): Response {
        $firstMouvement = $this->mouvementRepository->findFirstOne($compte?->id);
        $périodeÉtendue = $firstMouvement instanceof Mouvement ?
            new Periode($firstMouvement->date, $période->fin) :
            $période;

        // Toutes les catégories
        $categories = $this->categorieRepository->findAll();

        // Tous les comptes
        $comptes = $this->compteRepository->findAll();

        // Tous les mouvements sur la période donnée
        $mouvements = $this->mouvementRepository->findBy(
            maybeCategoriesIds: Maybe::nothing(), // toutes catégories confondues
            maybeCompteId: $compte instanceof Compte ? Maybe::from($compte->id) : Maybe::nothing(),
            maybeDateStart: Maybe::from($périodeÉtendue->début),
            maybeDateEnd: Maybe::from($périodeÉtendue->fin),
            maybeMontant: Maybe::nothing(),
        );
        $mouvementsSurLaPériode = $mouvements->filtrerParPériode($période);

        $balancePériodique = $mouvementsSurLaPériode->balance();
        $balancePériodiqueDesMouvementsCategorisés = Balance::nulle();
        $balanceAnnuelle = $mouvements->balanceAnnuelle($périodeÉtendue)->trierParDate();
        $balanceParCatégorie = [];

        /** @var Categorie $categorie */
        foreach ($categories as $categorie) {
            $mouvementsDeLaCatégorie = $mouvements->filtrerParCatégorie($categorie->id);
            $mouvementsDeLaCatégorieSurLaPériode = $mouvementsDeLaCatégorie->filtrerParPériode($période);

            $balancePériodiqueDeLaCatégorie = $mouvementsDeLaCatégorieSurLaPériode->balance();
            $balanceAnnuelleDeLaCatégorie = $mouvementsDeLaCatégorie->balanceAnnuelle($périodeÉtendue)->trierParDate();
            $balanceMensuelleMoyenneDeLaCatégorie = $mouvementsDeLaCatégorieSurLaPériode->balanceMensuelle($période)->moyenne();

            $balancePériodiqueDesMouvementsCategorisés = $balancePériodiqueDesMouvementsCategorisés->additionner($balancePériodiqueDeLaCatégorie);

            $clés = [(string) $categorie->id];
            if ($categorie->categorieParente instanceof CategorieId) {
                $clés[] = (string) $categorie->categorieParente;
            }

            // Double ajout dans la catégorie et dans la catégorie parente
            foreach ($clés as $clé) {
                $balanceParCatégorie[$clé] ??= [
                    'periodique' => Balance::nulle(),
                    'annuelle' => new BalanceAnnuelle(),
                    'mensuelle_moyenne' => Balance::nulle(),
                ];

                /** @var Balance $valeur */
                $valeur = $balanceParCatégorie[$clé]['periodique'];
                $balanceParCatégorie[$clé]['periodique'] = $valeur->additionner($balancePériodiqueDeLaCatégorie);

                /**
                 * @var Annee   $année
                 * @var Balance $balance
                 */
                foreach ($balanceAnnuelleDeLaCatégorie as $année => $balance) {
                    /** @var BalanceAnnuelle $valeur */
                    $valeur = $balanceParCatégorie[$clé]['annuelle'];
                    $balanceParCatégorie[$clé]['annuelle'] = $valeur->ajouter($année, $balance);
                }

                /** @var Balance $valeur */
                $valeur = $balanceParCatégorie[$clé]['mensuelle_moyenne'];
                $balanceParCatégorie[$clé]['mensuelle_moyenne'] = $valeur->additionner($balanceMensuelleMoyenneDeLaCatégorie);
            }
        }

        $balancePériodiqueDesMouvementsNonCatégorisés = $balancePériodique->soustraire($balancePériodiqueDesMouvementsCategorisés);

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
        ?Categorie $categorie,
        ?Compte $compte,
        #[PeriodeParDefautAttribute(Depuis::UN_AN)]
        Periode $période,
    ): Response {
        // Toutes les catégories
        $categories = $this->categorieRepository->findAll();

        // Tous les comptes
        $comptes = $this->compteRepository->findAll();

        // Les identifiants de la catégorie (mère + filles)
        $catégoriesFillesIds = $categorie instanceof Categorie ?
            $this->categorieRepository
                ->getCategoriesFillesRecursive($categorie->id)
                ->add($categorie->id) :
            null;

        // Tous les mouvements de la catégorie sur la période donnée
        $mouvements = $this->mouvementRepository->findBy(
            maybeCategoriesIds: Maybe::from($catégoriesFillesIds),
            maybeCompteId: $compte instanceof Compte ? Maybe::from($compte->id) : Maybe::nothing(),
            maybeDateStart: Maybe::from($période->début),
            maybeDateEnd: Maybe::from($période->fin),
            maybeMontant: Maybe::nothing(),
        );

        $balancePériodique = $mouvements->balance();
        $balancePériodiqueMensuelle = new BalanceMensuelle();
        $balancePériodiqueMensuelleMoyenne = Balance::nulle();
        $balancePériodiqueMensuelleContientUniquementDesDébits = true;
        $balancePériodiqueMensuelleContientUniquementDesCrédits = true;
        $balancePériodiqueParCatégorie = new BalanceParCategorie();

        if (!$mouvements->isEmpty()) {
            $balancePériodiqueMensuelle = $mouvements->balanceMensuelle($période)->trierParDate();

            /** @var Balance $balance */
            foreach ($balancePériodiqueMensuelle as $balance) {
                if ($balance->estPositive()) {
                    $balancePériodiqueMensuelleContientUniquementDesDébits = false;
                } elseif ($balance->estNégative()) {
                    $balancePériodiqueMensuelleContientUniquementDesCrédits = false;
                }
            }

            $balancePériodiqueMensuelleMoyenne = $balancePériodiqueMensuelle->moyenne();

            if ($categorie instanceof Categorie) {
                // La balance de la catégorie
                $balancePériodiqueParCatégorie = $balancePériodiqueParCatégorie->add($categorie->id, $balancePériodique);

                // La balance des catégories filles
                /** @var CategorieId $categorieFilleId */
                foreach ($categorie->categoriesFilles as $categorieFilleId) {
                    $balancePériodiqueParCatégorie = $balancePériodiqueParCatégorie->add(
                        $categorieFilleId,
                        $mouvements
                            ->filtrerParCatégorie($categorieFilleId)
                            ->balance()
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
                'balance_periodique_mensuelle_contient_uniquement_des_credits' => $balancePériodiqueMensuelleContientUniquementDesCrédits,
                'balance_periodique_mensuelle_moyenne' => $balancePériodiqueMensuelleMoyenne,
                'balance_periodique_par_categorie' => $balancePériodiqueParCatégorie,
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
                            $motsAvant = $keywordsParCatégorieId->has($categorieId) ?
                                $keywordsParCatégorieId->get($categorieId)->toArray(
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
                                maybeCategoriesIds: Maybe::from(new CategorieIdCollection()->add($categorieId)),
                                maybeCompteId: Maybe::nothing(),
                                maybeDateStart: Maybe::nothing(),
                                maybeDateEnd: Maybe::nothing(),
                                maybeMontant: Maybe::nothing(),
                            );

                            if (!$mouvementsDeLaCatégorie->isEmpty()) {
                                throw new BadRequestHttpException("La catégorie $categorieId ne peut pas être supprimée car elle est utilisée par {$mouvementsDeLaCatégorie->count()} mouvements.");
                            }

                            if ($keywordsParCatégorieId->has($categorieId)) {
                                $this->keywordRepository->delete(
                                    ...$keywordsParCatégorieId->get($categorieId)->toArray(
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
}
