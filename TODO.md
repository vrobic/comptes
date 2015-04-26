# Liste de tâches

## Partage et documentation

- rajouter l'entité Keyword dans le MLD Dia
- distribuer le bundle plutôt que toute l'installation Symfony

## Core

- Contrôleurs
    - utiliser OptionsResolver pour contrôler les données en entrée
- Services
    - n'injecter que les dépendances nécessaires, pas tout le container
    - remplacer ComptesBundle\Service\ConfigurationLoader par Symfony\Component\Config\Definition\ConfigurationInterface
- Doctrine
    - déporter le mapping dans un fichier YML
- Fixtures
    - utiliser Alice
- Assets
    - mettre à jour les librairies : TWBS, jQuery, Highcharts et Select2

## Imports

- en import web, afficher les indicateurs à l'utilisateur

## Stats

- catégories
    - revoir comment est calculé le total "non catégorisé"
- dashboard
    - créer une classe DashboardWidget
    - permettre de choisir quels widgets afficher
- pleins
    - consommation moyenne / plein
    - autonomie estimée / plein
    - prix du carburant / plein
    - coût en carburant / véhicule, au 100km
- comptes
    - dépenses moyennes / mois

## Évolutions

- intégrer dépenses véhicules + coût assurance
- intégrer gestion des congés ?
- intégrer suivi sportif ?
