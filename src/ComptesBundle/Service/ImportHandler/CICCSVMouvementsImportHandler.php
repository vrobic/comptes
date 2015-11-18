<?php

namespace ComptesBundle\Service\ImportHandler;

use ComptesBundle\Entity\Mouvement;

/**
 * Implémente un handler CSV d'import de mouvements de la banque CIC.
 */
final class CICCSVMouvementsImportHandler extends AbstractMouvementsImportHandler
{
    /**
     * Parse les mouvements et remplit les tableaux de classification du handler.
     *
     * @param \SplFileObject $file Fichier CSV fourni par le CIC.
     */
    public function parse(\SplFileObject $file)
    {
        // Repository
        $compteRepository = $this->em->getRepository('ComptesBundle:Compte');

        // Configuration du handler
        $configuration = $this->configuration['cic.csv'];

        // Le compte bancaire dans lequel importer les mouvements
        $compteID = $configuration['compte'];
        $compte = $compteRepository->find($compteID);

        // Lignes du fichier CSV qui représentent des mouvements
        $rows = array();

        // Les en-têtes de colonnes
        $headers = array(
            'date_operation',
            'date_valeur',
            'debit',
            'credit',
            'libelle',
            'solde',
        );

        // Numéros de ligne
        $currentLine = 0;
        $headersLine = 0;

        while (($cols = $file->fgetcsv(';')) !== null) {

            // Si on a dépassé la ligne d'en-têtes
            if ($currentLine > $headersLine) {

                // Si la date est valide et sans month shifting
                $date = \DateTime::createFromFormat('d/m/Y', $cols[0]);
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
            $date = \DateTime::createFromFormat('d/m/Y', (string) $row['date_operation']);
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
