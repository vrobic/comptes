<?php

namespace ComptesBundle\Service\ImportHandler;

use ComptesBundle\Entity\Compte;
use ComptesBundle\Entity\Repository\CompteRepository;
use PhpOffice\PhpSpreadsheet;
use ComptesBundle\Entity\Mouvement;

/**
 * Implémente un handler Excel d'import de mouvements de la banque Crédit Mutuel.
 */
class CMExcelMouvementsImportHandler extends AbstractMouvementsImportHandler
{
    const HANDLER_ID = 'cm.excel';

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
    public function parse(\SplFileObject $file): void
    {
        /** @var CompteRepository $compteRepository */
        $compteRepository = $this->em->getRepository('ComptesBundle:Compte');

        // Configuration du handler
        $configuration = $this->configuration[$this::HANDLER_ID]['config'];

        /**
         * Tableau de correspondance entre l'index de la feuille et le compte bancaire.
         *
         * @var array<int, Compte> $comptesBySheets
         */
        $comptesBySheets = [];

        foreach ($configuration['sheets'] as $sheetIndex => $compteID) {
            /** @var ?Compte $compte */
            $compte = $compteRepository->find($compteID);

            if (!($compte instanceof Compte)) {
                throw new \Exception("Compte $compteID introuvable.");
            }

            $comptesBySheets[$sheetIndex] = $compte;
        }

        $reader = PhpSpreadsheet\IOFactory::load($file->getRealPath());

        foreach ($comptesBySheets as $sheetIndex => $compte) {
            $sheet = $reader->getSheet($sheetIndex);
            $rowIterator = $sheet->getRowIterator($this::START_ROW_NUMBER);

            foreach ($rowIterator as $row) {
                $rowIndex = $row->getRowIndex();
                $date = $sheet->getCell(sprintf('%s%d', $this::DATE_COLUMN_ID, $rowIndex))->getValue();
                $debit = $sheet->getCell(sprintf('%s%d', $this::DEBIT_COLUMN_ID, $rowIndex))->getValue();
                $credit = $sheet->getCell(sprintf('%s%d', $this::CREDIT_COLUMN_ID, $rowIndex))->getValue();
                $description = $sheet->getCell(sprintf('%s%d', $this::DESCRIPTION_COLUMN_ID, $rowIndex))->getValue();

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
                $montant = (float) $montant;
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
