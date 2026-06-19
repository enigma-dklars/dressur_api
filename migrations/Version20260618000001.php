<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260618000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add typeBoost and nbContactsMax to formule_boost table';
    }
    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE formule_boost ADD type_boost VARCHAR(10) NOT NULL DEFAULT 'date'");
        $this->addSql('ALTER TABLE formule_boost ADD nb_contacts_max INT DEFAULT NULL');
    }
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formule_boost DROP COLUMN nb_contacts_max');
        $this->addSql('ALTER TABLE formule_boost DROP COLUMN type_boost');
    }
}
