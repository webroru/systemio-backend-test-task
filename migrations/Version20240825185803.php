<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240825185803 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE coupon ADD type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE coupon DROP discount_percent');
        $this->addSql('ALTER TABLE coupon RENAME COLUMN discount_amount TO value');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE coupon ADD discount_percent NUMERIC(5, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE coupon DROP type');
        $this->addSql('ALTER TABLE coupon RENAME COLUMN value TO discount_amount');
    }
}
