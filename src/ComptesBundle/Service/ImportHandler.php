<?php

namespace ComptesBundle\Service;

/**
 * Décrit un handler d'import.
 */
interface ImportHandler
{
    /**
     * Parse le fichier et remplit les tableaux de classification du handler.
     *
     * @param \SplFileObject $file
     */
    public function parse(\SplFileObject $file);
}