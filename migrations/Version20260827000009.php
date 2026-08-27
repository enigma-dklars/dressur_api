<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000009 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the developer profile table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE developer_profile (id SERIAL NOT NULL, user_id INT NOT NULL, status VARCHAR(20) DEFAULT \'pending\' NOT NULL, conditions_version VARCHAR(30) DEFAULT NULL, conditions_accepted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, activation_transaction_reference VARCHAR(255) DEFAULT NULL, activation_amount INT DEFAULT NULL, activated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, suspended_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, revoked_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEVELOPER_PROFILE_USER ON developer_profile (user_id)');
        $this->addSql('ALTER TABLE developer_profile ADD CONSTRAINT FK_DEVELOPER_PROFILE_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE developer_profile DROP CONSTRAINT FK_DEVELOPER_PROFILE_USER');
        $this->addSql('DROP TABLE developer_profile');
    }
}
