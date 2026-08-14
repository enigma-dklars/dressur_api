<?php

namespace App\Tests\Controller\API;

use App\Controller\API\VendeurController;
use App\Entity\User;
use App\Repository\MethodePaiementRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Services\VerificationsDS;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

final class VendeurControllerTest extends TestCase
{
    /**
     * @dataProvider rechargeAccessCases
     */
    public function testRechargeAccessRequiresAtLeastOneEligibleStatus(
        bool $vendeur,
        bool $partenaire,
        bool $programmeRecompense,
        bool $isAllowed
    ): void {
        $controller = (new ReflectionClass(VendeurController::class))->newInstanceWithoutConstructor();
        $this->setPrivateProperty($controller, 'cookieDS', $this->createConfiguredMock(
            CookieDS::class,
            ['getWithFallback' => 'user-uid']
        ));

        $user = (new User())
            ->setVendeur($vendeur)
            ->setEstPartenaire($partenaire)
            ->setIsInscritProgrammeRecompense($programmeRecompense);

        $verificationsDS = $this->createConfiguredMock(
            VerificationsDS::class,
            [
                'verifUSer' => [
                    'error' => false,
                    'user' => $user,
                ],
            ]
        );

        $response = $controller->recharge(
            Request::create('/api/vendeur/recharge', 'POST', ['montant' => 499]),
            $this->createMock(TraitementsDS::class),
            $verificationsDS,
            $this->createMock(UserRepository::class),
            $this->createMock(MethodePaiementRepository::class),
            $this->createMock(TransactionRepository::class)
        );

        $payload = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame($isAllowed, !($payload['titre'] === 'Non autorisé'));

        if ($isAllowed) {
            self::assertSame('Montant insuffisant', $payload['titre']);
        } else {
            self::assertSame(
                'Vous devez être vendeur, partenaire ou inscrit au programme de récompenses pour recharger votre solde.',
                $payload['message']
            );
        }
    }

    /**
     * @return iterable<string, array{bool, bool, bool, bool}>
     */
    public static function rechargeAccessCases(): iterable
    {
        yield 'vendeur seul' => [true, false, false, true];
        yield 'partenaire seul' => [false, true, false, true];
        yield 'inscrit au programme seul' => [false, false, true, true];
        yield 'aucun statut' => [false, false, false, false];
    }

    /**
     * @dataProvider rechargeVisibilityCases
     */
    public function testSettingsPageShowsRechargeForEveryEligibleStatus(
        bool $vendeur,
        bool $partenaire,
        bool $programmeRecompense,
        bool $shouldShowRecharge
    ): void {
        $html = $this->renderSettingsPage([
            'vendeur' => $vendeur,
            'estPartenaire' => $partenaire,
            'isInscritProgrammeRecompense' => $programmeRecompense,
        ]);

        self::assertSame($shouldShowRecharge, str_contains($html, 'href="/vendeur/recharge"'));
    }

    /**
     * @return iterable<string, array{bool, bool, bool, bool}>
     */
    public static function rechargeVisibilityCases(): iterable
    {
        yield 'vendeur' => [true, false, false, true];
        yield 'partenaire' => [false, true, false, true];
        yield 'programme de récompenses' => [false, false, true, true];
        yield 'aucun statut' => [false, false, false, false];
    }

    /**
     * @param array<string, bool|int|string> $status
     */
    private function renderSettingsPage(array $status): string
    {
        $loader = new FilesystemLoader(__DIR__ . '/../../../templates');
        $twig = new Environment($loader);
        $twig->addFunction(new TwigFunction('path', static fn (string $route): string => '/' . $route));

        $request = new class {
            public string $requestUri = '/parametres';

            public object $attributes;

            public function __construct()
            {
                $this->attributes = new class {
                    public function get(string $name): ?string
                    {
                        return $name === '_route' ? 'app_hub_parametres' : null;
                    }
                };
            }
        };

        return $twig->render('private/hub_parametres.html.twig', [
            'theme' => 'light-theme',
            'app' => (object)['request' => $request],
            'user' => array_merge([
                'admin' => false,
                'lecteur' => false,
                'aUnPartenaire' => false,
                'soldeProgrammeRecompense' => 0,
                'nom' => 'Test User',
                'pseudo' => 'test-user',
                'uid' => 'test-uid',
            ], $status),
        ]);
    }

    private function setPrivateProperty(object $object, string $property, mixed $value): void
    {
        $reflectionProperty = (new ReflectionClass($object))->getProperty($property);
        $reflectionProperty->setValue($object, $value);
    }
}