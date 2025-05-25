<?php

declare(strict_types=1);

namespace App\Infrastructure\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250525131425 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Récupère le schéma de base de données des comptes (abandon des pleins)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            CREATE TABLE `categories` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `categorie_parente_id` int(11) DEFAULT NULL,
              `nom` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `rang` int(11) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `IDX_3AF346685CBD743C` (`categorie_parente_id`),
              CONSTRAINT `FK_3AF346685CBD743C` FOREIGN KEY (`categorie_parente_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE `comptes` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `nom` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `numero` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `banque` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `plafond` int(11) NOT NULL,
              `solde_initial` decimal(8,2) NOT NULL,
              `rang` int(11) DEFAULT NULL,
              `date_ouverture` date NOT NULL,
              `date_fermeture` date DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE `keywords` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `categorie_id` int(11) NOT NULL,
              `word` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `UNIQ_AA5FB55EC3F17511` (`word`),
              KEY `IDX_AA5FB55EBCF5E72D` (`categorie_id`),
              CONSTRAINT `FK_AA5FB55EBCF5E72D` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        SQL);

        $this->addSql(<<<SQL
            CREATE TABLE `mouvements` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `categorie_id` int(11) DEFAULT NULL,
              `compte_id` int(11) NOT NULL,
              `date` date NOT NULL,
              `montant` decimal(8,2) NOT NULL,
              `description` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              PRIMARY KEY (`id`),
              KEY `IDX_DA34835CBCF5E72D` (`categorie_id`),
              KEY `IDX_DA34835CF2C56620` (`compte_id`),
              CONSTRAINT `FK_DA34835CBCF5E72D` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`),
              CONSTRAINT `FK_DA34835CF2C56620` FOREIGN KEY (`compte_id`) REFERENCES `comptes` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            DROP TABLE IF EXISTS `categories`, `comptes`, `keywords`, `mouvements`;
        SQL);
    }
}
