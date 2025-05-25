<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Domain\Compte\Compte;
use App\Domain\Mouvement\Mouvement;
use Shuchkin\SimpleXLSX;

/**
 * Implémente un handler Excel d'import de mouvements de la banque Crédit Mutuel.
 */
class CMExcelMouvementsImportHandler extends AbstractMouvementsImportHandler
{
    private const string HANDLER_ID = 'cm.excel';

    private const int START_ROW_NUMBER = 6;
    private const string DATE_COLUMN_ID = 'A';
    private const string DESCRIPTION_COLUMN_ID = 'C';
    private const string DEBIT_COLUMN_ID = 'D';
    private const string CREDIT_COLUMN_ID = 'E';

    public function supports(string $handlerId): bool
    {
        return self::HANDLER_ID === $handlerId;
    }

    /**
     * Parse les mouvements et remplit les tableaux de classification du handler.
     *
     * @param \SplFileObject $file fichier Excel fourni par le Crédit Mutuel
     */
    public function parse(\SplFileObject $file): void
    {
        // Configuration du handler
        $configuration = $this->configuration[self::HANDLER_ID]['config'];

        /**
         * Tableau de correspondance entre l'index de la feuille et le compte bancaire.
         *
         * @var array<int, Compte> $comptesBySheets
         */
        $comptesBySheets = [];

        foreach ($configuration['sheets'] as $sheetIndex => $compteID) {
            $compte = $this->compteRepository->find($compteID);

            if (!($compte instanceof Compte)) {
                // @todo
            }

            $comptesBySheets[$sheetIndex] = $compte;
        }

        $xlsx = SimpleXLSX::parse($file->getRealPath());

        foreach ($comptesBySheets as $sheetIndex => $compte) {
            foreach ($xlsx->rows($sheetIndex) as $rowIndex => $row) {
                if ($rowIndex < self::START_ROW_NUMBER) {
                    continue;
                }

                $date = $xlsx->getCell($sheetIndex, sprintf('%s%d', self::DATE_COLUMN_ID, $rowIndex));
                $debit = $xlsx->getCell($sheetIndex, sprintf('%s%d', self::DEBIT_COLUMN_ID, $rowIndex));
                $credit = $xlsx->getCell($sheetIndex, sprintf('%s%d', self::CREDIT_COLUMN_ID, $rowIndex));
                $description = $xlsx->getCell($sheetIndex, sprintf('%s%d', self::DESCRIPTION_COLUMN_ID, $rowIndex));

                // Arrivée à la fin du tableau des mouvements
                if (null === $debit && null === $credit) {
                    break;
                }

                // Montant
                $montant = '' !== $debit ? $debit : $credit;
                $montant = sprintf('%0.2f', $montant);
                $montant = (float) $montant;

                $mouvement = new Mouvement(
                    null,
                    new \DateTime($date),
                    null, // sera définie plus tard par la classification
                    $compte,
                    $montant,
                    $description
                );

                // Classification
                $classification = $this->getClassification($mouvement);
                $this->classify($mouvement, $classification);
            }
        }
    }
}
