<?php

namespace App\Tests\Controller\API;

use App\Controller\API\UserSocialNetworkController;
use App\Entity\User;
use App\Entity\UserSocialNetwork;
use App\Repository\UserSocialNetworkRepository;
use App\Services\CookieDS;
use App\Services\PublicSocialNetworkCatalog;
use App\Services\PublicSocialNetworkUrlValidator;
use App\Services\VerificationsDS;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

final class UserSocialNetworkControllerTest extends TestCase
{
    private array $originalCookies = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalCookies = $_COOKIE;
        $_COOKIE = [];
    }

    protected function tearDown(): void
    {
        $_COOKIE = $this->originalCookies;
        parent::tearDown();
    }

    public function testAuthenticatedUserCanCreateAValidNetwork(): void
    {
        $user = new User();
        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneForUser')
            ->with($user, 'instagram')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(
                static function (UserSocialNetwork $network) use ($user): bool {
                    return $network->getUser() === $user
                        && $network->getNetworkType() === 'instagram'
                        && $network->getUrl() === 'https://instagram.com/dressur';
                }
            ));
        $entityManager->expects(self::once())->method('flush');

        $response = $this->createController($user, $repository, $entityManager)->create(
            $this->jsonRequest('POST', [
                'networkType' => 'instagram',
                'url' => ' https://instagram.com/dressur ',
            ]),
            $this->authenticatedVerification($user)
        );

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertFalse($this->responseData($response)['error']);
        self::assertSame('instagram', $this->responseData($response)['network']['networkType']);
    }

    public function testAuthenticatedUserCanListOnlyTheirNetworks(): void
    {
        $user = new User();
        $otherUser = new User();
        $ownNetwork = $this->network($user, 'github', 'https://github.com/own-account');
        $otherNetwork = $this->network($otherUser, 'github', 'https://github.com/other-account');

        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository
            ->expects(self::once())
            ->method('findForUser')
            ->with($user)
            ->willReturn([$ownNetwork]);

        $response = $this->createController($user, $repository)->list(
            Request::create('/api/user/social-networks', 'GET'),
            $this->authenticatedVerification($user)
        );

        $data = $this->responseData($response);
        self::assertFalse($data['error']);
        self::assertCount(1, $data['networks']);
        self::assertSame('https://github.com/own-account', $data['networks'][0]['url']);
        self::assertNotSame($otherNetwork->getUrl(), $data['networks'][0]['url']);
    }

    public function testAuthenticatedUserCanUpdateTheirNetworkUrl(): void
    {
        $user = new User();
        $network = $this->network($user, 'linkedin', 'https://linkedin.com/in/old-account');

        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneForUser')
            ->with($user, 'linkedin')
            ->willReturn($network);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $response = $this->createController($user, $repository, $entityManager)->update(
            'linkedin',
            $this->jsonRequest('PATCH', ['url' => 'https://linkedin.com/in/new-account']),
            $this->authenticatedVerification($user)
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('https://linkedin.com/in/new-account', $network->getUrl());
        self::assertSame(
            'https://linkedin.com/in/new-account',
            $this->responseData($response)['network']['url']
        );
    }

    public function testAuthenticatedUserCanDeleteTheirNetwork(): void
    {
        $user = new User();
        $network = $this->network($user, 'youtube', 'https://youtube.com/@dressur');

        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneForUser')
            ->with($user, 'youtube')
            ->willReturn($network);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('remove')
            ->with($network);
        $entityManager->expects(self::once())->method('flush');

        $response = $this->createController($user, $repository, $entityManager)->delete(
            'youtube',
            Request::create('/api/user/social-networks/youtube', 'DELETE'),
            $this->authenticatedVerification($user)
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('youtube', $this->responseData($response)['networkType']);
    }

    public function testAuthenticatedUserCannotCreateTheSameNetworkTwice(): void
    {
        $user = new User();
        $existingNetwork = $this->network($user, 'facebook', 'https://facebook.com/existing');
        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneForUser')
            ->with($user, 'facebook')
            ->willReturn($existingNetwork);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $response = $this->createController($user, $repository, $entityManager)->create(
            $this->jsonRequest('POST', [
                'networkType' => 'facebook',
                'url' => 'https://facebook.com/new-profile',
            ]),
            $this->authenticatedVerification($user)
        );

        self::assertSame(Response::HTTP_CONFLICT, $response->getStatusCode());
        self::assertSame('network_already_exists', $this->responseData($response)['code']);
    }

    /**
     * @dataProvider invalidUrlProvider
     */
    public function testCreateRejectsInvalidUrls(
        string $networkType,
        string $url,
        string $expectedMessage
    ): void {
        $user = new User();
        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository->expects(self::never())->method('findOneForUser');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('persist');
        $entityManager->expects(self::never())->method('flush');

        $response = $this->createController($user, $repository, $entityManager)->create(
            $this->jsonRequest('POST', [
                'networkType' => $networkType,
                'url' => $url,
            ]),
            $this->authenticatedVerification($user)
        );

        $data = $this->responseData($response);
        self::assertSame(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        self::assertSame('url_invalid', $data['code']);
        self::assertSame($expectedMessage, $data['message']);
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function invalidUrlProvider(): iterable
    {
        yield 'wrong official domain' => [
            'instagram',
            'https://facebook.com/not-instagram',
            'L’URL ne correspond pas au domaine autorisé pour ce réseau.',
        ];
        yield 'http is rejected' => [
            'instagram',
            'http://instagram.com/dressur',
            'L’URL doit être une URL HTTPS valide.',
        ];
        yield 'url is too long' => [
            'website',
            'https://example.com/' . str_repeat('a', 512),
            'L’URL ne doit pas dépasser 512 caractères.',
        ];
    }

    /**
     * @dataProvider customNetworkProvider
     */
    public function testWebsiteAndPortfolioAcceptValidHttpsUrls(string $networkType, string $url): void
    {
        $user = new User();
        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneForUser')
            ->with($user, $networkType)
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::callback(
                static fn (UserSocialNetwork $network): bool =>
                    $network->getUser() === $user
                    && $network->getNetworkType() === $networkType
                    && $network->getUrl() === $url
            ));
        $entityManager->expects(self::once())->method('flush');

        $response = $this->createController($user, $repository, $entityManager)->create(
            $this->jsonRequest('POST', [
                'networkType' => $networkType,
                'url' => $url,
            ]),
            $this->authenticatedVerification($user)
        );

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
        self::assertFalse($this->responseData($response)['error']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function customNetworkProvider(): iterable
    {
        yield 'website' => ['website', 'https://creator.example.com'];
        yield 'portfolio' => ['portfolio', 'https://portfolio.example.dev/work'];
    }

    public function testUserCannotAccessAnotherUsersNetwork(): void
    {
        $authenticatedUser = new User();
        $otherUser = new User();
        $otherNetwork = $this->network($otherUser, 'instagram', 'https://instagram.com/other-account');

        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneForUser')
            ->with($authenticatedUser, 'instagram')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('remove');
        $entityManager->expects(self::never())->method('flush');

        $response = $this->createController($authenticatedUser, $repository, $entityManager)->update(
            'instagram',
            $this->jsonRequest('PATCH', ['url' => 'https://instagram.com/attempted-change']),
            $this->authenticatedVerification($authenticatedUser)
        );

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
        self::assertSame('network_not_found', $this->responseData($response)['code']);
        self::assertSame('https://instagram.com/other-account', $otherNetwork->getUrl());
    }

    public function testSignedWebCookieHasPriorityOverMobileHeader(): void
    {
        $user = new User();
        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository
            ->expects(self::once())
            ->method('findForUser')
            ->with($user)
            ->willReturn([]);

        $_COOKIE['uid'] = $this->signedCookie('web-uid');
        $verification = $this->createMock(VerificationsDS::class);
        $verification
            ->expects(self::once())
            ->method('verifUSer')
            ->with('web-uid')
            ->willReturn(['error' => false, 'user' => $user]);

        $response = $this->createControllerWithRealCookie($repository)->list(
            $this->mobileRequest('GET', '/api/user/social-networks', 'mobile-uid'),
            $verification
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testMobileGetAuthenticatesWithDedicatedHeader(): void
    {
        $user = new User();
        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository
            ->expects(self::once())
            ->method('findForUser')
            ->with($user)
            ->willReturn([]);

        $verification = $this->verificationForUid('mobile-get-uid', $user);
        $response = $this->createControllerWithRealCookie($repository)->list(
            $this->mobileRequest('GET', '/api/user/social-networks', 'mobile-get-uid'),
            $verification
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testMobilePostAuthenticatesWithDedicatedHeader(): void
    {
        $user = new User();
        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneForUser')
            ->with($user, 'instagram')
            ->willReturn(null);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('persist');
        $entityManager->expects(self::once())->method('flush');

        $response = $this->createControllerWithRealCookie($repository, $entityManager)->create(
            $this->mobileRequest('POST', '/api/user/social-networks', 'mobile-post-uid', [
                'networkType' => 'instagram',
                'url' => 'https://instagram.com/dressur',
            ]),
            $this->verificationForUid('mobile-post-uid', $user)
        );

        self::assertSame(Response::HTTP_CREATED, $response->getStatusCode());
    }

    public function testMobilePutAuthenticatesWithDedicatedHeader(): void
    {
        $user = new User();
        $network = $this->network($user, 'linkedin', 'https://linkedin.com/in/old-account');
        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneForUser')
            ->with($user, 'linkedin')
            ->willReturn($network);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $response = $this->createControllerWithRealCookie($repository, $entityManager)->update(
            'linkedin',
            $this->mobileRequest('PUT', '/api/user/social-networks/linkedin', 'mobile-put-uid', [
                'url' => 'https://linkedin.com/in/new-account',
            ]),
            $this->verificationForUid('mobile-put-uid', $user)
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testMobileDeleteAuthenticatesWithDedicatedHeader(): void
    {
        $user = new User();
        $network = $this->network($user, 'youtube', 'https://youtube.com/@dressur');
        $repository = $this->createMock(UserSocialNetworkRepository::class);
        $repository
            ->expects(self::once())
            ->method('findOneForUser')
            ->with($user, 'youtube')
            ->willReturn($network);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('remove')->with($network);
        $entityManager->expects(self::once())->method('flush');

        $response = $this->createControllerWithRealCookie($repository, $entityManager)->delete(
            'youtube',
            $this->mobileRequest('DELETE', '/api/user/social-networks/youtube', 'mobile-delete-uid'),
            $this->verificationForUid('mobile-delete-uid', $user)
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testMissingIdentityIsRejected(): void
    {
        $verification = $this->createMock(VerificationsDS::class);
        $verification->expects(self::never())->method('verifUSer');

        $response = $this->createControllerWithRealCookie()->list(
            Request::create('/api/user/social-networks', 'GET'),
            $verification
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('authentication_required', $this->responseData($response)['code']);
    }

    public function testInvalidMobileUidIsRejected(): void
    {
        $verification = $this->createMock(VerificationsDS::class);
        $verification
            ->expects(self::once())
            ->method('verifUSer')
            ->with('invalid-uid')
            ->willReturn([
                'error' => true,
                'deleted' => true,
                'blocked' => false,
                'titre' => 'Erreur!',
                'message' => "Ce compte n'existe plus.",
            ]);

        $response = $this->createControllerWithRealCookie()->list(
            $this->mobileRequest('GET', '/api/user/social-networks', 'invalid-uid'),
            $verification
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('session_invalid', $this->responseData($response)['code']);
    }

    public function testBlockedMobileUserIsRejected(): void
    {
        $verification = $this->createMock(VerificationsDS::class);
        $verification
            ->expects(self::once())
            ->method('verifUSer')
            ->with('blocked-uid')
            ->willReturn([
                'error' => true,
                'deleted' => false,
                'blocked' => true,
                'titre' => 'Erreur!',
                'message' => 'Compte bloqué.',
            ]);

        $response = $this->createControllerWithRealCookie()->list(
            $this->mobileRequest('GET', '/api/user/social-networks', 'blocked-uid'),
            $verification
        );

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        self::assertSame('account_blocked', $this->responseData($response)['code']);
    }

    public function testClientSuppliedUserIdCannotAuthenticateTheRequest(): void
    {
        $user = new User();
        $cookie = $this->createMock(CookieDS::class);
        $cookie
            ->expects(self::once())
            ->method('getWithFallback')
            ->with('uid', self::isInstanceOf(Request::class))
            ->willReturn(false);

        $verification = $this->createMock(VerificationsDS::class);
        $verification->expects(self::never())->method('verifUSer');

        $controller = new UserSocialNetworkController(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(UserSocialNetworkRepository::class),
            $cookie,
            new PublicSocialNetworkCatalog(),
            new PublicSocialNetworkUrlValidator(new PublicSocialNetworkCatalog()),
        );

        $response = $controller->list(
            $this->jsonRequest('GET', ['user_id' => (string) $user->getId()]),
            $verification
        );

        self::assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        self::assertSame('authentication_required', $this->responseData($response)['code']);
    }

    private function createController(
        User $user,
        ?UserSocialNetworkRepository $repository = null,
        ?EntityManagerInterface $entityManager = null
    ): UserSocialNetworkController {
        return new UserSocialNetworkController(
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $repository ?? $this->createMock(UserSocialNetworkRepository::class),
            $this->authenticatedCookie(),
            new PublicSocialNetworkCatalog(),
            new PublicSocialNetworkUrlValidator(new PublicSocialNetworkCatalog()),
        );
    }

    private function createControllerWithRealCookie(
        ?UserSocialNetworkRepository $repository = null,
        ?EntityManagerInterface $entityManager = null
    ): UserSocialNetworkController {
        return new UserSocialNetworkController(
            $entityManager ?? $this->createMock(EntityManagerInterface::class),
            $repository ?? $this->createMock(UserSocialNetworkRepository::class),
            new CookieDS(new ParameterBag(['kernel.secret' => 'test-secret'])),
            new PublicSocialNetworkCatalog(),
            new PublicSocialNetworkUrlValidator(new PublicSocialNetworkCatalog()),
        );
    }

    private function signedCookie(string $uid): string
    {
        return $uid . '.' . hash_hmac('sha256', $uid, 'test-secret');
    }

    /**
     * @param array<string, mixed> $parameters
     */
    private function mobileRequest(
        string $method,
        string $uri,
        string $uid,
        array $parameters = []
    ): Request {
        return Request::create(
            $uri,
            $method,
            $parameters,
            [],
            [],
            ['HTTP_X_DRESSUR_UID' => $uid],
        );
    }

    private function verificationForUid(string $uid, User $user): VerificationsDS&MockObject
    {
        $verification = $this->createMock(VerificationsDS::class);
        $verification
            ->expects(self::once())
            ->method('verifUSer')
            ->with($uid)
            ->willReturn([
                'error' => false,
                'user' => $user,
            ]);

        return $verification;
    }

    private function authenticatedCookie(): CookieDS&MockObject
    {
        $cookie = $this->createMock(CookieDS::class);
        $cookie
            ->method('getWithFallback')
            ->with('uid', self::isInstanceOf(Request::class))
            ->willReturn('authenticated-uid');

        return $cookie;
    }

    private function authenticatedVerification(User $user): VerificationsDS&MockObject
    {
        $verification = $this->createMock(VerificationsDS::class);
        $verification
            ->method('verifUSer')
            ->with('authenticated-uid')
            ->willReturn([
                'error' => false,
                'user' => $user,
            ]);

        return $verification;
    }

    private function network(User $user, string $networkType, string $url): UserSocialNetwork
    {
        return (new UserSocialNetwork())
            ->setUser($user)
            ->setNetworkType($networkType)
            ->setUrl($url);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function jsonRequest(string $method, array $payload): Request
    {
        return Request::create(
            '/api/user/social-networks',
            $method,
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function responseData(JsonResponse $response): array
    {
        $content = $response->getContent();
        self::assertIsString($content);

        /** @var array<string, mixed> $data */
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        return $data;
    }
}