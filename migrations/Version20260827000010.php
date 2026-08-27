<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000010 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create developer API keys';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE developer_api_key (id INT AUTO_INCREMENT NOT NULL, developer_profile_id INT NOT NULL, key_id VARCHAR(80) NOT NULL, secret_hash VARCHAR(255) NOT NULL, secret_prefix VARCHAR(24) NOT NULL, label VARCHAR(120) NOT NULL, scopes JSON NOT NULL, created_at DATETIME NOT NULL, last_used_at DATETIME DEFAULT NULL, expires_at DATETIME DEFAULT NULL, revoked_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('CREATE UNIQUE INDEX UNIQ_DEVELOPER_API_KEY_KEY_ID ON developer_api_key (key_id)');
        $this->addSql('CREATE INDEX IDX_DEVELOPER_API_KEY_PROFILE ON developer_api_key (developer_profile_id)');
        $this->addSql('ALTER TABLE developer_api_key ADD CONSTRAINT FK_DEVELOPER_API_KEY_PROFILE FOREIGN KEY (developer_profile_id) REFERENCES developer_profile (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE developer_api_key DROP FOREIGN KEY FK_DEVELOPER_API_KEY_PROFILE');
        $this->addSql('DROP TABLE developer_api_key');
    }
}
