<?php

namespace ComptesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class ImportCommand extends ContainerAwareCommand
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
     * Définit le type d'import.
     *
     * @param string Deux valeurs possibles : 'mouvements' ou 'pleins'.
     * @throws \Exception Dans le cas où le type est invalide.
     */
    protected function setType($type)
    {
        if (!in_array($type, array('mouvements', 'pleins'))) {
            throw new \Exception("Type d'import invalide.");
        }

        $this->type = $type;
    }

    /**
     * Charge la configuration adaptée au type d'import.
     */
    protected function loadConfiguration()
    {
        $configurationLoader = $this->getContainer()->get('comptes_bundle.configuration.loader');
        $configuration = $configurationLoader->load('import.yml');
        $this->handlers = $configuration['handlers'][$this->type];
    }

    /**
     * Renvoie une instance du handler d'import.
     *
     * @param string $handlerIdentifier
     * @return Une implémentation de l'interface ImportHandler.
     * @throws \Exception Si le handler demandé est invalide.
     */
    protected function getHandler($handlerIdentifier)
    {
        $handlerIdentifiers = array_keys($this->handlers);

        if (!in_array($handlerIdentifier, $handlerIdentifiers)) {
            throw new \Exception("Le handler [$handlerIdentifier] n'existe pas. Sont disponibles : [".implode(', ', $handlerIdentifiers)."].");
        }

        $this->handlerIdentifier = $handlerIdentifier;
        $handler = $this->getContainer()->get("comptes_bundle.import.$this->type.$handlerIdentifier");

        return $handler;
    }

    /**
     * Renvoie le fichier dont le chemin est passé en paramètre.
     *
     * @param string $filename Chemin du fichier.
     * @return SplFileObject
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException En cas d'erreur d'accès au fichier.
     * @throws \Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException Si le type de fichier n'est pas celui attendu.
     */
    protected function getFile($filename)
    {
        if (!file_exists($filename)) {
            throw new \Symfony\Component\HttpKernel\Exception\BadRequestHttpException("Le fichier $filename n'existe pas.");
        }

        $splFile = new \SplFileObject($filename);

        $fileExtension = $splFile->getExtension();

        // Handlers disponibles
        if ($fileExtension !== $this->handlers[$this->handlerIdentifier]['extension']) {
            throw new \Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException("Le handler [$this->handlerIdentifier] ne supporte pas le type de fichier [$fileExtension].");
        }

        return $splFile;
    }
}