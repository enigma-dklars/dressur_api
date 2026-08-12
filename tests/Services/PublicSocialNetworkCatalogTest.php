<?php

namespace App\Tests\Services;

use App\Services\PublicSocialNetworkCatalog;
use PHPUnit\Framework\TestCase;

final class PublicSocialNetworkCatalogTest extends TestCase
{
    public function testCatalogContainsOnlyTheCanonicalAllowedIdentifiers(): void
    {
        $catalog = new PublicSocialNetworkCatalog();

        self::assertSame([
            'facebook',
            'instagram',
            'tiktok',
            'x',
            'linkedin',
            'youtube',
            'discord',
            'snapchat',
            'reddit',
            'pinterest',
            'twitch',
            'telegram',
            'whatsapp',
            'github',
            'threads',
            'bluesky',
            'mastodon',
            'vimeo',
            'behance',
            'dribbble',
            'soundcloud',
            'website',
            'portfolio',
        ], array_column($catalog->all(), 'id'));
    }

    public function testOfficialDomainsAndSubdomainsAreAllowed(): void
    {
        $catalog = new PublicSocialNetworkCatalog();

        self::assertTrue($catalog->isUrlAllowed('facebook', 'https://www.facebook.com/dressur'));
        self::assertTrue($catalog->isUrlAllowed('x', 'https://mobile.twitter.com/dressur'));
        self::assertTrue($catalog->isUrlAllowed('youtube', 'https://youtu.be/example'));
        self::assertTrue($catalog->isUrlAllowed('discord', 'https://discord.gg/dressur'));
        self::assertTrue($catalog->isUrlAllowed('telegram', 'https://t.me/dressur'));
        self::assertTrue($catalog->isUrlAllowed('whatsapp', 'https://wa.me/22900000000'));
        self::assertTrue($catalog->isUrlAllowed('github', 'https://github.com/enigma-dklars'));
    }

    public function testNonOfficialDomainsAndInvalidUrlsAreRejected(): void
    {
        $catalog = new PublicSocialNetworkCatalog();

        self::assertFalse($catalog->isUrlAllowed('facebook', 'https://facebook.com.example.com/profile'));
        self::assertFalse($catalog->isUrlAllowed('facebook', 'https://example.com/facebook'));
        self::assertFalse($catalog->isUrlAllowed('facebook', 'javascript://facebook.com'));
        self::assertFalse($catalog->isUrlAllowed('unknown', 'https://example.com/profile'));
    }

    public function testWebsiteAndPortfolioAllowCustomHttpUrls(): void
    {
        $catalog = new PublicSocialNetworkCatalog();

        self::assertTrue($catalog->isUrlAllowed('website', 'https://example.com'));
        self::assertTrue($catalog->isUrlAllowed('portfolio', 'http://portfolio.example.dev/work'));
        self::assertFalse($catalog->isUrlAllowed('website', 'mailto:hello@example.com'));
    }

    public function testIdentifiersAreNormalizedWithoutChangingCanonicalIds(): void
    {
        $catalog = new PublicSocialNetworkCatalog();

        self::assertTrue($catalog->isAllowed(' FACEBOOK '));
        self::assertSame('facebook', $catalog->get(' FACEBOOK ')['id']);
        self::assertFalse($catalog->isAllowed('twitter'));
    }
}