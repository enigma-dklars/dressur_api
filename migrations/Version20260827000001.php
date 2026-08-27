<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000001 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Keep user restriction identities after account deletion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_restriction ADD identity_tel VARCHAR(255) DEFAULT NULL, ADD identity_mail VARCHAR(255) DEFAULT NULL');
        $this->addSql("WITH normalized_users AS (SELECT id, regexp_replace(COALESCE(tel, ''), '[^0-9+]', '', 'g') AS raw_tel, NULLIF(lower(trim(mail)), '') AS normalized_mail FROM \"user\") UPDATE user_restriction restriction SET identity_tel = NULLIF(CASE WHEN normalized_users.raw_tel LIKE '00%' THEN '+' || substring(normalized_users.raw_tel FROM 3) ELSE normalized_users.raw_tel END, ''), identity_mail = normalized_users.normalized_mail FROM normalized_users WHERE restriction.user_id = normalized_users.id");
        $this->addSql('ALTER TABLE user_restriction DROP CONSTRAINT FK_USER_RESTRICTION_USER');
        $this->addSql('ALTER TABLE user_restriction ALTER COLUMN user_id DROP NOT NULL');
        $this->addSql('ALTER TABLE user_restriction ADD CONSTRAINT FK_USER_RESTRICTION_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_USER_RESTRICTION_IDENTITY_TEL ON user_restriction (identity_tel)');
        $this->addSql('CREATE INDEX IDX_USER_RESTRICTION_IDENTITY_MAIL ON user_restriction (identity_mail)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM user_restriction WHERE user_id IS NULL');
        $this->addSql('DROP INDEX IDX_USER_RESTRICTION_IDENTITY_TEL');
        $this->addSql('DROP INDEX IDX_USER_RESTRICTION_IDENTITY_MAIL');
        $this->addSql('ALTER TABLE user_restriction DROP CONSTRAINT FK_USER_RESTRICTION_USER');
        $this->addSql('ALTER TABLE user_restriction ALTER COLUMN user_id SET NOT NULL');
        $this->addSql('ALTER TABLE user_restriction ADD CONSTRAINT FK_USER_RESTRICTION_USER FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_restriction DROP identity_tel, DROP identity_mail');
    }
}
