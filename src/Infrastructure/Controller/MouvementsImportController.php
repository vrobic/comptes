<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Import\ImportHandlerInterface;
use App\Application\Import\MouvementsImportHandlerInterface;
use App\Domain\Categorie\Categorie;
use App\Domain\Compte\Compte;
use App\Domain\Mouvement\Mouvement;
use App\Infrastructure\Configuration\ConfigurationLoader;
use App\Infrastructure\Repository\CategorieRepository;
use App\Infrastructure\Repository\CompteRepository;
use App\Infrastructure\Repository\MouvementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur du centre d'import.
 *
 * @todo Faire une fonction pour chaque action :
 *       - mouvementsParseAction
 *       - mouvementsImportAction
 *       - pleinsParseAction
 *       - pleinsImportAction
 * @todo Utiliser un service pour mutualiser le reste.
 */
class MouvementsImportController extends AbstractController
{
    /**
     * Configuration des handlers d'import.
     */
    private array $handlers;

    /**
     * Identifiant du service d'import, au sein de $this->handlers.
     *
     * @todo : à supprimer car pas très utile, on peut utiliser directement l'objet ImportHandlerInterface
     */
    private string $handlerIdentifier;

    /** @param ImportHandlerInterface[] $importHandlers */
    public function __construct(
        private readonly iterable $importHandlers,
        private readonly CompteRepository $compteRepository,
        private readonly CategorieRepository $categorieRepository,
        private readonly MouvementRepository $mouvementRepository,
        ConfigurationLoader $configurationLoader,
    ) {
        $this->handlers = $configurationLoader()['import']['handlers']['mouvements'];
    }

    /**
     * Contrôleur d'import des mouvements. Se déroule en trois étapes :
     *      - parsing du fichier uploadé
     *      - affichage d'un formulaire permettant d'ajuster les données
     *      - validation et import
     *
     * @todo Comme pour MouvementController->edit(), utiliser un formulaire Symfony.
     *
     * @throws \Exception en cas d'erreur d'import du fichier
     */
    #[Route('/import/mouvements', name: 'import_mouvements')]
    public function __invoke(Request $request): Response
    {
        // La session
        $session = $request->getSession();

        // Tous les comptes bancaires
        $comptes = $this->compteRepository->findAll();

        // Toutes les catégories de mouvements
        $categories = $this->categorieRepository->findAll();

        // Le dernier mouvement inséré
        $latestMouvement = $this->mouvementRepository->findLatestOne();

        // Classification des mouvements
        $categorizedMouvements = [];
        $uncategorizedMouvements = [];
        $ambiguousMouvements = [];
        $waitingMouvements = [];

        // Action : parsing ou import
        $action = $request->get('action');

        switch ($action) {
            case 'parse': // Parsing du fichier uploadé
                /** @var MouvementsImportHandlerInterface $handler */
                $handler = $this->getHandler($request);
                $splFile = $this->getFile($request);
                $handler->parse($splFile);

                // Mouvements classifiés
                $mouvements = $handler->getMouvements();
                $categorizedMouvements = $handler->getCategorizedMouvements();
                $uncategorizedMouvements = $handler->getUncategorizedMouvements();
                $ambiguousMouvements = $handler->getAmbiguousMouvements();
                $waitingMouvements = $handler->getWaitingMouvements();

                // Indique si on doit ignorer les mouvements anciens
                $skipOldOnes = $request->get('skipOldOnes', false);

                /* Passage des mouvements en session.
                 * Ils sont identifiés par leur hash car leur id ne sera disponible
                 * qu'une fois qu'ils auront été persistés. */
                foreach ($mouvements as $hash => $mouvement) {
                    /* Si on doit ignorer les mouvements anciens,
                     * alors on n'importe le mouvement que s'il est plus récent que le dernier présent en base. */
                    if (false !== $skipOldOnes && $latestMouvement instanceof Mouvement) {
                        $date = $mouvement->getDate();
                        $latestMouvementDate = $latestMouvement->getDate();

                        if ($date < $latestMouvementDate) {
                            unset($mouvements[$hash]);
                            unset($categorizedMouvements[$hash]);
                            unset($uncategorizedMouvements[$hash]);
                            unset($ambiguousMouvements[$hash]);
                            unset($waitingMouvements[$hash]);

                            continue;
                        }
                    }
                }

                $session->set('mouvements', $mouvements);

                break;

            case 'import': // Import des mouvements après ajustements manuels
                // Hash des mouvements à importer
                $mouvementsHashToImport = $request->get('mouvements_hash_to_import', []);

                // Données de mouvements à modifier
                $mouvementsData = $request->get('mouvements', []);

                /**
                 * Récupération des mouvements.
                 *
                 * @var Mouvement[] $mouvements
                 */
                $mouvements = $session->get('mouvements');

                foreach ($mouvements as $mouvement) {
                    // Identification du mouvement par son hash
                    $hash = $mouvement->getHash();

                    if (!in_array($hash, $mouvementsHashToImport)) {
                        continue;
                    }

                    // Modification éventuelle de la date
                    if (isset($mouvementsData[$hash]['date'])) {
                        $dateString = $mouvementsData[$hash]['date'];
                        $date = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateString 00:00:00");
                        if (!($date instanceof \DateTime)) {
                            throw new BadRequestHttpException("Date du mouvement invalide : $dateString");
                        }
                        $mouvement->setDate($date);
                    }

                    // Modification éventuelle de la catégorie
                    if (isset($mouvementsData[$hash]['categorie'])) {
                        $categorieID = (int) $mouvementsData[$hash]['categorie'];

                        if ($categorieID > 0) {
                            $categorie = $this->categorieRepository->find($categorieID);

                            if (!($categorie instanceof Categorie)) {
                                // @todo
                            }
                        } else {
                            $categorie = null;
                        }

                        $mouvement->setCategorie($categorie);
                    }

                    // Modification éventuelle du compte
                    if (isset($mouvementsData[$hash]['compte'])) {
                        $compteID = (int) $mouvementsData[$hash]['compte'];

                        if ($compteID > 0) {
                            $compte = $this->compteRepository->find($compteID);

                            if (!($compte instanceof Compte)) {
                                // @todo
                            }
                        }

                        $mouvement->setCompte($compte);
                    }

                    // Modification éventuelle du montant
                    if (isset($mouvementsData[$hash]['montant'])) {
                        $montant = (float) $mouvementsData[$hash]['montant'];
                        $mouvement->setMontant($montant);
                    }

                    // Modification éventuelle de la description
                    if (isset($mouvementsData[$hash]['description'])) {
                        $description = $mouvementsData[$hash]['description'];
                        $mouvement->setDescription($description);
                    }

                    // Persistance des données
                    $this->mouvementRepository->save($mouvement); // @todo : sortir de la boucle
                }

                // Vidage de la session
                $session->remove('mouvements');

                break;
        }

        return $this->render(
            'Import/mouvements.html.twig',
            [
                'handlers' => $this->handlers,
                'comptes' => $comptes,
                'categories' => $categories->toArray(
                    static fn (int $categorieId): int => $categorieId,
                    static fn (Categorie $categorie): Categorie => $categorie
                ),
                'categorized_mouvements' => $categorizedMouvements,
                'uncategorized_mouvements' => $uncategorizedMouvements,
                'ambiguous_mouvements' => $ambiguousMouvements,
                'waiting_mouvements' => $waitingMouvements,
            ]
        );
    }

