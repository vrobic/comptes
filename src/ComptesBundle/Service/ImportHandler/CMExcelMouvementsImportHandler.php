<?php

namespace ComptesBundle\Service\ImportHandler;

use Ddeboer\DataImport\Reader\ExcelReader;
use ComptesBundle\Entity\Mouvement;

/**
 * Implémente un handler Excel d'import de mouvements de la banque Crédit Mutuel.
 */
final class CMExcelMouvementsImportHandler extends AbstractMouvementsImportHandler
{
    /**
     * Parse les mouvements et remplit les tableaux de classification du handler.
     *
     * @param \SplFileObject $file Fichier Excel fourni par le Crédit Mutuel.
     */
    public function parse(\SplFileObject $file)
    {
        // Repository
        $compteRepository = $this->em->getRepository('ComptesBundle:Compte');

        // Configuration du handler
        $configuration = $this->configuration['cm.excel']['config'];

        // Tableau de correspondance entre l'index de la feuille et le compte bancaire
        $comptesBySheets = array();

        foreach ($configuration['sheets'] as $sheetIndex => $compteID) {
            $comptesBySheets[$sheetIndex] = $compteRepository->find($compteID);
        }

        foreach ($comptesBySheets as $sheetIndex => $compte) {

            $reader = new ExcelReader($file, 4, $sheetIndex);

            foreach ($reader as $row) {

                // Arrivée à la fin du tableau des mouvements
                if ($row["Solde"] === null) {
                    break;
                }

                $mouvement = new Mouvement();

                // Date, Excel la stocke comme un integer. 0 = 01/01/1900, 25569 = 01/01/1970
                $date = new \DateTime();
                $daysSince1970 = $row["Opération"] - 25569;
                $timestamp = strtotime("+$daysSince1970 days", 0);
                $date->setTimestamp($timestamp);
                $mouvement->setDate($date);

                // Compte
                $mouvement->setCompte($compte);

                // Montant
                $montant = $row["Débit"] !== null ? $row["Débit"] : $row["Crédit"];
                $montant = sprintf('%0.2f', $montant);
                $mouvement->setMontant($montant);

                // Description
                $description = $row["Libellé"];
                $mouvement->setDescription($description);

                // Classification
                $classification = $this->getClassification($mouvement);
                $this->classify($mouvement, $classification);
            }
        }
    }
}
