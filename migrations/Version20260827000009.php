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
        $this->addSql("CREATE TABLE developer_profile (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, status VARCHAR(20) NOT NULL DEFAULT 'pending', conditions_version VARCHAR(30) DEFAULT NULL, conditions_accepted_at DATETIME DEFAULT NULL, activation_transaction_reference VARCHAR(255) DEFAULT NULL, activation_amount INT DEFAULT NULL, activated_at DATETIME DEFAULT NULL, suspended_at DATETIME DEFAULT NULL, revoked_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEVELOPER_PROFILE_USER ON developer_profile (user_id)');
        $this->addSql('ALTER TABLE developer_profile ADD CONSTRAINT FK_DEVELOPER_PROFILE_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE developer_profile DROP FOREIGN KEY FK_DEVELOPER_PROFILE_USER');
        $this->addSql('DROP TABLE developer_profile');
    }
}
