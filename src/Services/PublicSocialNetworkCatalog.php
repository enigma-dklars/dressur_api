<?php

namespace App\Services;

final class PublicSocialNetworkCatalog
{
    public const FACEBOOK = 'facebook';
    public const INSTAGRAM = 'instagram';
    public const TIKTOK = 'tiktok';
    public const X = 'x';
    public const LINKEDIN = 'linkedin';
    public const YOUTUBE = 'youtube';
    public const DISCORD = 'discord';
    public const SNAPCHAT = 'snapchat';
    public const REDDIT = 'reddit';
    public const PINTEREST = 'pinterest';
    public const TWITCH = 'twitch';
    public const TELEGRAM = 'telegram';
    public const WHATSAPP = 'whatsapp';
    public const GITHUB = 'github';
    public const THREADS = 'threads';
    public const BLUESKY = 'bluesky';
    public const MASTODON = 'mastodon';
    public const VIMEO = 'vimeo';
    public const BEHANCE = 'behance';
    public const DRIBBBLE = 'dribbble';
    public const SOUNDCLOUD = 'soundcloud';
    public const WEBSITE = 'website';
    public const PORTFOLIO = 'portfolio';

    /**
     * @var array<string, array{label: string, domains: list<string>, allowsCustomDomains: bool}>
     */
    private const CATALOG = [
        self::FACEBOOK => [
            'label' => 'Facebook',
            'domains' => ['facebook.com'],
            'allowsCustomDomains' => false,
        ],
        self::INSTAGRAM => [
            'label' => 'Instagram',
            'domains' => ['instagram.com'],
            'allowsCustomDomains' => false,
        ],
        self::TIKTOK => [
            'label' => 'TikTok',
            'domains' => ['tiktok.com'],
            'allowsCustomDomains' => false,
        ],
        self::X => [
            'label' => 'X / Twitter',
            'domains' => ['x.com', 'twitter.com'],
            'allowsCustomDomains' => false,
        ],
        self::LINKEDIN => [
            'label' => 'LinkedIn',
            'domains' => ['linkedin.com'],
            'allowsCustomDomains' => false,
        ],
        self::YOUTUBE => [
            'label' => 'YouTube',
            'domains' => ['youtube.com', 'youtu.be'],
            'allowsCustomDomains' => false,
        ],
        self::DISCORD => [
            'label' => 'Discord',
            'domains' => ['discord.com', 'discord.gg', 'discordapp.com'],
            'allowsCustomDomains' => false,
        ],
        self::SNAPCHAT => [
            'label' => 'Snapchat',
            'domains' => ['snapchat.com'],
            'allowsCustomDomains' => false,
        ],
        self::REDDIT => [
            'label' => 'Reddit',
            'domains' => ['reddit.com', 'redd.it'],
            'allowsCustomDomains' => false,
        ],
        self::PINTEREST => [
            'label' => 'Pinterest',
            'domains' => ['pinterest.com', 'pin.it'],
            'allowsCustomDomains' => false,
        ],
        self::TWITCH => [
            'label' => 'Twitch',
            'domains' => ['twitch.tv'],
            'allowsCustomDomains' => false,
        ],
        self::TELEGRAM => [
            'label' => 'Telegram',
            'domains' => ['telegram.org', 'telegram.me', 't.me'],
            'allowsCustomDomains' => false,
        ],
        self::WHATSAPP => [
            'label' => 'WhatsApp',
            'domains' => ['whatsapp.com', 'wa.me'],
            'allowsCustomDomains' => false,
        ],
        self::GITHUB => [
            'label' => 'GitHub',
            'domains' => ['github.com'],
            'allowsCustomDomains' => false,
        ],
        self::THREADS => [
            'label' => 'Threads',
            'domains' => ['threads.net'],
            'allowsCustomDomains' => false,
        ],
        self::BLUESKY => [
            'label' => 'Bluesky',
            'domains' => ['bsky.app', 'bsky.social'],
            'allowsCustomDomains' => false,
        ],
        self::MASTODON => [
            'label' => 'Mastodon',
            'domains' => ['mastodon.social', 'mastodon.online'],
            'allowsCustomDomains' => false,
        ],
        self::VIMEO => [
            'label' => 'Vimeo',
            'domains' => ['vimeo.com'],
            'allowsCustomDomains' => false,
        ],
        self::BEHANCE => [
            'label' => 'Behance',
            'domains' => ['behance.net'],
            'allowsCustomDomains' => false,
        ],
        self::DRIBBBLE => [
            'label' => 'Dribbble',
            'domains' => ['dribbble.com'],
            'allowsCustomDomains' => false,
        ],
        self::SOUNDCLOUD => [
            'label' => 'SoundCloud',
            'domains' => ['soundcloud.com'],
            'allowsCustomDomains' => false,
        ],
        self::WEBSITE => [
            'label' => 'Website',
            'domains' => [],
            'allowsCustomDomains' => true,
        ],
        self::PORTFOLIO => [
            'label' => 'Portfolio',
            'domains' => [],
            'allowsCustomDomains' => true,
        ],
    ];

    /**
     * @return list<array{id: string, label: string, domains: list<string>, allowsCustomDomains: bool}>
     */
    public function all(): array
    {
        $catalog = [];

        foreach (self::CATALOG as $id => $network) {
            $catalog[] = ['id' => $id] + $network;
        }

        return $catalog;
    }

    /**
     * @return array{id: string, label: string, domains: list<string>, allowsCustomDomains: bool}|null
     */
    public function get(string $identifier): ?array
    {
        $identifier = $this->normalizeIdentifier($identifier);
        $network = self::CATALOG[$identifier] ?? null;

        return $network === null ? null : ['id' => $identifier] + $network;
    }

    public function isAllowed(string $identifier): bool
    {
        return $this->get($identifier) !== null;
    }

    public function isUrlAllowed(string $identifier, string $url): bool
    {
        $network = $this->get($identifier);
        if ($network === null) {
            return false;
        }

        $parts = parse_url(trim($url));
        if (
            $parts === false
            || !isset($parts['scheme'], $parts['host'])
            || !in_array(strtolower($parts['scheme']), ['http', 'https'], true)
        ) {
            return false;
        }

        if ($network['allowsCustomDomains']) {
            return true;
        }

        $host = strtolower(rtrim($parts['host'], '.'));
        foreach ($network['domains'] as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeIdentifier(string $identifier): string
    {
        return strtolower(trim($identifier));
    }
}