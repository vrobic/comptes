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

    src/Comptes/Bundle/CoreBundle/Resources/config/fixtures.yml

Une fois les données saisies, vous pouvez créer la base de données et importer les fixtures :

    php app/console comptes:install --load-fixtures

L'application peut maintenant être ouverte dans un navigateur web !

#### Import des données

Le moteur d'import est capable de catégoriser automatiquement les mouvements importés. Mais puisque rien n'est magique, il requiert un rapide paramétrage dans `import.yml` :

    src/Comptes/Bundle/CoreBundle/Resources/config/import.yml

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

Symfony Standard Edition
========================

Welcome to the Symfony Standard Edition - a fully-functional Symfony2
application that you can use as the skeleton for your new applications.

This document contains information on how to download, install, and start
using Symfony. For a more detailed explanation, see the [Installation][1]
chapter of the Symfony Documentation.

1) Installing the Standard Edition
----------------------------------

When it comes to installing the Symfony Standard Edition, you have the
following options.

### Use Composer (*recommended*)

As Symfony uses [Composer][2] to manage its dependencies, the recommended way
to create a new project is to use it.

If you don't have Composer yet, download it following the instructions on
http://getcomposer.org/ or just run the following command:

    curl -s http://getcomposer.org/installer | php

Then, use the `create-project` command to generate a new Symfony application:

    php composer.phar create-project symfony/framework-standard-edition path/to/install

Composer will install Symfony and all its dependencies under the
`path/to/install` directory.

### Download an Archive File

To quickly test Symfony, you can also download an [archive][3] of the Standard
Edition and unpack it somewhere under your web server root directory.

If you downloaded an archive "without vendors", you also need to install all
the necessary dependencies. Download composer (see above) and run the
following command:

    php composer.phar install

2) Checking your System Configuration
-------------------------------------

Before starting coding, make sure that your local system is properly
configured for Symfony.

Execute the `check.php` script from the command line:

    php app/check.php

The script returns a status code of `0` if all mandatory requirements are met,
`1` otherwise.

Access the `config.php` script from a browser:

    http://localhost/path/to/symfony/app/web/config.php

If you get any warnings or recommendations, fix them before moving on.

3) Browsing the Demo Application
--------------------------------

Congratulations! You're now ready to use Symfony.

From the `config.php` page, click the "Bypass configuration and go to the
Welcome page" link to load up your first Symfony page.

You can also use a web-based configurator by clicking on the "Configure your
Symfony Application online" link of the `config.php` page.

To see a real-live Symfony page in action, access the following page:

    web/app_dev.php/demo/hello/Fabien

4) Getting started with Symfony
-------------------------------

This distribution is meant to be the starting point for your Symfony
applications, but it also contains some sample code that you can learn from
and play with.

A great way to start learning Symfony is via the [Quick Tour][4], which will
take you through all the basic features of Symfony2.

Once you're feeling good, you can move onto reading the official
[Symfony2 book][5].

A default bundle, `AcmeDemoBundle`, shows you Symfony2 in action. After
playing with it, you can remove it by following these steps:

  * delete the `src/Acme` directory;

  * remove the routing entry referencing AcmeDemoBundle in `app/config/routing_dev.yml`;

  * remove the AcmeDemoBundle from the registered bundles in `app/AppKernel.php`;

  * remove the `web/bundles/acmedemo` directory;

  * remove the `security.providers`, `security.firewalls.login` and
    `security.firewalls.secured_area` entries in the `security.yml` file or
    tweak the security configuration to fit your needs.

What's inside?
---------------

The Symfony Standard Edition is configured with the following defaults:

  * Twig is the only configured template engine;

  * Doctrine ORM/DBAL is configured;

  * Swiftmailer is configured;

  * Annotations for everything are enabled.

It comes pre-configured with the following bundles:

  * **FrameworkBundle** - The core Symfony framework bundle

  * [**SensioFrameworkExtraBundle**][6] - Adds several enhancements, including
    template and routing annotation capability

  * [**DoctrineBundle**][7] - Adds support for the Doctrine ORM

  * [**TwigBundle**][8] - Adds support for the Twig templating engine

  * [**SecurityBundle**][9] - Adds security by integrating Symfony's security
    component

  * [**SwiftmailerBundle**][10] - Adds support for Swiftmailer, a library for
    sending emails

  * [**MonologBundle**][11] - Adds support for Monolog, a logging library

  * [**AsseticBundle**][12] - Adds support for Assetic, an asset processing
    library

  * **WebProfilerBundle** (in dev/test env) - Adds profiling functionality and
    the web debug toolbar

  * **SensioDistributionBundle** (in dev/test env) - Adds functionality for
    configuring and working with Symfony distributions

  * [**SensioGeneratorBundle**][13] (in dev/test env) - Adds code generation
    capabilities

  * **AcmeDemoBundle** (in dev/test env) - A demo bundle with some example
    code

All libraries and bundles included in the Symfony Standard Edition are
released under the MIT or BSD license.

Enjoy!

[1]:  http://symfony.com/doc/2.3/book/installation.html
[2]:  http://getcomposer.org/
[3]:  http://symfony.com/download
[4]:  http://symfony.com/doc/2.3/quick_tour/the_big_picture.html
[5]:  http://symfony.com/doc/2.3/index.html
[6]:  http://symfony.com/doc/2.3/bundles/SensioFrameworkExtraBundle/index.html
[7]:  http://symfony.com/doc/2.3/book/doctrine.html
[8]:  http://symfony.com/doc/2.3/book/templating.html
[9]:  http://symfony.com/doc/2.3/book/security.html
[10]: http://symfony.com/doc/2.3/cookbook/email.html
[11]: http://symfony.com/doc/2.3/cookbook/logging/monolog.html
[12]: http://symfony.com/doc/2.3/cookbook/assetic/asset_management.html
[13]: http://symfony.com/doc/2.3/bundles/SensioGeneratorBundle/index.html
