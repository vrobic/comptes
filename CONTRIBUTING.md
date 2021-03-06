# Contribuer

## Développement d'un handler d'import

Un handler d'import est un service de prise en charge d'un fichier, en vue d'importer son contenu dans la base de données.

Techniquement, le handler n'est qu'une des briques du moteur d'import et ce n'est pas lui qui enregistre les objets en base de données. Il se contente de lire le fichier et d'en extraire chaque mouvement.

L'application n'embarque qu'une poignée de handlers. Pour les mouvements de comptes bancaires :

- relevé de comptes de la banque CIC, aux formats Excel et CSV
- relevé de comptes de la banque Crédit Mutuel, aux formats Excel et CSV
- relevé de comptes de la banque Caisse d'Épargne, au format CSV

Et pour les pleins de carburant :

- données de l'application Android MyCars, aux formats XML et CSV

Le moteur d'import, par sa généricité, rend le développement d'un handler très simple. Il se résume à un service Symfony2 et quelques lignes de configuration.

### Mise en place du service

Le service est déclaré dans :

    src/ComptesBundle/Resources/config/services.yml

selon le schéma suivant :

    comptes_bundle.import.pleins.mycars.xml:
        class: ComptesBundle\Service\ImportHandler\MyCarsXMLPleinsImportHandler
        parent: comptes_bundle.import.pleins

où `mycars.xml` est l'identifiant du handler.

Le service doit étendre une des deux classes abstraites suivantes : `ComptesBundle\Service\ImportHandler\AbstractPleinsImportHandler` ou `ComptesBundle\Service\ImportHandler\AbstractMouvementsImportHandler` et implémenter la méthode :

    public function parse(\SplFileObject $file) {}

Cette méthode doit parser le contenu de `$file` pour en extraire les données. Chaque objet détecté doit être instancié puis passé aux fonctions de classification.

Une implémentation de la méthode parse en pseudo-code pourrait donc être :

    public function parse(\SplFileObject $file)
    {
        $lines = $file->getLines();

        foreach ($lines as $line) {

            $plein = new Plein();

            $plein->setVehicule($line->vehicule);
            // ...

            $classification = $this->getClassification($plein);
            $this->classify($plein, $classification);
        }
    }

### Configuration du handler

Le service étant déclaré à Symfony par `services.yml`, le handler doit être déclaré au moteur d'import dans le fichier `import.yml` :

    src/ComptesBundle/Resources/config/import.yml

au sein d'un des tableaux `pleins` ou `mouvements` :

    handlers:
        pleins:
            # Identifiant du service d'import, ici "comptes_bundle.import.pleins.mycars.xml".
            mycars.xml:
                # Nom de la source
                name: MyCars - XML
                # Description de la source. HTML autorisé.
                description: |-
                    Import XML depuis l'application Android MyCars.
                # L'extension du fichier attendu par le handler d'import
                extension: xml
                config: ~
        mouvements:
            # ...

Les paramètres `name`, `description`, `extension` et `config` sont obligatoires. Si les besoins du handler le justifient, des paramètres peuvent être rajoutés librement sous le nœud `config`.

Aucun risque risque de faire une mauvaise configuration. L'application utilise un service centralisé qui charge et vérifie la cohérence des configurations. Il ne manquera pas de vous rappeler à l'ordre !
