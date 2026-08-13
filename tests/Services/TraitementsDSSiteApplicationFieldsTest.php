<?php

namespace App\Tests\Services;

use App\Entity\Promotion;
use App\Services\TraitementsDS;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class TraitementsDSSiteApplicationFieldsTest extends TestCase
{
    /**
     * @dataProvider siteApplicationTypesProvider
     */
    public function testSiteApplicationTypeIsExposedAtTopLevel(string $sousTypeSiteApp): void
    {
        $promotion = (new Promotion())
            ->setTypePromotionAffaire('sites_applications')
            ->setNomSiteApp('Application Dressur')
            ->setUrlSiteApp('https://example.com/dressur')
            ->setSousTypeSiteApp($sousTypeSiteApp);

        $fields = $this->createTraitementsDS()->getSiteApplicationFields($promotion);

        self::assertSame('Application Dressur', $fields['nomSiteApp']);
        self::assertSame('https://example.com/dressur', $fields['urlSiteApp']);
        self::assertSame($sousTypeSiteApp, $fields['sousTypeSiteApp']);
    }

    public function testLegacyAnnotherInfoIsNormalized(): void
    {
        $promotion = (new Promotion())
            ->setTypePromotionAffaire('sites_applications')
            ->setAnnotherInfo([
                'nom' => 'Ancienne application',
                'url' => 'https://legacy.example.com/app',
                'sousType' => 'logiciel_desktop',
            ]);

        $fields = $this->createTraitementsDS()->getSiteApplicationFields($promotion);

        self::assertSame('Ancienne application', $fields['nomSiteApp']);
        self::assertSame('https://legacy.example.com/app', $fields['urlSiteApp']);
        self::assertSame('logiciel_desktop', $fields['sousTypeSiteApp']);
    }

    public static function siteApplicationTypesProvider(): array
    {
        return [
            'site web' => ['site_web'],
            'application mobile' => ['app_mobile'],
            'logiciel desktop' => ['logiciel_desktop'],
        ];
    }

    private function createTraitementsDS(): TraitementsDS
    {
        /** @var TraitementsDS $traitementsDS */
        $traitementsDS = (new ReflectionClass(TraitementsDS::class))->newInstanceWithoutConstructor();

        return $traitementsDS;
    }
}