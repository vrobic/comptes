<?php

namespace Comptes\Bundle\CoreBundle\Service;

use Ddeboer\DataImport\Reader\ExcelReader;
use Comptes\Bundle\CoreBundle\Entity\Mouvement;

/**
 * Implémente un handler Excel d'import de mouvements de la banque CIC.
 */
class CICExcelMouvementsImportHandler extends MouvementsImportHandler
{
    /**
     * Parse les mouvements et remplit les tableaux de classification du handler.
     *
     * @param \SplFileObject $file Fichier Excel fourni par le CIC.
     */
    public function parse(\SplFileObject $file)
    {
        // Repositories
        $mouvementRepository = $this->em->getRepository('ComptesCoreBundle:Mouvement');
        $compteRepository = $this->em->getRepository('ComptesCoreBundle:Compte');

        // Le dernier mouvement inséré
        $latestMouvement = $mouvementRepository->findLatestOne();

        // Configuration du handler
        $configuration = $this->configuration['cic.excel'];

        // Tableau de correspondance entre l'index de la feuille et le compte bancaire
        $comptesBySheets = array();

        foreach ($configuration['sheets'] as $sheetIndex => $compteID)
        {
            $comptesBySheets[$sheetIndex] = $compteRepository->find($compteID);
        }

        foreach ($comptesBySheets as $sheetIndex => $compte)
        {
            $reader = new ExcelReader($file, 4, $sheetIndex);

            foreach ($reader as $row)
            {
                // Arrivée à la fin du tableau des mouvements
                if ($row["Solde"] === null)
                {
                    break;
                }

                $mouvement = new Mouvement();

                // Date, Excel la stocke comme un integer. 0 = 01/01/1900, 25569 = 01/01/1970
                $date = new \DateTime();
                $daysSince1970 = $row["Opération"] - 25569;
                $timestamp = strtotime("+$daysSince1970 days", 0);
                $date->setTimestamp($timestamp);
                $mouvement->setDate($date);

                // On n'importe le mouvement que s'il est plus récent que le dernier présent en base
                if ($latestMouvement !== null)
                {
                    $latestMouvementDate = $latestMouvement->getDate();

                    if ($date < $latestMouvementDate)
                    {
                        continue;
                    }
                }

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