<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move banned users from Env array to UserBanned entity';
    }

    public function up(Schema $schema): void
    {
        $legacyValue = $this->connection->fetchOne('SELECT user_banned FROM env WHERE id = 1');
        $legacyValues = [];
        if (is_string($legacyValue) && $legacyValue !== '') {
            $decoded = @unserialize($legacyValue, ['allowed_classes' => false]);
            if (is_array($decoded)) {
                $legacyValues = array_values($decoded);
            }
        }

        $this->addSql('CREATE TABLE user_banned (id INT AUTO_INCREMENT NOT NULL, tel VARCHAR(255) DEFAULT NULL, mail VARCHAR(255) DEFAULT NULL, motif TEXT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE INDEX IDX_USER_BANNED_TEL ON user_banned (tel)');
        $this->addSql('CREATE INDEX IDX_USER_BANNED_MAIL ON user_banned (mail)');

        for ($index = 0, $length = count($legacyValues); $index + 2 < $length; $index += 3) {
            $tel = $this->normalizeTel($legacyValues[$index] ?? null);
            $mail = $this->normalizeMail($legacyValues[$index + 1] ?? null);
            $motif = trim((string) ($legacyValues[$index + 2] ?? ''));
            $this->addSql(
                'INSERT INTO user_banned (tel, mail, motif, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)',
                [$tel, $mail, $motif]
            );
        }

        $this->addSql('ALTER TABLE env DROP COLUMN user_banned');
    }

    public function down(Schema $schema): void
    {
        $bannedRows = $this->connection->fetchAllAssociative('SELECT tel, mail, motif FROM user_banned ORDER BY created_at ASC, id ASC');
        $legacyValues = [];
        foreach ($bannedRows as $row) {
            $legacyValues[] = $row['tel'];
            $legacyValues[] = $row['mail'];
            $legacyValues[] = $row['motif'];
        }

        $this->addSql('ALTER TABLE env ADD user_banned TEXT DEFAULT NULL');
        $this->addSql('UPDATE env SET user_banned = ? WHERE id = 1', [serialize($legacyValues)]);
        $this->addSql('DROP TABLE user_banned');
    }

    private function normalizeTel(mixed $tel): ?string
    {
        $value = trim((string) $tel);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('/[^0-9+]/', '', $value) ?? '';
        if (str_starts_with($value, '00')) {
            $value = '+' . substr($value, 2);
        }

        return $value === '' || $value === '+' ? null : $value;
    }

    private function normalizeMail(mixed $mail): ?string
    {
        $value = strtolower(trim((string) $mail));
        return $value === '' ? null : $value;
    }
}
