<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260801000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Affiliation : add code_partenaire + partenaire relation on user, create affiliation_used table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD code_partenaire VARCHAR(8) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649_CODE_PARTENAIRE ON `user` (code_partenaire)');
        $this->addSql('ALTER TABLE `user` ADD partenaire_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_USER_PARTENAIRE FOREIGN KEY (partenaire_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_USER_PARTENAIRE ON `user` (partenaire_id)');
        $this->addSql("CREATE TABLE affiliation_used (id INT AUTO_INCREMENT NOT NULL, tel VARCHAR(255) NOT NULL, mail VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_USER_PARTENAIRE ON `user`');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_USER_PARTENAIRE');
        $this->addSql('ALTER TABLE `user` DROP COLUMN partenaire_id');
        $this->addSql('DROP INDEX UNIQ_8D93D649_CODE_PARTENAIRE ON `user`');
        $this->addSql('ALTER TABLE `user` DROP COLUMN code_partenaire');
        $this->addSql('DROP TABLE affiliation_used');
    }
}
