<?php

namespace ComptesBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @todo Faire une fonction pour chaque action :
 *       - mouvementsParseAction
 *       - mouvementsImportParseAction
 *       - pleinsImportParseAction
 *       - pleinssImportParseAction
 * 
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
     * @param Request $request
     * @return Response
     * @throws \Exception En cas d'erreur d'import du fichier.
     */
    public function mouvementsAction(Request $request)
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
        $compteRepository = $doctrine->getRepository('ComptesBundle:Compte');
        $categorieRepository = $doctrine->getRepository('ComptesBundle:Categorie');
        $mouvementRepository = $doctrine->getRepository('ComptesBundle:Mouvement');

        // Tous les comptes bancaires
        $comptes = $compteRepository->findAll();

        // Toutes les catégories de mouvements
        $categories = $categorieRepository->findAll();

        // Le dernier mouvement inséré
        $latestMouvement = $mouvementRepository->findLatestOne();

        // Classification des mouvements
        $categorizedMouvements = array();
        $uncategorizedMouvements = array();
        $ambiguousMouvements = array();
        $waitingMouvements = array();

        // Action : parsing ou import
        $action = $request->get('action');

        switch ($action)
        {
            case 'parse': // Parsing du fichier uploadé

                // Parsing du fichier
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
                foreach ($mouvements as $hash => $mouvement)
                {
                    /* Si on doit ignorer les mouvements anciens,
                     * alors on n'importe le mouvement que s'il est plus récent que le dernier présent en base. */
                    if ($skipOldOnes !== false && $latestMouvement !== null)
                    {
                        $date = $mouvement->getDate();
                        $latestMouvementDate = $latestMouvement->getDate();

                        if ($date < $latestMouvementDate)
                        {
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
                $mouvementsHashToImport = $request->get('mouvements_hash_to_import', array());

                // Données de mouvements à modifier
                $mouvementsData = $request->get('mouvements', array());

                // Récupération des mouvements
                $mouvements = $session->get('mouvements');

                foreach ($mouvements as $mouvement)
                {
                    // Identification du mouvement par son hash
                    $hash = $mouvement->getHash();

                    if (!in_array($hash, $mouvementsHashToImport))
                    {
                        continue;
                    }

                    // Modification éventuelle de la date
                    if (isset($mouvementsData[$hash]['date']))
                    {
                        $dateString = $mouvementsData[$hash]['date'];
                        $date = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateString 00:00:00");
                        $mouvement->setDate($date);
                    }

                    // Modification éventuelle de la catégorie
                    if (isset($mouvementsData[$hash]['categorie']))
                    {
                        $categorieID = $mouvementsData[$hash]['categorie'];
                        $categorie = $categorieID !== "" ? $categorieRepository->find($categorieID) : null;
                        $mouvement->setCategorie($categorie);
                    }

                    // Modification éventuelle du compte
                    if (isset($mouvementsData[$hash]['compte']))
                    {
                        $compteID = $mouvementsData[$hash]['compte'];
                        $compte = $compteID !== "" ? $compteRepository->find($compteID) : null;
                        $mouvement->setCompte($compte);
                    }

                    // Modification éventuelle du montant
                    if (isset($mouvementsData[$hash]['montant']))
                    {
                        $montant = $mouvementsData[$hash]['montant'];
                        $mouvement->setMontant($montant);
                    }

                    // Modification éventuelle de la description
                    if (isset($mouvementsData[$hash]['description']))
                    {
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
            array(
                'handlers' => $this->handlers,
                'comptes' => $comptes,
                'categories' => $categories,
                'categorized_mouvements' => $categorizedMouvements,
                'uncategorized_mouvements' => $uncategorizedMouvements,
                'ambiguous_mouvements' => $ambiguousMouvements,
                'waiting_mouvements' => $waitingMouvements
            )
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
     * @param Request $request
     * @return Response
     * @throws \Exception En cas d'erreur d'import du fichier.
     */
    public function pleinsAction(Request $request)
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
        $vehiculeRepository = $doctrine->getRepository('ComptesBundle:Vehicule');
        $pleinRepository = $doctrine->getRepository('ComptesBundle:Plein');

        // Tous les véhicules
        $vehicules = $vehiculeRepository->findAll();

        // Le dernier plein inséré
        $latestPlein = $pleinRepository->findLatestOne();

        // Classification des pleins
        $validPleins = array();
        $waitingPleins = array();

        // Action : parsing ou import
        $action = $request->get('action');

        switch ($action)
        {
            case 'parse': // Parsing du fichier uploadé

                // Parsing du fichier
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
                foreach ($pleins as $hash => $plein)
                {
                    /* Si on doit importer les pleins anciens,
                     * alors on n'importe le plein que s'il est plus récent que le dernier présent en base. */
                    if ($skipOldOnes !== false && $latestPlein !== null)
                    {
                        $date = $plein->getDate();
                        $latestPleinDate = $latestPlein->getDate();

                        if ($date < $latestPleinDate)
                        {
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
                $pleinsHashToImport = $request->get('pleins_hash_to_import', array());

                // Données de pleins à modifier
                $pleinsData = $request->get('pleins', array());

                // Récupération des pleins
                $pleins = $session->get('pleins');

                foreach ($pleins as $plein)
                {
                    // Identification du plein par son hash
                    $hash = $plein->getHash();

                    if (!in_array($hash, $pleinsHashToImport))
                    {
                        continue;
                    }

                    // Modification éventuelle de la date
                    if (isset($pleinsData[$hash]['date']))
                    {
                        $dateString = $pleinsData[$hash]['date'];
                        $date = \DateTime::createFromFormat('d-m-Y H:i:s', "$dateString 00:00:00");
                        $plein->setDate($date);
                    }

                    // Modification éventuelle du véhicule
                    if (isset($pleinsData[$hash]['vehicule']))
                    {
                        $vehiculeID = $pleinsData[$hash]['vehicule'];
                        $vehicule = $vehiculeID !== "" ? $vehiculeRepository->find($vehiculeID) : null;
                        $plein->setVehicule($vehicule);
                    }

                    // Modification éventuelle de la distance parcourue
                    if (isset($pleinsData[$hash]['distanceParcourue']))
                    {
                        $distanceParcourue = $pleinsData[$hash]['distanceParcourue'];
                        $plein->setDistanceParcourue($distanceParcourue);
                    }

                    // Modification éventuelle de la quantité
                    if (isset($pleinsData[$hash]['quantite']))
                    {
                        $quantite = $pleinsData[$hash]['quantite'];
                        $plein->setQuantite($quantite);
                    }

                    // Modification éventuelle du prix au litre
                    if (isset($pleinsData[$hash]['prixLitre']))
                    {
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
            array(
                'handlers' => $this->handlers,
                'vehicules' => $vehicules,
                'valid_pleins' => $validPleins,
                'waiting_pleins' => $waitingPleins
            )
        );
    }

    /**
     * Définit le type d'import.
     *
     * @param string Deux valeurs possibles : 'mouvements' ou 'pleins'.
     * @throws \Exception Dans le cas où le type est invalide.
     */
    private function setType($type)
    {
        if (!in_array($type, array('mouvements', 'pleins')))
        {
            throw new \Exception("Type d'import invalide.");
        }

        $this->type = $type;
    }

    /**
     * Charge la configuration adaptée au type d'import.
     */
    private function loadConfiguration()
    {
        $configurationLoader = $this->container->get('comptes_bundle.configuration.loader');
        $configuration = $configurationLoader->load('import.yml');
        $this->handlers = $configuration['handlers'][$this->type];
    }

    /**
     * Renvoie une instance du handler d'import.
     *
     * @param Request $request
     * @return Une implémentation de l'interface ImportHandler.
     * @throws \Exception Si le handler demandé est invalide.
     */
    private function getHandler(Request $request)
    {
        $handlerIdentifier = $request->get('handlerIdentifier');

        if ($handlerIdentifier === null)
        {
            throw new \Exception("Handler manquant.");
        }
        elseif (!in_array($handlerIdentifier, array_keys($this->handlers)))
        {
            throw new \Exception("Le handler [$handlerIdentifier] n'existe pas.");
        }

        $this->handlerIdentifier = $handlerIdentifier;
        $handler = $this->container->get("comptes_bundle.import.$this->type.$handlerIdentifier");

        return $handler;
    }

    /**
     * Renvoie le fichier uploadé.
     *
     * @param Request $request
     * @return SplFileObject
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException En cas d'erreur d'accès au fichier.
     * @throws \Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException Si le type de fichier n'est pas celui attendu.
     */
    private function getFile(Request $request)
    {
        $file = $request->files->get('file');

        if ($file === null)
        {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Fichier manquant.");
        }
        elseif (!$file->isValid())
        {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Échec de l'upload du fichier.");
        }

        $fileExtension = $file->getClientOriginalExtension();

        // Handlers disponibles
        if ($fileExtension !== $this->handlers[$this->handlerIdentifier]['extension'])
        {
            throw new \Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException("Le handler [$this->handlerIdentifier] ne supporte pas le type de fichier [$fileExtension].");
        }

        $splFile = $file->openFile();

        return $splFile;
    }
}
