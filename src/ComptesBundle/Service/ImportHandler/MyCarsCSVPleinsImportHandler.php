<?php

namespace ComptesBundle\Service\ImportHandler;

use ComptesBundle\Entity\Plein;
use ComptesBundle\Entity\Repository\VehiculeRepository;
use ComptesBundle\Entity\Vehicule;

/**
 * Implémente un handler CSV d'import de pleins pour l'application MyCars.
 */
class MyCarsCSVPleinsImportHandler extends AbstractPleinsImportHandler
{
    const HANDLER_ID = 'mycars.csv';

    /**
     * Parse les pleins et remplit le tableau $pleins.
     *
     * @param \SplFileObject $file Fichier CSV fourni par MyCars.
     */
    public function parse(\SplFileObject $file): void
    {
        /** @var VehiculeRepository $vehiculeRepository */
        $vehiculeRepository = $this->em->getRepository('ComptesBundle:Vehicule');

        // Configuration du handler
        $configuration = $this->configuration[$this::HANDLER_ID]['config'];

        /**
         * Tableau de correspondance entre le nom du véhicule dans MyCars et l'objet Vehicule.
         *
         * @var array<string, Vehicule> $vehicules
         */
        $vehicules = [];

        foreach ($configuration['vehicules'] as $vehiculeLabel => $vehiculeID) {
            /** @var ?Vehicule $vehicule */
            $vehicule = $vehiculeRepository->find($vehiculeID);

            if (!($vehicule instanceof Vehicule)) {
                throw new \Exception("Véhicule $vehiculeID introuvable.");
            }

            $vehicules[$vehiculeLabel] = $vehicule;
        }

        /**
         * Lignes du fichier CSV qui représentent des pleins.
         *
         * @var array<string[]> $refuels
         */
        $refuels = [];

        // Les en-têtes de colonnes
        $headers = [];

        // Numéros de ligne
        $currentLine = 0;
        $headersLine = null;

        while (is_array($cols = $file->fgetcsv())) {
            /** @var string[] $cols */

            // Recherche de la ligne d'en-têtes
            if ($cols[0] === '#entity: refuel') {
                $headersLine = $currentLine + 1;
            }

            // Si la ligne d'en-têtes a été trouvée et qu'on l'a dépassée
            if (is_int($headersLine) && $currentLine > $headersLine) {
                // La ligne en cours est un plein
                $refuel = array_combine($headers, $cols);

                if (!is_array($refuel)) {
                    throw new \Exception("La ligne $currentLine ne comporte pas le même nombre de colonnes que la ligne $headersLine (en-tête).");
                }

                $refuels[] = $refuel;
            } elseif ($currentLine === $headersLine) {
                $headers = $cols;
            }

            $currentLine++;
        }

        foreach ($refuels as $refuel) {
            $plein = new Plein();

            // Véhicule
            $vehiculeName = $refuel['##car_name'];
            $vehicule = $vehicules[$vehiculeName];
            $plein->setVehicule($vehicule);

            // Date
            $date = \DateTime::createFromFormat('Y-m-d G:i', $refuel['refuelDate']);
            if (!($date instanceof \DateTime)) {
                throw new \Exception("Date du plein invalide : {$refuel['refuelDate']}");
            }
            $plein->setDate($date);

            // Distance parcourue
            $distanceParcourue = (float) $refuel['distance'];
            $plein->setDistanceParcourue($distanceParcourue);

            // Montant
            $montant = (float) $refuel['price'] * (float) $refuel['quantity'];
            $plein->setMontant($montant);

            // Prix au litre
            $prixLitre = (float) $refuel['price'];
            $plein->setPrixLitre($prixLitre);

            // Quantité
            $quantite = (float) $refuel['quantity'];
            $plein->setQuantite($quantite);

            // Classification
            $classification = $this->getClassification($plein);
            $this->classify($plein, $classification);
        }
    }
}
