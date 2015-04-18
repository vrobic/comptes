# Liste de tâches

## Core

- distribuer le bundle plutôt que toute l'installation Symfony
- remplacer ComptesBundle\Service\ConfigurationLoader par Symfony\Component\Config\Definition\ConfigurationInterface
- Déporter le mapping Doctrine dans un fichier YML
- Fixtures
    - utiliser Alice
- Améliorations
    - utilisation des .row dans les templates
- Évolutions
    - intégrer dépenses véhicules + coût assurance
    - intégrer gestion des congés ?
    - intégrer suivi sportif ?

## Imports

- en import web, afficher les indicateurs à l'utilisateur

## Stats

- catégories
    - revoir comment est calculé le total "non catégorisé"
- dashboard
    - créer une classe DashboardWidget
- pleins
    - consommation moyenne / plein
    - autonomie estimée / plein
    - prix du carburant / plein
    - coût en carburant / véhicule, au 100km
- comptes
    - dépenses moyennes / mois
