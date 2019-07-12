<?php

namespace ComptesBundle\Service\ImportHandler;

use ComptesBundle\Entity\Plein;

/**
 * Implémente un handler CSV d'import de pleins pour l'application MyCars.
 */
class MyCarsCSVPleinsImportHandler extends AbstractPleinsImportHandler
{
    /**
     * Parse les pleins et remplit le tableau $pleins.
     *
     * @param \SplFileObject $file Fichier CSV fourni par MyCars.
     */
    public function parse(\SplFileObject $file)
    {
        // Repository
        $vehiculeRepository = $this->em->getRepository('ComptesBundle:Vehicule');

        // Configuration du handler
        $configuration = $this->configuration['mycars.csv']['config'];

        // Tableau de correspondance entre le nom du véhicule dans MyCars et l'objet Vehicule
        $vehicules = [];

        foreach ($configuration['vehicules'] as $vehiculeLabel => $vehiculeID) {
            $vehicules[$vehiculeLabel] = $vehiculeRepository->find($vehiculeID);
        }

        // Lignes du fichier CSV qui représentent des pleins
        $refuels = [];

        // Les en-têtes de colonnes
        $headers = [];

        // Numéros de ligne
        $currentLine = 0;
        $headersLine = false;

        while (($cols = $file->fgetcsv()) !== null) {
            // Recherche de la ligne d'en-têtes
            if ($cols[0] === '#entity: refuel') {
                $headersLine = $currentLine + 1;
            }

            // Si la ligne d'en-têtes a été trouvée et qu'on l'a dépassée
            if (false !== $headersLine && $currentLine > $headersLine) {
                // La ligne en cours est un plein
                $refuel = array_combine($headers, $cols);
                $refuels[] = $refuel;
            } elseif ($currentLine === $headersLine) {
                $headers = $cols;
            }

            $currentLine++;
        }

        foreach ($refuels as $refuel) {
            $plein = new Plein();

            // Véhicule
            $vehiculeName = (string) $refuel['##car_name'];
            $vehicule = $vehicules[$vehiculeName];
            $plein->setVehicule($vehicule);

            // Date
            $date = \DateTime::createFromFormat('Y-m-d G:i', (string) $refuel['refuelDate']);
            $plein->setDate($date);

            // Distance parcourue
            $distanceParcourue = (string) $refuel['distance'];
            $plein->setDistanceParcourue($distanceParcourue);

            // Montant
            $montant = (string) $refuel['price'] * (string) $refuel['quantity'];
            $plein->setMontant($montant);

            // Prix au litre
            $prixLitre = (string) $refuel['price'];
            $plein->setPrixLitre($prixLitre);

            // Quantité
            $quantite = (string) $refuel['quantity'];
            $plein->setQuantite($quantite);

            // Classification
            $classification = $this->getClassification($plein);
            $this->classify($plein, $classification);
        }
    }
}
