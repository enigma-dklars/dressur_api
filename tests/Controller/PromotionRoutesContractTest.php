<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\TestCase;

final class PromotionRoutesContractTest extends TestCase
{
    public function testClassicAndAdvertisingRoutesUseTheSameEncodedTokenContract(): void
    {
        $controller = $this->source('src/Controller/PublicController.php');
        $crudController = $this->source('src/Controller/Crud/CrudPromotionController.php');

        self::assertStringContainsString(
            "#[Route('/actualite/pub/{token}', name: 'app_actualite_pub')]",
            $controller
        );
        self::assertStringContainsString(
            "#[Route('/actualite/{token}', name: 'app_actualite_detail')]",
            $controller
        );
        self::assertSame(2, substr_count($controller, '$id = $this->decodePromoToken($token);'));
        self::assertSame(2, substr_count($controller, '"token"                => $token,'));
        self::assertStringContainsString(
            "'https://dressur.site/actualite/pub/' . \$this->encodePromoToken(\$id)",
            $crudController
        );
    }

    public function testClassicRouteKeepsWhatsAppAndOtherNews(): void
    {
        $controller = $this->source('src/Controller/PublicController.php');
        $template = $this->source('templates/public/actualite_detail.html.twig');

        self::assertStringContainsString(
            "return \$this->render('public/actualite_detail.html.twig'",
            $controller
        );
        self::assertStringContainsString('Contacter l\'annonceur par WhatsApp', $template);
        self::assertStringContainsString('Autres actualités', $template);
        self::assertStringContainsString('data-share-url="https://dressur.site/actualite/{{ promo.token }}"', $template);
        self::assertStringContainsString('href="/actualite/{{ autre.token }}"', $template);
    }

    public function testAdvertisingRouteIsFocusedAndPromotesConversion(): void
    {
        $controller = $this->source('src/Controller/PublicController.php');
        $template = $this->source('templates/public/actualite_pub.html.twig');

        self::assertStringContainsString(
            "return \$this->render('public/actualite_pub.html.twig'",
            $controller
        );
        self::assertStringNotContainsString('Autres actualités', $template);
        self::assertStringContainsString('Contacter l\'annonceur par WhatsApp', $template);
        self::assertStringContainsString("path('app_inscription')", $template);
        self::assertStringContainsString("path('app_promotion_affaire')", $template);
        self::assertStringContainsString("path('app_boost_contact')", $template);
        self::assertStringContainsString("url('app_actualite_pub', { token: promo.token })", $template);
    }

    public function testAdminCopiesOnlyDedicatedAdvertisingUrls(): void
    {
        $template = $this->source('templates/crud_promotion/index.html.twig');

        self::assertStringContainsString('data-advertising-url="{{ advertisingUrl|e(\'html_attr\') }}"', $template);
        self::assertStringContainsString('js-copy-advertising-url', $template);
        self::assertStringContainsString('https://dressur.site/actualite/pub/', $template);
        self::assertStringContainsString('navigator.clipboard.writeText(url)', $template);
        self::assertStringContainsString("document.execCommand('copy')", $template);
        self::assertStringNotContainsString('js-copy-public-url', $template);
        self::assertStringNotContainsString('data-public-url', $template);
        self::assertStringNotContainsString('fallbackCopyPublicUrl', $template);
        self::assertStringNotContainsString('https://dressur.site/actualite/{{', $template);
    }

    private function source(string $relativePath): string
    {
        $path = dirname(__DIR__, 2) . '/' . $relativePath;
        $contents = file_get_contents($path);

        self::assertIsString($contents, sprintf('Unable to read %s', $relativePath));

        return $contents;
    }
}