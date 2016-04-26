<?php

namespace ComptesBundle\Service\ImportHandler;

use ComptesBundle\Entity\Plein;

/**
 * Implémente un handler XML d'import de pleins pour l'application MyCars.
 */
final class MyCarsXMLPleinsImportHandler extends AbstractPleinsImportHandler
{
    /**
     * Parse les pleins et remplit le tableau $pleins.
     *
     * @param \SplFileObject $file Fichier XML fourni par MyCars.
     */
    public function parse(\SplFileObject $file)
    {
        // Repository
        $vehiculeRepository = $this->em->getRepository('ComptesBundle:Vehicule');

        // Configuration du handler
        $configuration = $this->configuration['mycars.xml']['config'];

        // Tableau de correspondance entre le nom du véhicule dans MyCars et l'objet Vehicule
        $vehicules = array();

        foreach ($configuration['vehicules'] as $vehiculeLabel => $vehiculeID) {
            $vehicules[$vehiculeLabel] = $vehiculeRepository->find($vehiculeID);
        }

        $filename = $file->getPathname();
        $xml = simplexml_load_file($filename);

        foreach ($xml->refuel as $refuel) {

            $plein = new Plein();

            // Véhicule
            $vehiculeName = (string) $refuel->car_name;
            $vehicule = $vehicules[$vehiculeName];
            $plein->setVehicule($vehicule);

            // Date
            $date = \DateTime::createFromFormat('Y-m-d G:i', (string) $refuel->refuelDate);
            $plein->setDate($date);

            // Distance parcourue
            $distanceParcourue = (string) $refuel->distance;
            $plein->setDistanceParcourue($distanceParcourue);

            // Montant
            $montant = (string) $refuel->price * (string) $refuel->quantity;
            $plein->setMontant($montant);

            // Prix au litre
            $prixLitre = (string) $refuel->price;
            $plein->setPrixLitre($prixLitre);

            // Quantité
            $quantite = (string) $refuel->quantity;
            $plein->setQuantite($quantite);

            // Classification
            $classification = $this->getClassification($plein);
            $this->classify($plein, $classification);
        }
    }
}
