<?php

namespace ComptesBundle\Service\ImportHandler;

use PhpOffice\PhpSpreadsheet\Reader\Exception;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\IOFactory;
use ComptesBundle\Entity\Mouvement;
use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Implémente un handler Excel d'import de mouvements de la banque Crédit Mutuel.
 */
class CMExcelMouvementsImportHandler extends AbstractMouvementsImportHandler
{
    /**
     * Parse les mouvements et remplit les tableaux de classification du handler.
     *
     * @param \SplFileObject $file Fichier Excel fourni par le Crédit Mutuel.
     *
     * @throws \Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function parse(\SplFileObject $file)
    {
        // Repository
        $compteRepository = $this->em->getRepository('ComptesBundle:Compte');

        // Configuration du handler
        $configuration = $this->configuration['cm.excel']['config'];

        // Attention : ceci est susceptible de changer car la banque met parfois à
        // jour ses formats de fichiers.
        $startRowNumber = 6;
        $dateColumnId = "A";
        $descriptionColumnId = "C";
        $debitColumnId = "D";
        $creditColumnId = "E";

        // Tableau de correspondance entre l'index de la feuille et le compte
        // bancaire.
        $comptesBySheets = array();

        foreach ($configuration['sheets'] as $sheetIndex => $compteID) {
            $comptesBySheets[$sheetIndex] = $compteRepository->find($compteID);
        }

        // Lecture des cellules et création des mouvements bancaires.
        foreach ($comptesBySheets as $sheetIndex => $compte) {
            try {
                $reader = IOFactory::load($file->getRealPath());
                $worksheet = $reader->getSheet($sheetIndex);
                $rowIterator = $worksheet->getRowIterator($startRowNumber);

                foreach ($rowIterator as $row) {
                    $index = $row->getRowIndex();

                    // Récupération des cellules voulues.
                    $debitCell = $worksheet->getCell($debitColumnId . $index);
                    $creditCell = $worksheet->getCell($creditColumnId . $index);
                    $descriptionCell = $worksheet->getCell(
                        $descriptionColumnId . $index
                    );
                    $dateCell = $worksheet->getCell($dateColumnId . $index);

                    // Récupération des valeurs saisies dans les cellules.
                    $debit = $debitCell->getValue();
                    $credit = $creditCell->getValue();
                    $description = $descriptionCell->getValue();
                    $date = $dateCell->getValue();

                    // Arrivée à la fin du tableau des mouvements
                    if (null === $debit && null === $credit) {
                        break;
                    }

                    $mouvement = new Mouvement();

                    // Date
                    $mouvement->setDate(Date::excelToDateTimeObject($date));

                    // Compte
                    $mouvement->setCompte($compte);

                    // Montant
                    $montant = $debit !== null ? $debit : $credit;

                    $montant = sprintf('%0.2f', $montant);

                    $mouvement->setMontant($montant);

                    // Description
                    $mouvement->setDescription($description);

                    // Classification
                    $classification = $this->getClassification($mouvement);
                    $this->classify($mouvement, $classification);
                }
            } catch (Exception $e) {
                break;
                $session = new Session();
                $session->start();
                $session->getFlashBag()->add(
                    'error',
                    $e->getMessage()
                );
            }
        }
    }
}