    /**
     * Renvoie une instance du handler d'import.
     *
     * @throws \Exception si le handler demandé est invalide
     */
    private function getHandler(Request $request): ImportHandlerInterface
    {
        $handlerIdentifier = $request->get('handlerIdentifier');

        if (!is_string($handlerIdentifier)) {
            throw new \Exception('Handler manquant.');
        }

        if (!in_array($handlerIdentifier, array_keys($this->handlers))) {
            throw new \Exception(sprintf("Le handler [%s] n'est pas configuré.", $handlerIdentifier));
        }

        $this->handlerIdentifier = $handlerIdentifier;

        foreach ($this->importHandlers as $importHandler) {
            if ($importHandler->supports($handlerIdentifier)) {
                return $importHandler;
            }
        }

        throw new \Exception(sprintf("Le handler [%s] n'est pas implémenté.", $handlerIdentifier));
    }

    /**
     * Renvoie le fichier uploadé.
     *
     * @throws BadRequestHttpException           en cas d'erreur d'accès au fichier
     * @throws UnsupportedMediaTypeHttpException si le type de fichier n'est pas celui attendu
     */
    private function getFile(Request $request): \SplFileObject
    {
        /** @var ?UploadedFile $file */
        $file = $request->files->get('file');

        if (!($file instanceof UploadedFile)) {
            throw new BadRequestHttpException('Fichier manquant.');
        }
        if (!$file->isValid()) {
            throw new BadRequestHttpException("Échec de l'upload du fichier.");
        }

        $fileExtension = $file->getClientOriginalExtension();

        // Handlers disponibles
        if ($fileExtension !== $this->handlers[$this->handlerIdentifier]['extension']) {
            throw new UnsupportedMediaTypeHttpException("Le handler $this->handlerIdentifier ne supporte pas le type de fichier $fileExtension.");
        }

        $splFile = $file->openFile();

        return $splFile;
    }
}
