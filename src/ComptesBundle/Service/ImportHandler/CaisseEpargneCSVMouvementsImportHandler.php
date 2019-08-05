<?php

namespace ComptesBundle\Service\ImportHandler;

use ComptesBundle\Entity\Mouvement;

/**
 * Implémente un handler CSV d'import de mouvements de la banque Caisse d'Épargne.
 */
class CaisseEpargneCSVMouvementsImportHandler extends AbstractMouvementsImportHandler
{
    const HANDLER_ID = 'caissedepargne.csv';

    /**
     * Parse les mouvements et remplit les tableaux de classification du handler.
     *
     * @param \SplFileObject $file Fichier CSV fourni par la Caisse d'Épargne.
     */
    public function parse(\SplFileObject $file)
    {
        // Repository
        $compteRepository = $this->em->getRepository('ComptesBundle:Compte');

        // Configuration du handler
        $configuration = $this->configuration[self::HANDLER_ID]['config'];

        // Le compte bancaire dans lequel importer les mouvements
        $compteID = $configuration['compte'];
        $compte = $compteRepository->find($compteID);

        // Lignes du fichier CSV qui représentent des mouvements
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

        while (($cols = $file->fgetcsv(';')) !== null) {
            // Si on a dépassé la ligne d'en-têtes
            if ($currentLine > $headersLine) {
                // Si la date est valide et sans month shifting
                $date = \DateTime::createFromFormat('d/m/y', $cols[0]);
                $isValidDate = $date !== false && !array_sum($date->getLastErrors());

                // Alors la ligne en cours est un mouvement
                if ($isValidDate) {
                    $row = array_combine($headers, $cols);
                    $rows[] = $row;
                }
            }

            $currentLine++;
        }

        foreach ($rows as $row) {
            $mouvement = new Mouvement();

            // Date
            $date = \DateTime::createFromFormat('d/m/y', (string) $row['date']);
            $mouvement->setDate($date);

            // Compte
            $mouvement->setCompte($compte);

            // Montant
            $montant = $row['debit'] !== '' ? $row['debit'] : $row['credit'];
            $montant = str_replace(',', '.', $montant);
            $montant = sprintf('%0.2f', $montant);
            $mouvement->setMontant($montant);

            // Description
            $description = $row['libelle'];
            $mouvement->setDescription($description);

            // Classification
            $classification = $this->getClassification($mouvement);
            $this->classify($mouvement, $classification);
        }
    }
}
