<?php

declare(strict_types=1);

namespace App\Infrastructure\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250708155016 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Le plafond des comptes bancaires devient nullable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<SQL
            ALTER TABLE comptes MODIFY plafond INT NULL;
            UPDATE comptes SET plafond = NULL WHERE plafond = 0;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql(<<<SQL
            UPDATE comptes SET plafond = 0 WHERE plafond IS NULL;
            ALTER TABLE comptes MODIFY plafond INT NOT NULL;
        SQL);
    }
}
