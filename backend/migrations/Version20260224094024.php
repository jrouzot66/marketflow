<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260224094024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add status column to offers (PostgreSQL) with default draft';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform),
            'This migration is intended for PostgreSQL.'
        );

        $this->addSql("ALTER TABLE offers ADD COLUMN status VARCHAR(32) NOT NULL DEFAULT 'draft'");
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform),
            'This migration is intended for PostgreSQL.'
        );

        $this->addSql('ALTER TABLE offers DROP COLUMN status');
    }
}
