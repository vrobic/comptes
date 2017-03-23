# Liste de tâches

## Partage et documentation

- rajouter l'entité Keyword et Compte > dateFermeture dans le MLD Dia

## Core et refactoring

- Architecture
    - implémenter les bonnes pratiques concernant l'emplacement des templates et des fichiers de configuration
- Validation
    - la brancher au code
- Traduction
    - piper tous les libellés avec trans
- Contrôleurs
    - utiliser OptionsResolver pour contrôler les données en entrée
- Repositories
    - passer l'ordre en paramètre des méthodes est inutile lorsque celles-ci ne renvoient pas de listes d'entités
- Fixtures
    - utiliser Alice
- Formulaires
    - utiliser des form types

## Features

- afficher des colonnes débit/crédit dans les listes de mouvements
- multi-utilisateur
- édition des comptes, véhicules et carburants
- permettre de lancer manuellement une recatégorisation automatique des mouvements
- possibilité de désactiver gestion des mouvements ou des pleins

## Imports

- en import web, afficher les indicateurs à l'utilisateur

## Stats

- dashboard
    - afficher le débit/crédit en plus de la balance
    - créer une classe DashboardWidget
    - permettre de choisir quels widgets afficher
- pleins
    - consommation moyenne / plein
    - autonomie estimée / plein
    - prix du carburant / plein
    - coût en carburant / véhicule, au 100km
- comptes
    - économies / intervalle de dates

## Évolutions à long terme

- intégrer dépenses véhicules + coût assurance
- intégrer gestion des congés ?
- intégrer suivi sportif ?
