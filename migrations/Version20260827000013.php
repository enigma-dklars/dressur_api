<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000013 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create developer API audit log';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE developer_api_audit_log (id INT AUTO_INCREMENT NOT NULL, developer_profile_id INT NOT NULL, key_id VARCHAR(40) NOT NULL, endpoint VARCHAR(160) NOT NULL, method VARCHAR(10) NOT NULL, response_status INT NOT NULL, ip_address VARCHAR(64) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('CREATE INDEX IDX_DEVELOPER_API_AUDIT_PROFILE_CREATED ON developer_api_audit_log (developer_profile_id, created_at)');
        $this->addSql('ALTER TABLE developer_api_audit_log ADD CONSTRAINT FK_DEVELOPER_API_AUDIT_PROFILE FOREIGN KEY (developer_profile_id) REFERENCES developer_profile (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE developer_api_audit_log DROP FOREIGN KEY FK_DEVELOPER_API_AUDIT_PROFILE');
        $this->addSql('DROP INDEX IDX_DEVELOPER_API_AUDIT_PROFILE_CREATED ON developer_api_audit_log');
        $this->addSql('DROP TABLE developer_api_audit_log');
    }
}
