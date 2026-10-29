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
use App\Domain\Keyword\KeywordCollection;
use App\Domain\Keyword\KeywordId;
use App\Domain\Keyword\KeywordRepositoryInterface;
use App\Domain\Keyword\KeywordsParCategorieIdMap;
use App\Domain\Mouvement\Balance;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Domain\Temps\Annee;
use App\Domain\Temps\Depuis;
use App\Domain\Temps\Periode;
use App\Infrastructure\ValueResolver\PeriodeParDefautAttribute;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
        private readonly Connection $connection,
    ) {
    }

    #[Route('/categories', name: 'categories_categories', methods: ['GET'])]
    public function liste(
        ?Compte $compte,
        #[PeriodeParDefautAttribute(Depuis::UN_AN)]
        Periode $période,
    ): Response {
        // Depuis le premier mouvement, pour l'évolution annuelle, sauf si la période lui est antérieure
        $firstMouvement = $this->mouvementRepository->findFirstOne($compte?->id);
        $périodeÉtendue = $firstMouvement instanceof Mouvement && $firstMouvement->date <= $période->fin ?
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

    #[Route('/categorie/{categorieId}', name: 'categories_categorie', methods: ['GET'])]
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

    #[Route('/categories/edit', name: 'categories_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request): Response
    {
        if ($request->isMethod('POST') && !$this->isCsrfTokenValid('categories_edit', $request->request->getString('_token'))) {
            throw new AccessDeniedHttpException('Jeton CSRF invalide.');
        }

        $keywords = $this->keywordRepository->findAll();
        $keywordsParCatégorieId = $keywords->trierParCatégorie();

        // Les modifications d'un envoi forment un bloc : si l'une est refusée, aucune n'est enregistrée
        if ($request->isMethod('POST')) {
            $this->connection->transactional(fn () => $this->enregistrer($request, $keywords, $keywordsParCatégorieId));
        }

        if ($request->request->has('redirect_route')) {
            return $this->redirectToRoute(
                $request->request->getString('redirect_route'),
                $request->request->all('redirect_params')
            );
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

    /**
     * Enregistre ou supprime les catégories cochées, selon l'action postée.
     */
    private function enregistrer(
        Request $request,
        KeywordCollection $keywords,
        KeywordsParCategorieIdMap $keywordsParCatégorieId,
    ): void {
        // Valeurs postées
        $action = $request->request->get('action');
        $batchArray = $request->request->all('batch');
        $categoriesArray = $request->request->all('categories');

        // Les mots-clés que cet envoi ajoute à une catégorie, enregistrés une fois tout l'envoi lu
        $keywordsAjoutés = new KeywordCollection();
        // Les mots-clés que cet envoi retire de leur catégorie : chacun peut être repris par une autre catégorie
        $keywordsRetirés = new KeywordCollection();

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
                                throw new BadRequestHttpException(sprintf('Impossible de définir la catégorie [%s] comme parente de [%s] (référence circulaire).', $categorieParenteId, $categorieId));
                            }

                            if ($categorieParenteId instanceof CategorieId) {
                                $categorieParente = $this->categorieRepository->find($categorieParenteId);

                                if (!$categorieParente instanceof Categorie) {
                                    throw new BadRequestHttpException(sprintf('Catégorie [%s] introuvable.', $categorieParenteId));
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

                            if (!$categorie instanceof Categorie) {
                                throw new BadRequestHttpException(sprintf('Catégorie [%s] introuvable.', $categorieId));
                            }

                            if (array_key_exists('nom', $variablesDéfinies)) {
                                $categorie->nom = $nom;
                            }
                            if (array_key_exists('categorieParente', $variablesDéfinies)) {
                                $categorie->categorieParente = $categorieParente?->id;
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
                                // Un même mot ne peut être ajouté qu'à une seule catégorie par envoi
                                $déjàAjouté = $keywordsAjoutés->findFirst(
                                    static fn (Keyword $keyword): bool => $keyword->word === $motAjouté,
                                );

                                if ($déjàAjouté instanceof Keyword) {
                                    throw new BadRequestHttpException(sprintf("Le mot-clé [%s] ne peut pas être ajouté à la catégorie [%s] puisqu'il l'est déjà à [%s].", $motAjouté, $categorie, $déjàAjouté->categorie));
                                }

                                // Un mot qui existe déjà garde son mot-clé, et donc son identifiant
                                $keyword = $keywords->findFirst(
                                    static fn (Keyword $keyword): bool => $keyword->word === $motAjouté,
                                );

                                $keywordsAjoutés = $keywordsAjoutés->add(new Keyword(
                                    $keyword instanceof Keyword ? $keyword->id : new KeywordId((string) $this->idGenerator->générer()),
                                    $motAjouté,
                                    $categorie,
                                ));
                            }

                            foreach ($motsSupprimés as $motSupprimé) {
                                $keyword = $keywords->findFirst(
                                    static fn (Keyword $keyword): bool => $keyword->word === $motSupprimé,
                                );

                                if ($keyword instanceof Keyword) {
                                    $keywordsRetirés = $keywordsRetirés->add($keyword);
                                }
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
                                throw new BadRequestHttpException(sprintf('La catégorie [%s] ne peut pas être supprimée car elle est utilisée par [%d] mouvements.', $categorieId, $mouvementsDeLaCatégorie->count()));
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

        // Ajoute les mots-clés sélectionnés
        /** @var Keyword $keywordAjouté */
        foreach ($keywordsAjoutés as $keywordAjouté) {
            // Ce mot-clé existe-il déjà ?
            $keyword = $keywords->findFirst(
                static fn (Keyword $keyword): bool => $keyword->word === $keywordAjouté->word,
            );

            // Si oui, on vérifie qu'il n'est pas déjà affecté à une autre catégorie, sauf si cet envoi l'en retire
            if ($keyword instanceof Keyword && !$keyword->categorie->id->estÉgalÀ($keywordAjouté->categorie->id)) {
                if (!$keywordsRetirés->contains($keyword)) {
                    throw new BadRequestHttpException(sprintf("Le mot-clé [%s] ne peut pas être ajouté à la catégorie [%s] puisqu'il est déjà affecté à [%s].", $keyword, $keywordAjouté->categorie, $keyword->categorie));
                }

                // Repris par une autre catégorie, le mot-clé n'est plus à supprimer
                $keywordsRetirés = $keywordsRetirés->remove($keyword);
            }

            $this->keywordRepository->save($keywordAjouté);
        }

        // Supprime les mots-clés retirés qu'aucune catégorie n'a repris : ils n'ont plus de raison d'être
        if (!$keywordsRetirés->isEmpty()) {
            $this->keywordRepository->delete(
                ...$keywordsRetirés->toArray(
                    static fn (Keyword $keyword): KeywordId => $keyword->id
                )
            );
        }
    }
}
