<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create chat_message table for AI assistant conversation history';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE chat_message (
                id INT AUTO_INCREMENT NOT NULL,
                user_id INT NOT NULL,
                role VARCHAR(20) NOT NULL,
                content LONGTEXT NOT NULL,
                created_at DATETIME NOT NULL,
                INDEX IDX_FAB3FC16A76ED395 (user_id),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
        $this->addSql('
            ALTER TABLE chat_message
            ADD CONSTRAINT FK_FAB3FC16A76ED395
            FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE chat_message DROP FOREIGN KEY FK_FAB3FC16A76ED395');
        $this->addSql('DROP TABLE chat_message');
    }
}
