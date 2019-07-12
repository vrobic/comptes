<?php

namespace ComptesBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Classe d'abstraction des scripts d'import de mouvements et de pleins.
 */
abstract class AbstractImportCommand extends ContainerAwareCommand
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
     * @param string $type Deux valeurs possibles : 'mouvements' ou 'pleins'.
     *
     * @throws \Exception Dans le cas où le type est invalide.
     */
    protected function setType($type)
    {
        if (!in_array($type, ['mouvements', 'pleins'])) {
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
        $configuration = $configurationLoader->load('import');
        $this->handlers = $configuration['handlers'][$this->type];
    }

    /**
     * Renvoie une instance du handler d'import.
     *
     * @param string $handlerIdentifier
     *
     * @return Une implémentation de l'interface ImportHandlerInterface.
     *
     * @throws \Exception Si le handler demandé est invalide.
     */
    protected function getHandler($handlerIdentifier)
    {
        $handlerIdentifiers = array_keys($this->handlers);

        if (!in_array($handlerIdentifier, $handlerIdentifiers)) {
            throw new \Exception(sprintf("Le handler [%s] n'existe pas. Sont disponibles : [%s].", $handlerIdentifier, implode(', ', $handlerIdentifiers)));
        }

        $this->handlerIdentifier = $handlerIdentifier;
        $handler = $this->getContainer()->get("comptes_bundle.import.$this->type.$handlerIdentifier");

        return $handler;
    }

    /**
     * Renvoie le fichier dont le chemin est passé en paramètre.
     *
     * @param string $filename Chemin du fichier.
     *
     * @return SplFileObject
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException           En cas d'erreur d'accès au fichier.
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
