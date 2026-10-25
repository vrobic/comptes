<?php

declare(strict_types=1);

namespace App\Application\Import;

/**
 * Le fichier à importer ne respecte pas le format attendu par le handler.
 */
final class FichierImportInvalideException extends \UnexpectedValueException
{
}
