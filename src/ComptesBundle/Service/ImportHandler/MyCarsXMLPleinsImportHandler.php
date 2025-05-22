<?php

namespace ComptesBundle\Service\ImportHandler;

use ComptesBundle\Entity\Plein;
use ComptesBundle\Entity\Repository\VehiculeRepository;
use ComptesBundle\Entity\Vehicule;

/**
 * Implémente un handler XML d'import de pleins pour l'application MyCars.
 */
class MyCarsXMLPleinsImportHandler extends AbstractPleinsImportHandler
{
    const HANDLER_ID = 'mycars.xml';

    /**
     * Parse les pleins et remplit le tableau $pleins.
     *
     * @param \SplFileObject $file Fichier XML fourni par MyCars.
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

        $filename = $file->getPathname();
        $xml = simplexml_load_file($filename);

        if (!($xml instanceof \SimpleXMLElement)) {
            throw new \Exception("Impossible de charger le fichier XML.");
        }

        if (!($xml->refuel instanceof \SimpleXMLElement)) {
            throw new \Exception("Impossible d'accéder au nœud refuel.");
        }

        foreach ($xml->refuel as $refuel) {
            $plein = new Plein();

            // Véhicule
            // @todo : supprimer ce @codingStandardsIgnoreLine ?
            $vehiculeName = (string) $refuel->car_name; // @codingStandardsIgnoreLine
            $vehicule = $vehicules[$vehiculeName];
            $plein->setVehicule($vehicule);

            // Date
            $date = \DateTime::createFromFormat('Y-m-d G:i', (string) $refuel->refuelDate);
            if (!($date instanceof \DateTime)) {
                throw new \Exception("Date du plein invalide : $refuel->refuelDate");
            }
            $plein->setDate($date);

            // Distance parcourue
            $distanceParcourue = (float) $refuel->distance;
            $plein->setDistanceParcourue($distanceParcourue);

            // Montant
            $montant = (float) $refuel->price * (float) $refuel->quantity;
            $plein->setMontant($montant);

            // Prix au litre
            $prixLitre = (float) $refuel->price;
            $plein->setPrixLitre($prixLitre);

            // Quantité
            $quantite = (float) $refuel->quantity;
            $plein->setQuantite($quantite);

            // Classification
            $classification = $this->getClassification($plein);
            $this->classify($plein, $classification);
        }
    }
}
