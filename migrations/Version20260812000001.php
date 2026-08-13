<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260812000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user social networks table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_social_network (id SERIAL NOT NULL, user_id INT NOT NULL, network_type VARCHAR(50) NOT NULL, url VARCHAR(500) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_USER_SOCIAL_NETWORK_USER ON user_social_network (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_SOCIAL_NETWORK_USER_NETWORK_TYPE ON user_social_network (user_id, network_type)');
        $this->addSql('ALTER TABLE user_social_network ADD CONSTRAINT FK_USER_SOCIAL_NETWORK_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_social_network');
    }
}