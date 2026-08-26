<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create per-user administrator restrictions';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE user_restriction (id SERIAL NOT NULL, user_id INT NOT NULL, type VARCHAR(50) NOT NULL, minimum_transaction_amount INT DEFAULT NULL, reason TEXT DEFAULT NULL, active BOOLEAN NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_USER_RESTRICTION_USER ON user_restriction (user_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_RESTRICTION_USER_TYPE ON user_restriction (user_id, type)');
        $this->addSql('ALTER TABLE user_restriction ADD CONSTRAINT FK_USER_RESTRICTION_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE user_restriction');
    }
}
