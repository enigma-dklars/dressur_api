<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create developer API idempotency records';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE developer_idempotency (id SERIAL NOT NULL, developer_profile_id INT NOT NULL, idempotency_key VARCHAR(160) NOT NULL, request_hash VARCHAR(64) NOT NULL, response_body JSON NOT NULL, response_status INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEVELOPER_IDEMPOTENCY_PROFILE_KEY ON developer_idempotency (developer_profile_id, idempotency_key)');
        $this->addSql('ALTER TABLE developer_idempotency ADD CONSTRAINT FK_DEVELOPER_IDEMPOTENCY_PROFILE FOREIGN KEY (developer_profile_id) REFERENCES developer_profile (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE developer_idempotency DROP CONSTRAINT FK_DEVELOPER_IDEMPOTENCY_PROFILE');
        $this->addSql('DROP TABLE developer_idempotency');
    }
}
