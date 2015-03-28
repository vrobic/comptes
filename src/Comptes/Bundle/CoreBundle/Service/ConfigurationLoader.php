<?php

namespace Comptes\Bundle\CoreBundle\Service;

use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Yaml\Parser;

/**
 * Service permettant de charger et valider les fichiers de configuration.
 * @todo Il existe des classes de validation de la configuration.
 */
class ConfigurationLoader
{
    /**
     * @var Container
     */
    protected $container;

    /**
     * Nom du fichier de configuration.
     *
     * @var string
     */
    private $configurationFile;

    /**
     * Configuration.
     *
     * @var array
     */
    private $configuration;

    /**
     * Constructeur.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        // Injection de dépendances
        $this->container = $container;

        $this->configurationFile = null;
        $this->configuration = array();
    }

    /**
     * Charge la configuration.
     *
     * @param string $configurationFile Le nom du fichier de configuration.
     * @return array La configuration.
     * @throws \Exception En cas d'erreur de configuration.
     */
    public function load($configurationFile)
    {
        // Chemin du fichier de configuration
        $this->configurationFile = $configurationFile;
        $fileLocator = new FileLocator(__DIR__.'/../Resources/config');
        $configurationFilename = $fileLocator->locate($configurationFile);

        // Parseur YAML
        $yaml = new Parser();
        $this->configuration = $yaml->parse(file_get_contents($configurationFilename));

        // Validation de la configuration
        $valid = $this->validateConfiguration();

        if (!$valid)
        {
            $this->configuration = array();
            throw new \Exception("$configurationFile : configuration invalide.");
        }

        return $this->configuration;
    }

    /**
     * Construit un message d'exception détaillé.
     *
     * @param array $parameters Le chemin du paramètre en faute.
     * @param string $message Le message d'erreur.
     * @return string Le message d'exception.
     */
    private function getExceptionMessage($parameters, $message)
    {
        $parametersString = implode(":", $parameters);
        return "Mauvaise configuration $this->configurationFile (paramètre $parametersString) : $message";
    }

    /**
     * Valide la configuration.
     *
     * @return boolean
     * @throws \Exception En cas d'erreur de configuration.
     */
    public function validateConfiguration()
    {
        $configurationFile = $this->configurationFile;

        // Méthodes de validation
        $validators = array(
            'fixtures.yml' => 'validateFixturesConfiguration',
            'import.yml' => 'validateImportConfiguration',
            'stats.yml' => 'validateStatsConfiguration'
        );

        if (isset($validators[$configurationFile]))
        {
            $validator = $validators[$configurationFile];

            $valid = $this->$validator();
        }
        else
        {
            $valid = true;
        }

        return $valid;
    }

    /**
     * Valide la configuration du fichier fixtures.yml.
     *
     * @return boolean
     * @throws \Exception En cas d'erreur de configuration.
     */
    private function validateFixturesConfiguration()
    {
        return true;
    }

    /**
     * Valide la configuration du fichier import.yml.
     *
     * @return boolean
     * @throws \Exception En cas d'erreur de configuration.
     */
    private function validateImportConfiguration()
    {
        $configuration = $this->configuration;

        // Vérification des handlers
        if (empty($configuration['handlers']))
        {
            throw new \Exception($this->getExceptionMessage(array("handlers"), "Aucun handler n'est défini."));
        }

        if (!is_array($configuration['handlers']))
        {
            throw new \Exception($this->getExceptionMessage(array("handlers"), "Doit être un tableau."));
        }

        foreach ($configuration['handlers'] as $type => $handlers)
        {
            if (!in_array($type, array('mouvements', 'pleins')))
            {
                throw new \Exception($this->getExceptionMessage(array("handlers", $type), "Les types de handler autorisés sont [mouvements] et [pleins], pas [$type]."));
            }

            if (!is_array($handlers))
            {
                throw new \Exception($this->getExceptionMessage(array("handlers", $type), "Doit être un tableau."));
            }

            foreach ($handlers as $identifier => $handler)
            {
                $hasService = $this->container->has("comptes_core.import.$type.$identifier");

                if (!$hasService)
                {
                    throw new \Exception($this->getExceptionMessage(array("handlers", $type, $identifier), "Aucun service correspondant à [comptes_core.import.$type.$identifier]."));
                }

                if (!key_exists('name', $handler))
                {
                    throw new \Exception($this->getExceptionMessage(array("handlers", $type, $identifier, "name"), "Paramètre manquant."));
                }

                if (!key_exists('extension', $handler))
                {
                    throw new \Exception($this->getExceptionMessage(array("handlers", $type, $identifier, "extension"), "Paramètre manquant."));
                }
            }
        }

        return true;
    }

    /**
     * Valide la configuration du fichier stats.yml.
     *
     * @return boolean
     * @throws \Exception En cas d'erreur de configuration.
     */
    private function validateStatsConfiguration()
    {
        return true;
    }
}