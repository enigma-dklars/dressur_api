<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260618000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add typeBoost, nbContactsObtenus to boost table and make dateExp nullable';
    }
    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE boost MODIFY date_exp DATETIME DEFAULT NULL');
        $this->addSql("ALTER TABLE boost ADD type_boost VARCHAR(10) NOT NULL DEFAULT 'date'");
        $this->addSql('ALTER TABLE boost ADD nb_contacts_obtenus INT NOT NULL DEFAULT 0');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE boost DROP COLUMN nb_contacts_obtenus');
        $this->addSql('ALTER TABLE boost DROP COLUMN type_boost');
        $this->addSql('ALTER TABLE boost MODIFY date_exp DATETIME NOT NULL');
    }
}
