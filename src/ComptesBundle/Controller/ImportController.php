<?php

namespace ComptesBundle\Controller;

use ComptesBundle\Entity\Categorie;
use ComptesBundle\Entity\Compte;
use ComptesBundle\Entity\Mouvement;
use ComptesBundle\Entity\Plein;
use ComptesBundle\Entity\Repository\CategorieRepository;
use ComptesBundle\Entity\Repository\CompteRepository;
use ComptesBundle\Entity\Repository\MouvementRepository;
use ComptesBundle\Entity\Repository\PleinRepository;
use ComptesBundle\Entity\Repository\VehiculeRepository;
use ComptesBundle\Entity\Vehicule;
use ComptesBundle\Service\ConfigurationLoader;
use ComptesBundle\Service\ImportHandler\ImportHandlerInterface;
use ComptesBundle\Service\ImportHandler\MouvementsImportHandlerInterface;
use ComptesBundle\Service\ImportHandler\PleinsImportHandlerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
class ImportController extends Controller
{
    /**
     * Type d'import : 'mouvements' ou 'pleins'.
     *
     * @var string
     */
    private $type;

    /**
     * Configuration des handlers d'import.
     *
     * @var array
     */
    private $handlers;

    /**
     * Identifiant du service d'import, au sein de $this->handlers.
     *
     * @var string
     */
    private $handlerIdentifier;

    /**
     * Contrôleur d'import des mouvements. Se déroule en trois étapes :
     *      - parsing du fichier uploadé
     *      - affichage d'un formulaire permettant d'ajuster les données
     *      - validation et import
     *
     * @todo Comme pour MouvementController->edit(), utiliser un formulaire Symfony.
     *
     * @throws \Exception En cas d'erreur d'import du fichier.
     */
    public function mouvementsAction(Request $request): Response
    {
        // Définit le type d'import
        $this->setType('mouvements');

        // Chargement de la configuration
        $this->loadConfiguration();

        // La session
        $session = $request->getSession();

        // Entity manager et repositories
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();
        /** @var CompteRepository $compteRepository */
        $compteRepository = $doctrine->getRepository('ComptesBundle:Compte');
        /** @var CategorieRepository $categorieRepository */
        $categorieRepository = $doctrine->getRepository('ComptesBundle:Categorie');
        /** @var MouvementRepository $mouvementRepository */
        $mouvementRepository = $doctrine->getRepository('ComptesBundle:Mouvement');

        // Tous les comptes bancaires
        $comptes = $compteRepository->findAll();

        // Toutes les catégories de mouvements
        $categories = $categorieRepository->findAll();

        // Le dernier mouvement inséré
        $latestMouvement = $mouvementRepository->findLatestOne();

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

                    // Détache les relations
                    $em->detach($mouvement);
                }

                $session->set('mouvements', $mouvements);

                break;

            case 'import': // Import des mouvements après ajustements manuels
                // Indicateurs
                $i = 0; // Nombre de mouvements importés
                $balance = 0; // Balance des mouvements (crédit ou débit)

                // Hash des mouvements à importer
                $mouvementsHashToImport = $request->get('mouvements_hash_to_import', []);

                // Données de mouvements à modifier
                $mouvementsData = $request->get('mouvements', []);

