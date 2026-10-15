<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261012015709 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE special_event (id BLOB NOT NULL, name VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, event_description CLOB NOT NULL, dates CLOB NOT NULL, price DOUBLE PRECISION NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE TABLE ticket (id BLOB NOT NULL, confirmation_code VARCHAR(32) NOT NULL, ticket_type VARCHAR(255) NOT NULL, ticket_date DATE NOT NULL, email VARCHAR(255) DEFAULT NULL, event_id BLOB DEFAULT NULL, PRIMARY KEY (id), CONSTRAINT FK_97A0ADA371F7E88B FOREIGN KEY (event_id) REFERENCES special_event (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_97A0ADA3A0E239DE ON ticket (confirmation_code)');
        $this->addSql('CREATE INDEX IDX_97A0ADA371F7E88B ON ticket (event_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE special_event');
        $this->addSql('DROP TABLE ticket');
    }
}
