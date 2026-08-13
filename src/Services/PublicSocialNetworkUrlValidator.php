<?php

namespace App\Services;

final class PublicSocialNetworkUrlValidator
{
    public const MAX_URL_LENGTH = 512;

    public function __construct(
        private readonly PublicSocialNetworkCatalog $catalog
    ) {
    }

    /**
     * Returns true when the URL can be safely accepted for the selected network.
     */
    public function validate(?string $networkType, ?string $url): bool
    {
        return $this->getError($networkType, $url) === null;
    }

    public function isValid(?string $networkType, ?string $url): bool
    {
        return $this->validate($networkType, $url);
    }

    /**
     * Returns a client-safe validation message, or null when the URL is valid.
     */
    public function getError(?string $networkType, ?string $url): ?string
    {
        $normalizedUrl = is_string($url) ? trim($url) : '';

        if ($normalizedUrl === '') {
            return 'L’URL est obligatoire.';
        }

        if (strlen($normalizedUrl) > self::MAX_URL_LENGTH) {
            return 'L’URL ne doit pas dépasser 512 caractères.';
        }

        $parts = parse_url($normalizedUrl);
        if (
            filter_var($normalizedUrl, FILTER_VALIDATE_URL) === false
            || $parts === false
            || !isset($parts['scheme'], $parts['host'])
            || strtolower((string) $parts['scheme']) !== 'https'
            || trim((string) $parts['host']) === ''
        ) {
            return 'L’URL doit être une URL HTTPS valide.';
        }

        if ($networkType === null || !$this->catalog->isAllowed($networkType)) {
            return 'Le réseau sélectionné n’est pas autorisé.';
        }

        if (!$this->catalog->isUrlAllowed($networkType, $normalizedUrl)) {
            return 'L’URL ne correspond pas au domaine autorisé pour ce réseau.';
        }

        return null;
    }
}