                // Récupération des mouvements
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
                            throw new \Exception("Date du mouvement invalide : $dateString");
                        }
                        $mouvement->setDate($date);
                    }

                    // Modification éventuelle de la catégorie
                    if (isset($mouvementsData[$hash]['categorie'])) {
                        $categorieID = $mouvementsData[$hash]['categorie'];
                        /** @var ?Categorie $categorie */
                        $categorie = $categorieID !== '' ? $categorieRepository->find($categorieID) : null; // @todo : voir que faire du null
                        $mouvement->setCategorie($categorie);
                    }

                    // Modification éventuelle du compte
                    if (isset($mouvementsData[$hash]['compte'])) {
                        $compteID = $mouvementsData[$hash]['compte'];
                        /** @var ?Compte $compte */
                        $compte = $compteID !== '' ? $compteRepository->find($compteID) : null; // @todo : voir que faire du null
                        $mouvement->setCompte($compte);
                    }

                    // Modification éventuelle du montant
                    if (isset($mouvementsData[$hash]['montant'])) {
                        $montant = $mouvementsData[$hash]['montant'];
                        $mouvement->setMontant($montant);
                    }

                    // Modification éventuelle de la description
                    if (isset($mouvementsData[$hash]['description'])) {
                        $description = $mouvementsData[$hash]['description'];
                        $mouvement->setDescription($description);
                    }

                    // Indicateurs
                    $i++;
                    $balance += $mouvement->getMontant();

                    // Enregistrement
                    $mouvement = $em->merge($mouvement);
                    $em->persist($mouvement);
                }

                // Persistance des données
                $em->flush();

                // Vidage de la session
                $session->remove('mouvements');

                break;
        }

        return $this->render(
            'ComptesBundle:Import:mouvements.html.twig',
            [
                'handlers' => $this->handlers,
                'comptes' => $comptes,
                'categories' => $categories,
                'categorized_mouvements' => $categorizedMouvements,
                'uncategorized_mouvements' => $uncategorizedMouvements,
                'ambiguous_mouvements' => $ambiguousMouvements,
                'waiting_mouvements' => $waitingMouvements,
            ]
        );
    }

    /**
     * Contrôleur d'import des pleins. Se déroule en trois étapes :
     *      - parsing du fichier uploadé
     *      - affichage d'un formulaire permettant d'ajuster les données
     *      - validation et import
     *
     * @todo Comme pour PleinController->edit(), utiliser un formulaire Symfony.
     *
     * @throws \Exception En cas d'erreur d'import du fichier.
     */
    public function pleinsAction(Request $request): Response
    {
        // Définit le type d'import
        $this->setType('pleins');

        // Chargement de la configuration
        $this->loadConfiguration();

        // La session
        $session = $request->getSession();

        // Entity manager et repositories
        $doctrine = $this->getDoctrine();
        $em = $doctrine->getManager();
        /** @var VehiculeRepository $vehiculeRepository */
        $vehiculeRepository = $doctrine->getRepository('ComptesBundle:Vehicule');
        /** @var PleinRepository $pleinRepository */
        $pleinRepository = $doctrine->getRepository('ComptesBundle:Plein');

        // Tous les véhicules
        $vehicules = $vehiculeRepository->findAll();

        // Le dernier plein inséré
        $latestPlein = $pleinRepository->findLatestOne();

        // Classification des pleins
        $validPleins = [];
        $waitingPleins = [];

        // Action : parsing ou import
        $action = $request->get('action');

        switch ($action) {
            case 'parse': // Parsing du fichier uploadé
                /** @var PleinsImportHandlerInterface $handler */
                $handler = $this->getHandler($request);
                $splFile = $this->getFile($request);
                $handler->parse($splFile);

                // Pleins classifiés
                $pleins = $handler->getPleins();
                $validPleins = $handler->getValidPleins();
                $waitingPleins = $handler->getWaitingPleins();

                // Indique si on doit ignorer les pleins anciens
                $skipOldOnes = $request->get('skipOldOnes', false);

                /* Passage des pleins en session.
                 * Ils sont identifiés par leur hash car leur id ne sera disponible
                 * qu'une fois qu'ils auront été persistés. */
                foreach ($pleins as $hash => $plein) {
                    /* Si on doit importer les pleins anciens,
                     * alors on n'importe le plein que s'il est plus récent que le dernier présent en base. */
                    if (false !== $skipOldOnes && $latestPlein instanceof Plein) {
                        $date = $plein->getDate();
                        $latestPleinDate = $latestPlein->getDate();

                        if ($date < $latestPleinDate) {
                            unset($validPleins[$hash]);
                            unset($waitingPleins[$hash]);
                            unset($pleins[$hash]);

                            continue;
                        }
                    }

                    // Détache les relations
                    $em->detach($plein);
                }

                $session->set('pleins', $pleins);

                break;

            case 'import': // Import des pleins après ajustements manuels
                // Indicateurs
                $i = 0; // Nombre de pleins importés

                // Hash des pleins à importer
                $pleinsHashToImport = $request->get('pleins_hash_to_import', []);

                // Données de pleins à modifier
                $pleinsData = $request->get('pleins', []);

                // Récupération des pleins
                $pleins = $session->get('pleins');

                foreach ($pleins as $plein) {
                    // Identification du plein par son hash
                    $hash = $plein->getHash();

                    if (!in_array($hash, $pleinsHashToImport)) {
                        continue;
                    }

                    // Modification éventuelle de la date
                    if (isset($pleinsData[$hash]['date'])) {
                        $dateString = $pleinsData[$hash]['date'];
                        $date = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateString 00:00:00");
                        if (!($date instanceof \DateTime)) {
                            throw new \Exception("Date du plein invalide : $dateString");
                        }
                        $plein->setDate($date);
                    }

                    // Modification éventuelle du véhicule
                    if (isset($pleinsData[$hash]['vehicule'])) {
                        $vehiculeID = $pleinsData[$hash]['vehicule'];
                        /** @var ?Vehicule $vehicule */
                        $vehicule = $vehiculeID !== '' ? $vehiculeRepository->find($vehiculeID) : null; // @todo : voir que faire du null
                        $plein->setVehicule($vehicule);
                    }

                    // Modification éventuelle de la distance parcourue
                    if (isset($pleinsData[$hash]['distanceParcourue'])) {
                        $distanceParcourue = $pleinsData[$hash]['distanceParcourue'];
                        $plein->setDistanceParcourue($distanceParcourue);
                    }

                    // Modification éventuelle de la quantité
                    if (isset($pleinsData[$hash]['quantite'])) {
                        $quantite = $pleinsData[$hash]['quantite'];
                        $plein->setQuantite($quantite);
                    }

                    // Modification éventuelle du prix au litre
                    if (isset($pleinsData[$hash]['prixLitre'])) {
                        $prixLitre = $pleinsData[$hash]['prixLitre'];
                        $plein->setPrixLitre($prixLitre);
                    }

                    // Recalcul du montant
                    $quantite = $plein->getQuantite();
                    $prixLitre = $plein->getPrixLitre();
                    $montant = $quantite * $prixLitre;
                    $plein->setMontant($montant);

                    // Indicateurs
                    $i++;

                    // Enregistrement
                    $plein = $em->merge($plein);
                    $em->persist($plein);
                }

                // Persistance des données
                $em->flush();

                // Vidage de la session
                $session->remove('pleins');

                break;
        }

        return $this->render(
            'ComptesBundle:Import:pleins.html.twig',
            [
                'handlers' => $this->handlers,
                'vehicules' => $vehicules,
                'valid_pleins' => $validPleins,
                'waiting_pleins' => $waitingPleins,
            ]
        );
    }

    /**
     * Définit le type d'import.
     *
     * @todo : $type peut devenir une enum
     *
     * @param string $type Deux valeurs possibles : 'mouvements' ou 'pleins'.
     *
     * @throws \Exception Dans le cas où le type est invalide.
     */
    private function setType(string $type): void
    {
        if (!in_array($type, array('mouvements', 'pleins'))) {
            throw new \Exception("Type d'import invalide.");
        }

        $this->type = $type;
    }

    /**
     * Charge la configuration adaptée au type d'import.
     */
    private function loadConfiguration(): void
    {
        /** @var ConfigurationLoader $configurationLoader */
        $configurationLoader = $this->container->get('comptes_bundle.configuration.loader');
        $configuration = $configurationLoader->load('import');
        $this->handlers = $configuration['handlers'][$this->type];
    }

    /**
     * Renvoie une instance du handler d'import.
     *
     * @throws \Exception Si le handler demandé est invalide.
     */
    private function getHandler(Request $request): ImportHandlerInterface
    {
        $handlerIdentifier = $request->get('handlerIdentifier');

        if (!is_string($handlerIdentifier)) {
            throw new \Exception("Handler manquant.");
        }
        if (!in_array($handlerIdentifier, array_keys($this->handlers))) {
            throw new \Exception(sprintf("Le handler [%s] n'existe pas.", $handlerIdentifier));
        }

        $this->handlerIdentifier = $handlerIdentifier;
        /** @var ImportHandlerInterface $handler */
        $handler = $this->container->get("comptes_bundle.import.$this->type.$handlerIdentifier");

        return $handler;
    }

    /**
     * Renvoie le fichier uploadé.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException           En cas d'erreur d'accès au fichier.
     * @throws \Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException Si le type de fichier n'est pas celui attendu.
     */
    private function getFile(Request $request): \SplFileObject
    {
        /** @var ?UploadedFile $file */
        $file = $request->files->get('file');

        if (!($file instanceof UploadedFile)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Fichier manquant.");
        }
        if (!$file->isValid()) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Échec de l'upload du fichier.");
        }

        $fileExtension = $file->getClientOriginalExtension();

        // Handlers disponibles
        if ($fileExtension !== $this->handlers[$this->handlerIdentifier]['extension']) {
            throw new \Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException("Le handler [$this->handlerIdentifier] ne supporte pas le type de fichier [$fileExtension].");
        }

        $splFile = $file->openFile();

        return $splFile;
    }
}
