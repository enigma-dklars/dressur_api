<?php

namespace App\Tests\Controller\API;

use App\Controller\API\PromotionReseauController;
use App\Entity\FormulePromoReseau;
use App\Entity\User;
use App\Repository\EnvRepository;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\MethodePaiementRepository;
use App\Repository\PromoReseauRepository;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\PromotionReseauPricing;
use App\Services\TraitementsDS;
use App\Services\VerificationsDS;
use App\Utilities\SendMail;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PromotionReseauControllerTest extends TestCase
{
    public function testFalsifiedClientAmountIsRejectedBeforeAnyPayment(): void
    {
        $controller = (new ReflectionClass(PromotionReseauController::class))->newInstanceWithoutConstructor();
        $this->setPrivateProperty($controller, 'cookieDS', $this->configuredMock(
            CookieDS::class,
            ['getWithFallback' => 'user-uid']
        ));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');
        $this->setPrivateProperty($controller, 'em', $entityManager);
        $this->setPrivateProperty($controller, 'sendMail', $this->createMock(SendMail::class));

        $formulaRepository = $this->createMock(FormulePromoReseauRepository::class);
        $formulaRepository
            ->expects(self::once())
            ->method('find')
            ->with(12)
            ->willReturn($this->formula());

        $promoRepository = $this->createMock(PromoReseauRepository::class);
        $promoRepository
            ->expects(self::exactly(2))
            ->method('findBy')
            ->willReturn([]);

        $user = new User();
        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['uid' => 'user-uid'])
            ->willReturn($user);

        $response = $controller->newPromoReseau(
            Request::create('/api/newPromoReseau', 'POST', [
                'idFormulePromoReseau' => 12,
                'qteDemander' => 50,
                'prixQteDemander' => 999999,
                'lien' => 'https://example.test/promotion',
                'valueMethodePaiement' => 1,
                'tel' => '+22960000000',
            ]),
            $formulaRepository,
            $this->createMock(VerificationsDS::class),
            $userRepository,
            $this->createMock(TraitementsDS::class),
            $promoRepository,
            $this->createMock(MethodePaiementRepository::class),
            new PromotionReseauPricing()
        );

        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame(
            'Le montant de la promotion est invalide. Veuillez actualiser le prix et réessayer.',
            json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR)['message']
        );
    }

    private function formula(): FormulePromoReseau
    {
        return (new FormulePromoReseau())
            ->setTitre('Formule test')
            ->setPrix(1.0)
            ->setQte(100)
            ->setQteMin(10)
            ->setQteMax(1000)
            ->setAvailable(true);
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflection = new ReflectionClass($object);
        $reflectionProperty = $reflection->getProperty($property);
        $reflectionProperty->setValue($object, $value);
    }

    /**
     * @param class-string $class
     * @param array<string, mixed> $configuration
     */
    private function configuredMock(string $class, array $configuration): object
    {
        return $this->createConfiguredMock($class, $configuration);
    }
}