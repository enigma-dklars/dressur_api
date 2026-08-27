<?php

declare(strict_types=1);

namespace App\Tests\Controller\API;

use App\Controller\API\DeveloperPublicApiController;
use App\Repository\DeveloperIdempotencyRepository;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\PromoReseauRepository;
use App\Services\DeveloperApiAuditService;
use App\Services\DeveloperApiKeyService;
use App\Services\DeveloperOrderService;
use App\Services\DeveloperOrderStatusService;
use App\Services\DeveloperApiProtectionService;
use App\Services\PromotionReseauPricing;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class DeveloperPublicApiControllerTest extends TestCase
{
    public function testCatalogWithoutBearerReturnsJsonUnauthorized(): void
    {
        /** @var DeveloperApiKeyService&MockObject $keyService */
        $keyService = $this->createMock(DeveloperApiKeyService::class);
        $keyService->expects(self::once())->method('authenticate')->willReturn(null);

        $controller = new DeveloperPublicApiController(
            $this->createMock(EntityManagerInterface::class),
            $keyService,
            $this->createMock(FormulePromoReseauRepository::class),
            $this->createMock(PromoReseauRepository::class),
            $this->createMock(DeveloperIdempotencyRepository::class),
            new PromotionReseauPricing(),
            $this->createMock(TraitementsDS::class),
            $this->createMock(DeveloperOrderService::class),
            $this->createMock(DeveloperApiAuditService::class),
        );

        $response = $controller->catalog(Request::create('/api/v1/developer/catalog', 'GET'));
        $payload = json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertTrue($payload['error']);
        self::assertSame('Clé API invalide, révoquée ou expirée.', $payload['message']);
    }

    public function testBalanceWithMalformedBearerReturnsJsonUnauthorized(): void
    {
        /** @var DeveloperApiKeyService&MockObject $keyService */
        $keyService = $this->createMock(DeveloperApiKeyService::class);
        $keyService->expects(self::once())->method('authenticate')->willReturn(null);

        $controller = new DeveloperPublicApiController(
            $this->createMock(EntityManagerInterface::class),
            $keyService,
            $this->createMock(FormulePromoReseauRepository::class),
            $this->createMock(PromoReseauRepository::class),
            $this->createMock(DeveloperIdempotencyRepository::class),
            new PromotionReseauPricing(),
            $this->createMock(TraitementsDS::class),
            $this->createMock(DeveloperOrderService::class),
            $this->createMock(DeveloperApiAuditService::class),
        );

        $request = Request::create('/api/v1/developer/balance', 'GET', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Basic malformed',
        ]);
        $response = $controller->balance($request);

        $payload = json_decode((string)$response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('Clé API invalide, révoquée ou expirée.', $payload['message']);
    }
}
