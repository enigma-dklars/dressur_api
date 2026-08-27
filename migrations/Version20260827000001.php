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

        $rows = $this->connection->fetchAllAssociative(
            'SELECT restriction.id, u.tel, u.mail FROM user_restriction restriction INNER JOIN `user` u ON u.id = restriction.user_id'
        );
        foreach ($rows as $row) {
            $this->addSql(
                'UPDATE user_restriction SET identity_tel = ?, identity_mail = ? WHERE id = ?',
                [$this->normalizeTel($row['tel'] ?? null), $this->normalizeMail($row['mail'] ?? null), (int)$row['id']]
            );
        }

        $this->addSql('ALTER TABLE user_restriction DROP FOREIGN KEY FK_USER_RESTRICTION_USER');
        $this->addSql('ALTER TABLE user_restriction MODIFY user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_restriction ADD CONSTRAINT FK_USER_RESTRICTION_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_USER_RESTRICTION_IDENTITY_TEL ON user_restriction (identity_tel)');
        $this->addSql('CREATE INDEX IDX_USER_RESTRICTION_IDENTITY_MAIL ON user_restriction (identity_mail)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DELETE FROM user_restriction WHERE user_id IS NULL');
        $this->addSql('DROP INDEX IDX_USER_RESTRICTION_IDENTITY_TEL ON user_restriction');
        $this->addSql('DROP INDEX IDX_USER_RESTRICTION_IDENTITY_MAIL ON user_restriction');
        $this->addSql('ALTER TABLE user_restriction DROP FOREIGN KEY FK_USER_RESTRICTION_USER');
        $this->addSql('ALTER TABLE user_restriction MODIFY user_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_restriction ADD CONSTRAINT FK_USER_RESTRICTION_USER FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_restriction DROP COLUMN identity_tel, DROP COLUMN identity_mail');
    }

    private function normalizeTel(mixed $tel): ?string
    {
        $value = preg_replace('/[^0-9+]/', '', trim((string)$tel)) ?? '';
        if (str_starts_with($value, '00')) {
            $value = '+' . substr($value, 2);
        }
        return $value === '' || $value === '+' ? null : $value;
    }

    private function normalizeMail(mixed $mail): ?string
    {
        $value = strtolower(trim((string)$mail));
        return $value === '' ? null : $value;
    }
}
