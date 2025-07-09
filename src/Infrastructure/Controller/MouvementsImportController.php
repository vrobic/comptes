<?php

declare(strict_types=1);

namespace App\Infrastructure\Controller;

use App\Application\Import\MouvementsImportHandlerInterface;
use App\Domain\Categorie\Categorie;
use App\Domain\Categorie\CategorieId;
use App\Domain\Categorie\CategorieRepositoryInterface;
use App\Domain\Categorie\Classification;
use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteId;
use App\Domain\Compte\CompteRepositoryInterface;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementRepositoryInterface;
use App\Domain\Mouvement\MouvementsParHash;
use App\Infrastructure\Configuration\ConfigurationLoader;
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
     * @todo : à supprimer car pas très utile, on peut utiliser directement l'objet MouvementsImportHandlerInterface
     */
    private string $handlerIdentifier;

    /** @param MouvementsImportHandlerInterface[] $mouvementsImportHandlers */
    public function __construct(
        private readonly iterable $mouvementsImportHandlers,
        private readonly CompteRepositoryInterface $compteRepository,
        private readonly CategorieRepositoryInterface $categorieRepository,
        private readonly MouvementRepositoryInterface $mouvementRepository,
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
        // Classification des mouvements
        $categorizedMouvements = new MouvementsParHash();
        $uncategorizedMouvements = new MouvementsParHash();
        $ambiguousMouvements = new MouvementsParHash();
        $waitingMouvements = new MouvementsParHash();

        switch ($request->get('action')) {
            case null:
                return $this->render(
                    'Import/mouvements.html.twig',
                    [
                        'handlers' => $this->handlers,
                        'categorized_mouvements' => $categorizedMouvements,
                        'uncategorized_mouvements' => $uncategorizedMouvements,
                        'ambiguous_mouvements' => $ambiguousMouvements,
                        'waiting_mouvements' => $waitingMouvements,
                    ]
                );

            case 'parse': // Parsing du fichier uploadé
                /** @var MouvementsImportHandlerInterface $handler */
                $handler = $this->getHandler($request);
                $splFile = $this->getFile($request);
                $handler->parse($splFile);

                $mouvementsParHashParClassification = $handler->mouvementsParHashParClassification;

                $mouvements = $mouvementsParHashParClassification->getMouvementsParHash();

                /** @var MouvementsParHash $categorizedMouvements */
                $categorizedMouvements = $mouvementsParHashParClassification->has(Classification::CATEGORIZED->name) ?
                    $mouvementsParHashParClassification->get(Classification::CATEGORIZED->name) :
                    new MouvementsParHash();

                /** @var MouvementsParHash $uncategorizedMouvements */
                $uncategorizedMouvements = $mouvementsParHashParClassification->has(Classification::UNCATEGORIZED->name) ?
                    $mouvementsParHashParClassification->get(Classification::UNCATEGORIZED->name) :
                    new MouvementsParHash();

                /** @var MouvementsParHash $ambiguousMouvements */
                $ambiguousMouvements = $mouvementsParHashParClassification->has(Classification::AMBIGUOUS->name) ?
                    $mouvementsParHashParClassification->get(Classification::AMBIGUOUS->name) :
                    new MouvementsParHash();

                /** @var MouvementsParHash $waitingMouvements */
                $waitingMouvements = $mouvementsParHashParClassification->has(Classification::WAITING->name) ?
                    $mouvementsParHashParClassification->get(Classification::WAITING->name) :
                    new MouvementsParHash();

                // Indique si on doit ignorer les mouvements anciens
                $skipOldOnes = $request->get('skipOldOnes', false);

                // Le dernier mouvement inséré
                $latestMouvement = $this->mouvementRepository->findLatestOne();

                /* Passage des mouvements en session.
                 * Ils sont identifiés par leur hash car leur id ne sera disponible
                 * qu'une fois qu'ils auront été persistés. */
                foreach ($mouvements as $hash => $mouvement) {
                    /* Si on doit ignorer les mouvements anciens,
                     * alors on n'importe le mouvement que s'il est plus récent que le dernier présent en base. */
                    if (false !== $skipOldOnes && $latestMouvement instanceof Mouvement) {
                        $date = $mouvement->date;
                        $latestMouvementDate = $latestMouvement->date;

                        if ($date < $latestMouvementDate) {
                            $mouvements = $mouvements->remove($hash);
                            $categorizedMouvements = $categorizedMouvements->remove($hash);
                            $uncategorizedMouvements = $uncategorizedMouvements->remove($hash);
                            $ambiguousMouvements = $ambiguousMouvements->remove($hash);
                            $waitingMouvements = $waitingMouvements->remove($hash);

                            continue;
                        }
                    }
                }

                $request->getSession()->set('mouvements', $mouvements);

                return $this->render(
                    'Import/mouvements.html.twig',
                    [
                        'handlers' => $this->handlers,
                        'categorized_mouvements' => $categorizedMouvements,
                        'uncategorized_mouvements' => $uncategorizedMouvements,
                        'ambiguous_mouvements' => $ambiguousMouvements,
                        'waiting_mouvements' => $waitingMouvements,
                        'comptes' => $this->compteRepository->findAll(),
                        'categories' => $this->categorieRepository->findAll()->toAssociativeArray(),
                    ]
                );

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
                $mouvements = $request->getSession()->get('mouvements');

                foreach ($mouvements as $mouvement) {
                    // Identification du mouvement par son hash
                    $hash = $mouvement->getHash();

                    if (!in_array($hash, $mouvementsHashToImport)) {
                        continue;
                    }

                    // Modification éventuelle de la date
                    if (isset($mouvementsData[$hash]['date'])) {
                        $dateString = $mouvementsData[$hash]['date'];
                        $date = \DateTimeImmutable::createFromFormat('d-m-Y H:i:s', "$dateString 00:00:00");
                        if (!($date instanceof \DateTimeImmutable)) {
                            throw new BadRequestHttpException("Date du mouvement invalide : $dateString");
                        }
                        $mouvement->date = $date;
                    }

                    // Modification éventuelle de la catégorie
                    if (isset($mouvementsData[$hash]['categorie'])) {
                        $categorieId = CategorieId::estValide((string) $mouvementsData[$hash]['categorie']) ?
                            new CategorieId((string) $mouvementsData[$hash]['categorie']) :
                            null;

                        if ($categorieId instanceof CategorieId) {
                            $categorie = $this->categorieRepository->find($categorieId);

                            if (!($categorie instanceof Categorie)) {
                                throw new BadRequestHttpException("Catégorie $categorieId introuvable");
                            }
                        } else {
                            $categorie = null;
                        }

                        $mouvement->categorie = $categorie;
                    }

                    // Modification éventuelle du compte
                    if (isset($mouvementsData[$hash]['compte'])) {
                        if (CompteId::estValide((string) $mouvementsData[$hash]['compte'])) {
                            $compteId = new CompteId((string) $mouvementsData[$hash]['compte']);
                            $compte = $this->compteRepository->find($compteId);

                            if (!($compte instanceof Compte)) {
                                throw new BadRequestHttpException("Compte $compteId introuvable");
                            }

                            $mouvement->compte = $compte;
                        }
                    }

                    // Modification éventuelle du montant
                    if (isset($mouvementsData[$hash]['montant'])) {
                        $montant = (float) $mouvementsData[$hash]['montant'];
                        $mouvement->montant = $montant;
                    }

                    // Modification éventuelle de la description
                    if (isset($mouvementsData[$hash]['description'])) {
                        $description = $mouvementsData[$hash]['description'];
                        $mouvement->description = $description;
                    }

                    // Persistance des données
                    $this->mouvementRepository->save($mouvement); // @todo : sortir de la boucle
                }

                // Vidage de la session
                $request->getSession()->remove('mouvements');

                return $this->redirectToRoute('import_mouvements');
        }
    }

    /**
     * Renvoie une instance du handler d'import.
     *
     * @throws \Exception si le handler demandé est invalide
     */
    private function getHandler(Request $request): MouvementsImportHandlerInterface
    {
        $handlerIdentifier = $request->get('handlerIdentifier');

        if (!is_string($handlerIdentifier)) {
            throw new \Exception('Handler manquant.');
        }

        if (!in_array($handlerIdentifier, array_keys($this->handlers))) {
            throw new \Exception(sprintf("Le handler [%s] n'est pas configuré.", $handlerIdentifier));
        }

        $this->handlerIdentifier = $handlerIdentifier;

        foreach ($this->mouvementsImportHandlers as $mouvementImportHandler) {
            if ($mouvementImportHandler->supports($handlerIdentifier)) {
                return $mouvementImportHandler;
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
