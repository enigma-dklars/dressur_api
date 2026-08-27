<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Controller\PublicController;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class PublicApiDocumentationTest extends TestCase
{
    public function testOpenApiDescriptionExplainsThe1000UnitReference(): void
    {
        $controller = (new ReflectionClass(PublicController::class))->newInstanceWithoutConstructor();
        $response = $controller->documentationApiOpenApi();
        $payload = json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('1 000 unités', $payload['info']['description']);
        self::assertStringContainsString('price × quantité souhaitée ÷ 1 000', $payload['info']['description']);
        self::assertStringContainsString('1 000 unités', $payload['paths']['/api/v1/developer/catalog']['get']['responses']['200']['description']);
    }

    public function testHtmlDocumentationContainsThePricingRule(): void
    {
        $template = (string)file_get_contents(__DIR__ . '/../../templates/public/documentation_api.html.twig');

        self::assertStringContainsString('1&nbsp;000 unités', $template);
        self::assertStringContainsString('price × quantité souhaitée ÷ 1&nbsp;000', $template);
        self::assertStringContainsString('750 XOF', $template);
    }
}
