<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260611000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create file_attente_whatsapp table for WhatsApp confirmation queue';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE file_attente_whatsapp (
            id INT AUTO_INCREMENT NOT NULL,
            sendto VARCHAR(30) NOT NULL,
            titre LONGTEXT NOT NULL,
            message LONGTEXT NOT NULL,
            statut VARCHAR(50) NOT NULL DEFAULT \'en_attente\',
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE file_attente_whatsapp');
    }
}
