<?php

namespace Comptes\Bundle\CoreBundle\Service;

use Comptes\Bundle\CoreBundle\Entity\Mouvement;

/**
 * Implémente un handler CSV d'import de mouvements de la banque Caisse d'Épargne.
 */
class CaisseEpargneMouvementsImportHandler extends MouvementsImportHandler
{
    /**
     * Parse les mouvements et remplit les tableaux de classification du handler.
     *
     * @param \SplFileObject $file Fichier CSV fourni par la Caisse d'Épargne.
     */
    public function parse(\SplFileObject $file)
    {
        // Repositories
        $mouvementRepository = $this->em->getRepository('ComptesCoreBundle:Mouvement');
        $compteRepository = $this->em->getRepository('ComptesCoreBundle:Compte');

        // Le dernier mouvement inséré
        $latestMouvement = $mouvementRepository->findLatestOne();

        // Configuration du handler
        $configuration = $this->configuration['caissedepargne.csv'];

        // Le compte bancaire dans lequel importer les mouvements
        $compteID = $configuration['compte'];
        $compte = $compteRepository->find($compteID);

        // Lignes du fichier CSV qui représentent des mouvements
        $rows = array();

        // Les en-têtes de colonnes
        $headers = array();

        // Numéros de ligne
        $currentLine = 0;
        $headersLine = false;

        while (($cols = $file->fgetcsv(';')) !== null)
        {
            // Recherche de la ligne d'en-têtes
            if ($cols[0] == "Date")
            {
                $headersLine = $currentLine;
            }

            // Si la ligne d'en-têtes a été trouvée et qu'on l'a dépassée
            if ($headersLine !== false && $currentLine > $headersLine)
            {
                // Si la date est valide et sans month shifting
                $date = \DateTime::createFromFormat('d/m/y', $cols[0]);
                $isValidDate = $date !== false && !array_sum($date->getLastErrors());

                // Alors la ligne en cours est un mouvement
                if ($isValidDate)
                {
                    $row = array_combine($headers, $cols);
                    $rows[] = $row;
                }
            }
            elseif ($currentLine == $headersLine)
            {
                $headers = $cols;
            }

            $currentLine++;
        }

        foreach ($rows as $row)
        {
            $mouvement = new Mouvement();

            // Date
            $date = \DateTime::createFromFormat('d/m/y', (string) $row["Date"]);
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
            $montant = $row["Débit"] !== "" ? $row["Débit"] : $row["Crédit"];
            $montant = str_replace(',', '.', $montant);
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