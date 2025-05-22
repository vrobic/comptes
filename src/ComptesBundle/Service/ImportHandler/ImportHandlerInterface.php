<?php

namespace ComptesBundle\Service\ImportHandler;

interface ImportHandlerInterface
{
    /**
     * @todo documenter
     */
    public function parse(\SplFileObject $file): void;
}
