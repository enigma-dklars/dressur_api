<?php

namespace App\Tests\Services;

use App\Services\PublicSocialNetworkCatalog;
use App\Services\PublicSocialNetworkUrlValidator;
use PHPUnit\Framework\TestCase;

final class PublicSocialNetworkUrlValidatorTest extends TestCase
{
    public function testUrlIsRequired(): void
    {
        $validator = $this->createValidator();

        self::assertFalse($validator->validate('facebook', null));
        self::assertFalse($validator->validate('facebook', '   '));
        self::assertSame('L’URL est obligatoire.', $validator->getError('facebook', null));
    }

    public function testOnlyHttpsUrlsAreAccepted(): void
    {
        $validator = $this->createValidator();

        self::assertTrue($validator->validate('facebook', 'https://facebook.com/dressur'));
        self::assertFalse($validator->validate('facebook', 'http://facebook.com/dressur'));
        self::assertFalse($validator->validate('facebook', 'ftp://facebook.com/dressur'));
        self::assertFalse($validator->validate('facebook', 'javascript://facebook.com/dressur'));
    }

    public function testUrlLengthCannotExceedTheMaximum(): void
    {
        $validator = $this->createValidator();
        $url = 'https://example.com/' . str_repeat('a', 512);

        self::assertGreaterThan(PublicSocialNetworkUrlValidator::MAX_URL_LENGTH, strlen($url));
        self::assertFalse($validator->validate('website', $url));
        self::assertSame(
            'L’URL ne doit pas dépasser 512 caractères.',
            $validator->getError('website', $url)
        );
    }

    public function testSelectedNetworkMustMatchTheOfficialDomain(): void
    {
        $validator = $this->createValidator();

        self::assertTrue($validator->validate('instagram', 'https://www.instagram.com/dressur'));
        self::assertTrue($validator->validate('x', 'https://mobile.twitter.com/dressur'));
        self::assertFalse($validator->validate('instagram', 'https://www.facebook.com/dressur'));
        self::assertFalse($validator->validate('twitter', 'https://twitter.com/dressur'));
    }

    public function testWebsiteAndPortfolioAcceptValidHttpsUrlsWithoutSocialDomains(): void
    {
        $validator = $this->createValidator();

        self::assertTrue($validator->validate('website', 'https://does-not-exist.example/landing'));
        self::assertTrue($validator->validate('portfolio', 'https://designer.example.dev/work'));
        self::assertFalse($validator->validate('website', 'http://example.com'));
    }

    public function testMalformedUrlsAreRejectedWithoutCheckingProfileExistence(): void
    {
        $validator = $this->createValidator();

        self::assertFalse($validator->validate('facebook', 'https://'));
        self::assertFalse($validator->validate('facebook', 'not-a-url'));
        self::assertFalse($validator->validate('facebook', 'https://facebook.com.example.com/profile'));
        self::assertTrue($validator->validate('facebook', 'https://facebook.com/profile-that-may-not-exist'));
    }

    private function createValidator(): PublicSocialNetworkUrlValidator
    {
        return new PublicSocialNetworkUrlValidator(new PublicSocialNetworkCatalog());
    }
}