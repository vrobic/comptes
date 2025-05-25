<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Domain\Compte\Compte;
use App\Domain\Mouvement\Mouvement;

/**
 * Implémente un handler CSV d'import de mouvements de la banque Caisse d'Épargne.
 */
class CaisseEpargneCSVMouvementsImportHandler extends AbstractMouvementsImportHandler
{
    private const string HANDLER_ID = 'caissedepargne.csv';

    public function supports(string $handlerId): bool
    {
        return self::HANDLER_ID === $handlerId;
    }

    /**
     * Parse les mouvements et remplit les tableaux de classification du handler.
     *
     * @param \SplFileObject $file fichier CSV fourni par la Caisse d'Épargne
     */
    public function parse(\SplFileObject $file): void
    {
        // Configuration du handler
        $configuration = $this->configuration[self::HANDLER_ID]['config'];

        // Le compte bancaire dans lequel importer les mouvements
        $compteID = $configuration['compte'];
        $compte = $this->compteRepository->find($compteID);

        if (!($compte instanceof Compte)) {
            // @todo
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

        while (is_array($cols = $file->fgetcsv(';'))) {
            /* @var string[] $cols */

            // Si on a dépassé la ligne d'en-têtes
            if ($currentLine > $headersLine) {
                // Si la date est valide et sans month shifting
                $date = \DateTime::createFromFormat('d/m/y', $cols[0]);
                $isValidDate = $date instanceof \DateTime && is_array($date->getLastErrors()) && 0 === array_sum($date->getLastErrors());

                // Alors la ligne en cours est un mouvement
                if ($isValidDate) {
                    $row = array_combine($headers, $cols);

                    if (!is_array($row)) {
                        throw new \Exception("La ligne $currentLine ne comporte pas le même nombre de colonnes que la ligne $headersLine (en-tête).");
                    }

                    $rows[] = $row;
                }
            }

            ++$currentLine;
        }

        foreach ($rows as $row) {
            // Date
            $date = \DateTime::createFromFormat('d/m/y', (string) $row['date']);
            if (!($date instanceof \DateTime)) {
                throw new \Exception("Date du mouvement invalide : {$row['date']}");
            }

            // Montant
            $montant = '' !== $row['debit'] ? $row['debit'] : $row['credit'];
            $montant = str_replace(',', '.', $montant);
            $montant = sprintf('%0.2f', $montant);
            $montant = (float) $montant;

            $mouvement = new Mouvement(
                null,
                $date,
                null, // sera définie plus tard par la classification
                $compte,
                $montant,
                $row['libelle']
            );

            // Classification
            $classification = $this->getClassification($mouvement);
            $this->classify($mouvement, $classification);
        }
    }
}
