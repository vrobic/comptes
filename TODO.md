# Liste de tâches

## Partage et documentation

- rajouter l'entité Keyword et Compte > dateFermeture dans le MLD Dia
- distribuer le bundle plutôt que toute l'installation Symfony

## Core

- Après chaque find (dans les contrôleurs uniquement ?), faire un throw $this->createNotFoundException();
- Traduction
    - tout piper avec trans
- Comptes
    - afficher le solde en début et fin de période
    - permettre d'éditer les comptes ?
    - rajouter une date de fermeture
- Catégories
    - à la mise à jour ou création d'une catégorie et s'il y a un mot-clé, proposer de recatégoriser les mouvements existants
    - sur les graphiques de catégories, ajouter une courbe de moyenne lissée
- Contrôleurs
    - utiliser OptionsResolver pour contrôler les données en entrée
- Services
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
    - économies / intervalle de dates
    - dépenses moyennes / mois

## Évolutions

- intégrer dépenses véhicules + coût assurance
- intégrer gestion des congés ?
- intégrer suivi sportif ?
