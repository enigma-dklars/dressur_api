<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create story table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE story (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT DEFAULT NULL,
            images LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\',
            videos LONGTEXT DEFAULT NULL COMMENT \'(DC2Type:json)\',
            url VARCHAR(255) DEFAULT NULL,
            description LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_EB560438A76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE story ADD CONSTRAINT FK_EB560438A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE story DROP FOREIGN KEY FK_EB560438A76ED395');
        $this->addSql('DROP TABLE story');
    }
}
