# Comptes

_Comptes_ est une application web permettant de suivre ses comptes bancaires en proposant des outils d'analyse.

Elle a été créée en 2014 sur un socle [Symfony 2](https://symfony.com/releases/2.8) en MVC avec Doctrine et Twig, une architecture populaire à l'époque.

En plus des comptes bancaires, elle permettait également de suivre ses dépenses de transport.
Cette fonctionnalité a été abandonnée lors de la refonte de 2025, qui a passé le projet sur [Symfony 7](https://symfony.com/7.2), avec une architecture hexagonale en DDD.

L'infrastructure repose désormais sur [Symfony Docker](https://github.com/dunglas/symfony-docker).

## ✨ Fonctionnalités

* gestion et catégorisation de mouvements de comptes bancaires
* statistiques : solde des comptes, dépenses par catégories, épargne mensuelle moyenne
* import de relevés de compte depuis un export bancaire

🚧 À venir :

* poursuite de la migration DDD et architecture hexagonale
* simplification de la configuration d'import
* recherche de mouvements
* recatégorisation en masse
* création, modification et suppression de comptes

## 📦️ Installation

```
make install
make up
```

Pour éteindre la stack :

```
make down
```

## ⚙️ Paramétrage

### Création des comptes

Puisqu'il n'existe pas encore d'interface pour administrer les comptes, il faut les créer directement en base de données.

### Moteur d'import

En attendant que sa configuration soit simplifiée, le moteur d'import requiert un rapide paramétrage dans `config/comptes.yaml`. Modifier le tableau `handlers` pour ajuster les identifiants de comptes bancaires.

### Catégories

Le moteur d'import est capable de catégoriser automatiquement les mouvements importés, en analysant les mots-clés contenus dans leur description. Pour bénéficier de cette fonctionnalité, se rendre sur la page d'édition des catégories et renseigner les mots-clés associés à chaque catégorie de mouvement.

## 🚀 Accès

https://localhost

Le certificat SSL étant auto-signé, il est normal d'avoir une alerte de confidentialité.

## 💼️ Portabilité des données

### Export

Génère un fichier `~/Downloads/comptes.sql` :

```
make export-bdd
```

### Import

Importe un fichier `~/Downloads/comptes.sql` :

```
make import-bdd
```
