<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260805000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des champs nom_site_app, url_site_app, sous_type_site_app dans la table promotion (type Sites & Applications)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE promotion ADD nom_site_app VARCHAR(150) NULL, ADD url_site_app VARCHAR(500) NULL, ADD sous_type_site_app VARCHAR(50) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE promotion DROP COLUMN nom_site_app, DROP COLUMN url_site_app, DROP COLUMN sous_type_site_app');
    }
}
