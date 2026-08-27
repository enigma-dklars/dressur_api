<?php

namespace App\Controller\API;

use App\Repository\DeveloperApiKeyRepository;
use App\Services\CookieDS;
use App\Services\DeveloperApiKeyService;
use App\Services\VerificationsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/developpeur/cles', name: 'api_developpeur_cles_')]
class DeveloperApiKeyController extends AbstractController
{
    public function __construct(
        private readonly CookieDS $cookieDS,
        private readonly EntityManagerInterface $entityManager,
        private readonly DeveloperApiKeyRepository $repository,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, VerificationsDS $verificationsDS, DeveloperApiKeyService $keyService): JsonResponse
    {
        $user = $this->getUserFromRequest($request, $verificationsDS);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $profile = $user->getDeveloperProfile();
        if (!$profile || !$profile->isActive()) {
            return $this->notActiveResponse();
        }

        return new JsonResponse([
            'error' => false,
            'keys' => array_map(
                static fn ($key): array => $keyService->toPublicArray($key),
                $profile->getApiKeys()->toArray()
            ),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, VerificationsDS $verificationsDS, DeveloperApiKeyService $keyService): JsonResponse
    {
        $user = $this->getUserFromRequest($request, $verificationsDS);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $profile = $user->getDeveloperProfile();
        if (!$profile || !$profile->isActive()) {
            return $this->notActiveResponse();
        }

        $label = trim((string)$request->request->get('label', ''));
        $rawScopes = $request->request->all('scopes');
        if ($rawScopes === []) {
            $rawScopes = [
                DeveloperApiKeyService::SCOPE_CATALOG_READ,
                DeveloperApiKeyService::SCOPE_BALANCE_READ,
                DeveloperApiKeyService::SCOPE_ORDERS_READ,
                DeveloperApiKeyService::SCOPE_ORDERS_WRITE,
                DeveloperApiKeyService::SCOPE_STATUS_READ,
            ];
        }

        try {
            $created = $keyService->createKey($profile, $label, $rawScopes);
            $this->entityManager->flush();
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Clé invalide',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'error' => false,
            'message' => 'Clé créée. Le secret complet ne sera plus affiché après cette réponse.',
            'key' => $keyService->toPublicArray($created['key']),
            'secret' => $created['secret'],
            'token' => $created['key']->getKeyId() . '.' . $created['secret'],
        ], Response::HTTP_CREATED);
    }

    #[Route('/{keyId}/revoquer', name: 'revoke', methods: ['POST'])]
    public function revoke(string $keyId, Request $request, VerificationsDS $verificationsDS, DeveloperApiKeyService $keyService): JsonResponse
    {
        $user = $this->getUserFromRequest($request, $verificationsDS);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $profile = $user->getDeveloperProfile();
        $key = $profile ? $this->repository->findOneBy(['developerProfile' => $profile, 'keyId' => $keyId]) : null;
        if (!$key) {
            return new JsonResponse([
                'error' => true,
                'message' => 'Clé introuvable.',
            ], Response::HTTP_NOT_FOUND);
        }

        $keyService->revoke($key);

        return new JsonResponse([
            'error' => false,
            'message' => 'Clé révoquée.',
        ]);
    }

    private function getUserFromRequest(Request $request, VerificationsDS $verificationsDS): mixed
    {
        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $verification = $verificationsDS->verifUSer($uid);
        if (($verification['error'] ?? true) === true) {
            return new JsonResponse($verification, Response::HTTP_UNAUTHORIZED);
        }

        return $verification['user'];
    }

    private function notActiveResponse(): JsonResponse
    {
        return new JsonResponse([
            'error' => true,
            'titre' => 'Espace développeur inactif',
            'message' => 'Activez votre statut développeur avant de gérer vos clés API.',
        ], Response::HTTP_FORBIDDEN);
    }
}
