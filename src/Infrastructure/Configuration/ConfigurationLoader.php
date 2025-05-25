<?php

declare(strict_types=1);

namespace App\Infrastructure\Configuration;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Service permettant de charger la configuration.
 */
readonly class ConfigurationLoader
{
    public function __construct(
        private string $projectDir,
        private string $configFilePath,
    ) {
    }

    /**
     * Charge la configuration.
     *
     * @return array la configuration
     *
     * @throws \Exception s'il est impossible de récupérer la configuration
     */
    public function __invoke(): array
    {
        $fileContent = file_get_contents(sprintf('%s/%s', $this->projectDir, $this->configFilePath));
        if (false === $fileContent) {
            throw new \Exception(sprintf('Échec de lecture du fichier de configuration %s.', $this->configFilePath));
        }

        try {
            $processedConfiguration = new Processor()->processConfiguration(
                new Configuration(),
                [
                    Yaml::parse($fileContent),
                ]
            );
        } catch (ParseException $e) {
            throw new \Exception(sprintf('Fichier YAML %s invalide.', $this->configFilePath), previous: $e);
        }

        return $processedConfiguration;
    }
}
