<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260827000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Move used phone and email identities out of Env';
    }

    public function up(Schema $schema): void
    {
        $legacyValue = $this->connection->fetchOne('SELECT users_tel FROM env WHERE id = 1');
        $legacyValues = [];
        if (is_string($legacyValue) && $legacyValue !== '') {
            $decoded = @unserialize($legacyValue, ['allowed_classes' => false]);
            if (is_array($decoded)) {
                $legacyValues = array_values($decoded);
            }
        }

        $this->addSql('CREATE TABLE user_used_identity (id SERIAL NOT NULL, type VARCHAR(10) NOT NULL, value VARCHAR(255) NOT NULL, first_used_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, last_used_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_USED_IDENTITY_TYPE_VALUE ON user_used_identity (type, value)');

        $this->addSql("WITH normalized_users AS (SELECT regexp_replace(COALESCE(tel, ''), '[^0-9+]', '', 'g') AS raw_tel, created_at FROM \"user\") INSERT INTO user_used_identity (type, value, first_used_at, last_used_at) SELECT 'tel', CASE WHEN raw_tel LIKE '00%' THEN '+' || substring(raw_tel FROM 3) ELSE raw_tel END, COALESCE(created_at, CURRENT_TIMESTAMP), CURRENT_TIMESTAMP FROM normalized_users WHERE raw_tel <> '' ON CONFLICT (type, value) DO NOTHING");
        $this->addSql("INSERT INTO user_used_identity (type, value, first_used_at, last_used_at) SELECT 'mail', lower(trim(mail)), COALESCE(created_at, CURRENT_TIMESTAMP), CURRENT_TIMESTAMP FROM \"user\" WHERE mail IS NOT NULL AND trim(mail) <> '' ON CONFLICT (type, value) DO NOTHING");

        $seen = [];
        foreach ($legacyValues as $legacyValue) {
            $value = trim((string) $legacyValue);
            if ($value === '') {
                continue;
            }

            $type = filter_var($value, FILTER_VALIDATE_EMAIL) ? 'mail' : 'tel';
            $normalized = $type === 'mail' ? strtolower($value) : $this->normalizeTel($value);
            if ($normalized === null || isset($seen[$type . ':' . $normalized])) {
                continue;
            }
            $seen[$type . ':' . $normalized] = true;
            $this->addSql(
                'INSERT INTO user_used_identity (type, value, first_used_at, last_used_at) VALUES (?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP) ON CONFLICT (type, value) DO NOTHING',
                [$type, $normalized]
            );
        }

        $this->addSql('ALTER TABLE env DROP COLUMN users_tel');
    }

    public function down(Schema $schema): void
    {
        $usedRows = $this->connection->fetchAllAssociative("SELECT value FROM user_used_identity WHERE type = 'tel' ORDER BY first_used_at ASC, id ASC");
        $legacyValues = [];
        foreach ($usedRows as $row) {
            $legacyValues[] = $row['value'];
        }

        $this->addSql('ALTER TABLE env ADD users_tel TEXT DEFAULT NULL');
        $this->addSql('UPDATE env SET users_tel = ? WHERE id = 1', [serialize($legacyValues)]);
        $this->addSql('DROP TABLE user_used_identity');
    }

    private function normalizeTel(string $tel): ?string
    {
        $value = preg_replace('/[^0-9+]/', '', trim($tel)) ?? '';
        if (str_starts_with($value, '00')) {
            $value = '+' . substr($value, 2);
        }
        return $value === '' || $value === '+' ? null : $value;
    }
}
