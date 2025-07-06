<?php

declare(strict_types=1);

namespace App\Infrastructure\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250706133200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Les identifiers integer deviennent des UUID';
    }

    public function up(Schema $schema): void
    {
        // Ajoute les colonnes uuid
        $this->addSql(<<<SQL
            ALTER TABLE categories
                ADD COLUMN uuid CHAR(36) NULL FIRST,
                ADD COLUMN categorie_parente_uuid CHAR(36) NULL AFTER uuid;
            ALTER TABLE comptes ADD COLUMN uuid CHAR(36) NULL FIRST;
            ALTER TABLE keywords
                ADD COLUMN uuid CHAR(36) NULL FIRST,
                ADD COLUMN categorie_uuid CHAR(36) NULL AFTER uuid;
            ALTER TABLE mouvements
                ADD COLUMN uuid CHAR(36) NULL FIRST,
                ADD COLUMN categorie_uuid CHAR(36) NULL AFTER uuid,
                ADD COLUMN compte_uuid CHAR(36) NOT NULL AFTER categorie_uuid;
        SQL);

        // Remplit les uuid
        $this->addSql(<<<SQL
            UPDATE categories SET uuid = (SELECT UUID());
            UPDATE comptes SET uuid = (SELECT UUID());
            UPDATE keywords SET uuid = (SELECT UUID());
            UPDATE mouvements SET uuid = (SELECT UUID());
        SQL);

        // Recopie les relations des colonnes id vers uuid
        $this->addSql(<<<SQL
            UPDATE categories c
            JOIN categories parent ON c.categorie_parente_id = parent.id
            SET c.categorie_parente_uuid = parent.uuid;

            UPDATE keywords k
            JOIN categories c ON k.categorie_id = c.id
            SET k.categorie_uuid = c.uuid;

            UPDATE mouvements m
            JOIN categories c ON m.categorie_id = c.id
            SET m.categorie_uuid = c.uuid;

            UPDATE mouvements m
            JOIN comptes c ON m.compte_id = c.id
            SET m.compte_uuid = c.uuid;
        SQL);

        // Supprime les clés sur les colonnes id
        $this->addSql(<<<SQL
            ALTER TABLE categories DROP FOREIGN KEY FK_3AF346685CBD743C;
            ALTER TABLE keywords DROP FOREIGN KEY FK_AA5FB55EBCF5E72D;
            ALTER TABLE mouvements DROP FOREIGN KEY FK_DA34835CBCF5E72D;
            ALTER TABLE mouvements DROP FOREIGN KEY FK_DA34835CF2C56620;
        SQL);

        // Supprime les colonnes id
        $this->addSql(<<<SQL
            ALTER TABLE categories
                DROP COLUMN id,
                DROP COLUMN categorie_parente_id;
            ALTER TABLE comptes DROP COLUMN id;
            ALTER TABLE keywords
                DROP COLUMN id,
                DROP COLUMN categorie_id;
            ALTER TABLE mouvements
                DROP COLUMN id,
                DROP COLUMN categorie_id,
                DROP COLUMN compte_id;
        SQL);

        // Renomme les colonnes uuid en id et les définit comme clés primaires
        $this->addSql(<<<SQL
            ALTER TABLE categories
                CHANGE uuid id CHAR(36) NOT NULL PRIMARY KEY,
                CHANGE categorie_parente_uuid categorie_parente_id CHAR(36);
            ALTER TABLE comptes CHANGE uuid id CHAR(36) NOT NULL PRIMARY KEY;
            ALTER TABLE keywords
                CHANGE uuid id CHAR(36) NOT NULL PRIMARY KEY,
                CHANGE categorie_uuid categorie_id CHAR(36) NOT NULL;
            ALTER TABLE mouvements
                CHANGE uuid id CHAR(36) NOT NULL PRIMARY KEY,
                CHANGE categorie_uuid categorie_id CHAR(36),
                CHANGE compte_uuid compte_id CHAR(36) NOT NULL;
        SQL);

        // Recrée les clés
        $this->addSql(<<<SQL
            ALTER TABLE categories
                ADD KEY IDX_3AF346685CBD743C (categorie_parente_id),
                ADD CONSTRAINT FK_3AF346685CBD743C FOREIGN KEY (categorie_parente_id) REFERENCES categories(id) ON DELETE SET NULL;
            ALTER TABLE keywords
                ADD KEY IDX_AA5FB55EBCF5E72D (categorie_id),
                ADD CONSTRAINT FK_AA5FB55EBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories(id);
            ALTER TABLE mouvements
                ADD KEY IDX_DA34835CBCF5E72D (categorie_id),
                ADD KEY IDX_DA34835CF2C56620 (compte_id),
                ADD CONSTRAINT FK_DA34835CBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories(id),
                ADD CONSTRAINT FK_DA34835CF2C56620 FOREIGN KEY (compte_id) REFERENCES comptes(id);
        SQL);
    }

    public function down(Schema $schema): void
    {
        // Remet les colonnes id
        $this->addSql(<<<SQL
            ALTER TABLE categories
                ADD COLUMN old_id INT NOT NULL FIRST,
                ADD COLUMN old_categorie_parente_id INT DEFAULT NULL AFTER old_id;
            ALTER TABLE comptes ADD COLUMN old_id INT NOT NULL FIRST;
            ALTER TABLE keywords
                ADD COLUMN old_id INT NOT NULL FIRST,
                ADD COLUMN old_categorie_id INT NOT NULL AFTER old_id;
            ALTER TABLE mouvements
                ADD COLUMN old_id INT NOT NULL FIRST,
                ADD COLUMN old_categorie_id INT DEFAULT NULL AFTER old_id,
                ADD COLUMN old_compte_id INT NOT NULL AFTER old_categorie_id;
        SQL);

        // Remplit les id
        $this->addSql(<<<SQL
            SET @categorie_id = 0;
            UPDATE categories SET old_id = (@categorie_id := @categorie_id + 1) ORDER BY nom;

            SET @compte_id = 0;
            UPDATE comptes SET old_id = (@compte_id := @compte_id + 1) ORDER BY date_ouverture;

            SET @keyword_id = 0;
            UPDATE keywords SET old_id = (@keyword_id := @keyword_id + 1) ORDER BY word;

            SET @mouvement_id = 0;
            UPDATE mouvements SET old_id = (@mouvement_id := @mouvement_id + 1) ORDER BY date;
        SQL);

        // Recrée les relations des colonnes uuid vers id
        $this->addSql(<<<SQL
            UPDATE categories c
            JOIN categories p ON c.categorie_parente_id = p.id
            SET c.old_categorie_parente_id = p.old_id;

            UPDATE keywords k
            JOIN categories c ON k.categorie_id = c.id
            SET k.old_categorie_id = c.old_id;

            UPDATE mouvements m
            JOIN categories c ON m.categorie_id = c.id
            SET m.old_categorie_id = c.old_id;

            UPDATE mouvements m
            JOIN comptes c ON m.compte_id = c.id
            SET m.old_compte_id = c.old_id;
        SQL);

        // Supprime les clés sur les colonnes uuid
        $this->addSql(<<<SQL
            ALTER TABLE categories DROP FOREIGN KEY FK_3AF346685CBD743C;
            ALTER TABLE keywords DROP FOREIGN KEY FK_AA5FB55EBCF5E72D;
            ALTER TABLE mouvements DROP FOREIGN KEY FK_DA34835CBCF5E72D;
            ALTER TABLE mouvements DROP FOREIGN KEY FK_DA34835CF2C56620;
        SQL);

        // Supprime les colonnes uuid
        $this->addSql(<<<SQL
            ALTER TABLE categories
                DROP COLUMN id,
                DROP COLUMN categorie_parente_id;
            ALTER TABLE comptes DROP COLUMN id;
            ALTER TABLE keywords
                DROP COLUMN id,
                DROP COLUMN categorie_id;
            ALTER TABLE mouvements
                DROP COLUMN id,
                DROP COLUMN categorie_id,
                DROP COLUMN compte_id;
        SQL);

        // Supprime le préfixe old_ des colonnes id et les définit comme clés primaires
        $this->addSql(<<<SQL
            ALTER TABLE categories
                CHANGE old_id id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
                CHANGE old_categorie_parente_id categorie_parente_id INT DEFAULT NULL;
            ALTER TABLE comptes CHANGE old_id id INT AUTO_INCREMENT NOT NULL PRIMARY KEY;
            ALTER TABLE keywords
                CHANGE old_id id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
                CHANGE old_categorie_id categorie_id INT NOT NULL;
            ALTER TABLE mouvements
                CHANGE old_id id INT AUTO_INCREMENT NOT NULL PRIMARY KEY,
                CHANGE old_categorie_id categorie_id INT DEFAULT NULL,
                CHANGE old_compte_id compte_id INT NOT NULL;
        SQL);

        // Recrée les clés
        $this->addSql(<<<SQL
            ALTER TABLE categories
                ADD KEY IDX_3AF346685CBD743C (categorie_parente_id),
                ADD CONSTRAINT FK_3AF346685CBD743C FOREIGN KEY (categorie_parente_id) REFERENCES categories(id) ON DELETE SET NULL;
            ALTER TABLE keywords
                ADD KEY IDX_AA5FB55EBCF5E72D (categorie_id),
                ADD CONSTRAINT FK_AA5FB55EBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories(id);
            ALTER TABLE mouvements
                ADD KEY IDX_DA34835CBCF5E72D (categorie_id),
                ADD KEY IDX_DA34835CF2C56620 (compte_id),
                ADD CONSTRAINT FK_DA34835CBCF5E72D FOREIGN KEY (categorie_id) REFERENCES categories(id),
                ADD CONSTRAINT FK_DA34835CF2C56620 FOREIGN KEY (compte_id) REFERENCES comptes(id);
        SQL);
    }
}
