<?php

namespace ComptesBundle\Service\ImportHandler;

use PhpOffice\PhpSpreadsheet;
use ComptesBundle\Entity\Mouvement;

/**
 * Implémente un handler Excel d'import de mouvements de la banque Crédit Mutuel.
 */
class CMExcelMouvementsImportHandler extends AbstractMouvementsImportHandler
{
    const START_ROW_NUMBER = 6;
    const DATE_COLUMN_ID = 'A';
    const DESCRIPTION_COLUMN_ID = 'C';
    const DEBIT_COLUMN_ID = 'D';
    const CREDIT_COLUMN_ID = 'E';

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
        $comptesBySheets = [];

        foreach ($configuration['sheets'] as $sheetIndex => $compteID) {
            $comptesBySheets[$sheetIndex] = $compteRepository->find($compteID);
        }

        $reader = PhpSpreadsheet\IOFactory::load($file->getRealPath());

        foreach ($comptesBySheets as $sheetIndex => $compte) {
            $sheet = $reader->getSheet($sheetIndex);
            $rowIterator = $sheet->getRowIterator(self::START_ROW_NUMBER);

            foreach ($rowIterator as $row) {
                $rowIndex = $row->getRowIndex();
                $date = $sheet->getCell(sprintf('%d%d', self::DATE_COLUMN_ID, $rowIndex))->getValue();
                $debit = $sheet->getCell(sprintf('%d%d', self::DEBIT_COLUMN_ID, $rowIndex))->getValue();
                $credit = $sheet->getCell(sprintf('%d%d', self::CREDIT_COLUMN_ID, $rowIndex))->getValue();
                $description = $sheet->getCell(sprintf('%d%d', self::DESCRIPTION_COLUMN_ID, $rowIndex))->getValue();

                // Arrivée à la fin du tableau des mouvements
                if (null === $debit && null === $credit) {
                    break;
                }

                $mouvement = new Mouvement();

                // Date
                $mouvement->setDate(PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date));

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
        }
    }
}
