<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteId;
use App\Domain\Mouvement\Montant;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementCollection;
use App\Domain\Mouvement\MouvementId;

/**
 * Implémente un handler CSV d'import de mouvements de la banque Caisse d'Épargne.
 */
class CaisseEpargneCSVMouvementsImportHandler extends AbstractMouvementsImportHandler
{
    protected const string HANDLER_ID = 'caissedepargne.csv';

    public function supports(string $handlerId): bool
    {
        return static::HANDLER_ID === $handlerId;
    }

    /**
     * @param \SplFileObject $file Fichier CSV fourni par la Caisse d'Épargne
     */
    protected function lireMouvements(\SplFileObject $file): MouvementCollection
    {
        $mouvements = new MouvementCollection();

        // Configuration du handler
        $configuration = $this->configuration[static::HANDLER_ID]['config'];

        if (!CompteId::estValide($configuration['compte'])) {
            throw new \UnexpectedValueException(sprintf("Le compte [%s] configuré pour le handler [%s] n'est pas un UUID valide.", $configuration['compte'], static::HANDLER_ID));
        }

        // Le compte bancaire dans lequel importer les mouvements
        $compte = $this->compteRepository->find(new CompteId($configuration['compte']));

        if (!$compte instanceof Compte) {
            throw new \UnexpectedValueException(sprintf("Le compte [%s] configuré pour le handler [%s] n'existe pas.", $configuration['compte'], static::HANDLER_ID));
        }

        /**
         * Lignes du fichier CSV qui représentent des mouvements.
         *
         * @var array<string[]> $rows
         */
        $rows = [];

        // Les en-têtes de colonnes
        $headers = [
            'date',
            'numero_operation',
            'libelle',
            'debit',
            'credit',
            'detail',
            '',
        ];

        // Numéros de ligne
        $currentLine = 0;
        $headersLine = 4;

        while (is_array($cols = $file->fgetcsv(';', '"', '\\'))) {
            /* @var string[] $cols */

            // Si on a dépassé la ligne d'en-têtes
            if ($currentLine > $headersLine) {
                // Si la date est valide et sans month shifting
                $date = \DateTimeImmutable::createFromFormat('d/m/y', (string) $cols[0]);
                $isValidDate = $date instanceof \DateTimeImmutable && false === $date->getLastErrors();

                // Alors la ligne en cours est un mouvement
                if ($isValidDate) {
                    if (count($cols) !== count($headers)) {
                        throw new FichierImportInvalideException(sprintf('La ligne [%d] ne comporte pas le même nombre de colonnes que la ligne [%d] (en-tête).', $currentLine, $headersLine));
                    }

                    $rows[] = array_combine($headers, $cols);
                }
            }

            ++$currentLine;
        }

        foreach ($rows as $row) {
            // Date
            $date = \DateTimeImmutable::createFromFormat('d/m/y H:i:s', "{$row['date']} 00:00:00");
            if (!$date instanceof \DateTimeImmutable) {
                throw new FichierImportInvalideException(sprintf('Date du mouvement invalide : [%s].', $row['date']));
            }

            // Montant
            $montant = '' !== $row['debit'] ? $row['debit'] : $row['credit'];
            $montant = str_replace(',', '.', $montant);
            $montant = sprintf('%0.2f', $montant);
            $montant = new Montant((float) $montant);

            $mouvement = new Mouvement(
                new MouvementId((string) $this->idGenerator->générer()),
                $date,
                null, // sera définie plus tard par la classification
                $compte,
                $montant,
                $row['libelle']
            );

            $mouvements = $mouvements->add($mouvement);
        }

        return $mouvements;
    }
}
