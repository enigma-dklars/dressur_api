<?php
declare(strict_types=1);
namespace DoctrineMigrations;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
final class Version20260618000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Insert new FormuleBoost records of type quota (free and paid)';
    }
    public function up(Schema $schema): void
    {
        // Boost Gratuit — quota 20 contacts
        $this->addSql("
            INSERT INTO formule_boost (titre, prix, nbr_jour, alert, activated, type_boost, nb_contacts_max)
            VALUES ('Boost Gratuit - 20 contacts', 0, 0, 0, 1, 'quota', 20)
        ");
        // Boost Payant — quota 10 contacts
        $this->addSql("
            INSERT INTO formule_boost (titre, prix, nbr_jour, alert, activated, type_boost, nb_contacts_max)
            VALUES ('Boost Contact - 10 contacts', 500, 0, 0, 1, 'quota', 10)
        ");
        // Boost Payant — quota 25 contacts
        $this->addSql("
            INSERT INTO formule_boost (titre, prix, nbr_jour, alert, activated, type_boost, nb_contacts_max)
            VALUES ('Boost Contact - 25 contacts', 1000, 0, 0, 1, 'quota', 25)
        ");
        // Boost Payant — quota 60 contacts
        $this->addSql("
            INSERT INTO formule_boost (titre, prix, nbr_jour, alert, activated, type_boost, nb_contacts_max)
            VALUES ('Boost Contact - 60 contacts', 2000, 0, 0, 1, 'quota', 60)
        ");
    }
    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM formule_boost WHERE type_boost = 'quota'");
    }
}
