<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260528000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Drop campagne_mail, file_attente_campagne_mail and formule_campagne_mail tables (feature removed)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');
        $this->addSql('DROP TABLE IF EXISTS file_attente_campagne_mail');
        $this->addSql('DROP TABLE IF EXISTS campagne_mail');
        $this->addSql('DROP TABLE IF EXISTS formule_campagne_mail');
        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('SET FOREIGN_KEY_CHECKS=0');

        $this->addSql('CREATE TABLE formule_campagne_mail (
            id INT AUTO_INCREMENT NOT NULL,
            titre VARCHAR(255) NOT NULL,
            prix INT NOT NULL,
            nombre_mail INT NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE campagne_mail (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            formule_campagne_mail_id INT NOT NULL,
            titre LONGTEXT NOT NULL,
            sujet LONGTEXT NOT NULL,
            replyto LONGTEXT NOT NULL,
            sendto LONGTEXT NOT NULL,
            contentmail LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            status INT NOT NULL,
            motif LONGTEXT DEFAULT NULL,
            traitement TINYINT(1) NOT NULL,
            INDEX IDX_campagne_mail_user (user_id),
            INDEX IDX_campagne_mail_formule (formule_campagne_mail_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE file_attente_campagne_mail (
            id INT AUTO_INCREMENT NOT NULL,
            campagne_mail_id INT NOT NULL,
            titre LONGTEXT NOT NULL,
            sujet LONGTEXT NOT NULL,
            replyto LONGTEXT NOT NULL,
            sendto LONGTEXT NOT NULL,
            contentmail LONGTEXT NOT NULL,
            INDEX IDX_file_attente_campagne_mail (campagne_mail_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE campagne_mail
            ADD CONSTRAINT FK_campagne_mail_user FOREIGN KEY (user_id) REFERENCES user (id),
            ADD CONSTRAINT FK_campagne_mail_formule FOREIGN KEY (formule_campagne_mail_id) REFERENCES formule_campagne_mail (id)');

        $this->addSql('ALTER TABLE file_attente_campagne_mail
            ADD CONSTRAINT FK_file_attente_campagne_mail FOREIGN KEY (campagne_mail_id) REFERENCES campagne_mail (id)');

        $this->addSql('SET FOREIGN_KEY_CHECKS=1');
    }
}
