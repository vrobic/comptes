# Comptes

_Comptes_ est une application web permettant de suivre ses comptes bancaires et dépenses de transport.

> You can't improve what you can't measure.

Basée sur ce principe, elle a pour but de proposer des outils d'analyse : statistiques et graphiques.

Son architecture Symfony2 la rend très modulable et son intérêt réside dans le fait que chacun peut l'adapter à ses besoins et définir de nouvelles statistiques.

## Fonctionnalités

- gestion et catégorisation de mouvements de comptes bancaires
- gestion de pleins de carburant d'un parc de véhicules
- statistiques : solde des comptes, dépenses par catégories, épargne mensuelle moyenne, consommation des véhicules, et bien d'autres
- tableau de bord personnalisable
- moteur d'import des relevés de comptes et des pleins de carburant (en ligne de commande ou par interface web)

## Installation

### Socle Symfony2

Ce paragraphe sera bref puisqu'il s'agit d'une installation Symfony2 classique.

1. Clôner le projet
2. Renseigner le fichier `app/config/parameters.yml.dist`
3. Configurer un hôte virtuel, modifier le fichier `hosts` puis redémarrer le serveur web
4. Installer _Composer_ et lancer l'installation des dépendances : `php composer.phar install`
5. Relier les assets au le répertoire `web` : `php app/console assets:install --symlink`

### Paramétrage de l'application

Ce paragraphe décrit les grandes étapes de la configuration. Les fichiers dont il est question ci-dessous sont généreusement documentés et vous apporteront le complément d'informations nécessaire.

#### Fixtures

Les fixtures sont les jeux de données nécessaires au fonctionnement de l'application. Elles contiennent la liste des comptes bancaires, des catégories de mouvements, des véhicules et des carburants. Pour vous permettre de les définir facilement, ces données sont regroupées dans le fichier `fixtures.yml` :

    src/ComptesBundle/Resources/config/fixtures.yml

Une fois les données saisies, vous pouvez créer la base de données et importer les fixtures :

    php app/console comptes:install --load-fixtures

L'application peut maintenant être ouverte dans un navigateur web !

#### Import des données

Le moteur d'import est capable de catégoriser automatiquement les mouvements importés. Mais puisque rien n'est magique, il requiert un rapide paramétrage dans `import.yml` :

    src/ComptesBundle/Resources/config/import.yml

Lors de l'import, le tableau `keywords` permettra d'associer tel ou tel mot clé à une catégorie de mouvement. Pour connaître l'identifiant des catégories, accéder à la page _Catégories_ depuis le menu de l'application.

Il vous faudra également modifier le tableau `handlers` pour ajuster les identifiants de comptes bancaires ou de véhicules, selon le même principe.

## Utilisation

### Import

Import des mouvements de comptes bancaires :

    php app/console comptes:import:mouvements filename handler

Import des pleins de carburant d'un parc de véhicules :

    php app/console comptes:import:pleins filename handler

où `filename` est le chemin du fichier et `handler` le service de prise en charge du fichier. En cas de doute, donner une valeur arbitraire à `handler` pour que le système vous liste les possibilités.

Le fichier que vous tentez d'importer n'est pas pris en charge ? La documentation `CONTRIBUTING.md` vous détaillera le développement d'un handler d'import.