<?php

namespace ComptesBundle\Service;

use Symfony\Component\DependencyInjection\Container;

/**
 * Service permettant de charger et valider la configuration.
 */
class ConfigurationLoader
{
    /**
     * @internal Les clés de configuration disponibles.
     */
    const KEYS = [
        'fixtures',
        'import',
        'stats',
    ];

    /**
     * La clé de configuration chargée parmi celles disponibles.
     *
     * @var string|null Est null tant que la méthode load n'a pas été appelée.
     */
    private $key;

    /**
     * La configuration chargée.
     *
     * @var array
     */
    private $configuration;

    /**
     * L'injection du conteneur de services est justifiée par le dynamisme
     * des handlers d'import qui ne permet pas de connaître à ce stade le nom
     * des classes utilisées.
     *
     * @var Container
     */
    private $container;

    /**
     * Constructeur.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->key = null;
        $this->configuration = [];
        $this->container = $container;
    }

    /**
     * Charge la configuration.
     *
     * @param string $key La clé de la configuration à charger : 'fixtures',
     *                    'import' ou 'stats'.
     *
     * @return array La configuration.
     *
     * @throws \Exception En cas d'erreur de configuration, ou lorsque la clé
     *                    de configuration à charger n'est pas valide.
     */
    public function load($key)
    {
        if (!in_array($key, self::KEYS)) {
            throw new \Exception(sprintf("La clé de configuration [%s] n'existe pas. Sont disponibles : [%s].", $key, implode(', ', self::KEYS)));
        }

        $this->configuration = $this->container->getParameter("comptes.$key");

        $valid = $this->validateConfiguration();

        if (!$valid) {
            $this->configuration = [];
            throw new \Exception("Configuration invalide.");
        }

        return $this->configuration;
    }

    /**
     * Valide la configuration.
     *
     * @return bool
     *
     * @throws \Exception En cas d'erreur de configuration.
     */
    public function validateConfiguration()
    {
        // Méthodes de validation
        $validators = [
            'fixtures' => 'validateFixturesConfiguration',
            'import' => 'validateImportConfiguration',
            'stats' => 'validateStatsConfiguration',
        ];

        if (isset($validators[$this->key])) {
            $validator = $validators[$this->key];
            $valid = $this->$validator();
        } else {
            $valid = true;
        }

        return $valid;
    }

    /**
     * Valide la configuration des fixtures.
     *
     * @return bool
     *
     * @throws \Exception En cas d'erreur de configuration.
     */
    private function validateFixturesConfiguration()
    {
        return true;
    }

    /**
     * Valide la configuration des imports.
     *
     * @return bool
     *
     * @throws \Exception En cas d'erreur de configuration.
     */
    private function validateImportConfiguration()
    {
        foreach ($this->configuration['handlers'] as $type => $handlers) {
            foreach ($handlers as $identifier => $handler) {
                $hasService = $this->container->has("comptes_bundle.import.$type.$identifier");
                if (!$hasService) {
                    throw new \Exception($this->getExceptionMessage(
                        ['handlers', $type, $identifier],
                        "Aucun service n'est enregistré sous l'identifiant [comptes_bundle.import.$type.$identifier]."
                    ));
                }
            }
        }

        return true;
    }

    /**
     * Valide la configuration des statistiques.
     *
     * @return bool
     *
     * @throws \Exception En cas d'erreur de configuration.
     */
    private function validateStatsConfiguration()
    {
        return true;
    }

    /**
     * Construit un message d'exception détaillé.
     *
     * @param array  $parameters Le chemin du paramètre en faute.
     * @param string $message    Le message d'erreur.
     *
     * @return string Le message d'exception.
     */
    private function getExceptionMessage($parameters, $message)
    {
        $parametersString = implode('.', $parameters);

        return "Mauvaise configuration du paramètre [$parametersString] : $message";
    }
}
