<?php

declare(strict_types=1);

namespace App\Application\Import;

use App\Domain\Compte\Compte;
use App\Domain\Compte\CompteId;
use App\Domain\Mouvement\Montant;
use App\Domain\Mouvement\Mouvement;
use App\Domain\Mouvement\MouvementId;
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
     * @param \SplFileObject $file Fichier Excel fourni par le Crédit Mutuel
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

        $comptes = $this->compteRepository->findAll();

        /**
         * @var int    $sheetIndex
         * @var string $compteId
         */
        foreach ($configuration['sheets'] as $sheetIndex => $compteId) {
            if (!CompteId::estValide($compteId)) {
                throw new \RuntimeException(); // @todo
            }

            $compte = $comptes->findFirst(
                static fn (Compte $compte): bool => (string) $compte->id === $compteId
            );

            if (!($compte instanceof Compte)) {
                throw new \RuntimeException(); // @todo
            }

            $comptesBySheets[$sheetIndex] = $compte;
        }

        $xlsx = SimpleXLSX::parse($file->getRealPath());

        foreach ($comptesBySheets as $sheetIndex => $compte) {
            /** @var int $rowIndex */
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
                $montant = new Montant((float) $montant);

                $mouvement = new Mouvement(
                    new MouvementId((string) $this->idGenerator->générer()),
                    new \DateTimeImmutable($date),
                    null, // sera définie plus tard par la classification
                    $compte,
                    $montant,
                    $description
                );

                $this->classify($mouvement);
            }
        }
    }
}
