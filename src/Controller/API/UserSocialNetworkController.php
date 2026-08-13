<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Entity\UserSocialNetwork;
use App\Repository\UserSocialNetworkRepository;
use App\Services\CookieDS;
use App\Services\PublicSocialNetworkCatalog;
use App\Services\PublicSocialNetworkUrlValidator;
use App\Services\VerificationsDS;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/user/social-networks', name: 'api_user_social_networks_')]
final class UserSocialNetworkController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserSocialNetworkRepository $repository,
        private readonly CookieDS $cookieDS,
        private readonly PublicSocialNetworkCatalog $catalog,
        private readonly PublicSocialNetworkUrlValidator $urlValidator,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request, VerificationsDS $verificationsDS): JsonResponse
    {
        $user = $this->authenticatedUser($request, $verificationsDS);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        return new JsonResponse([
            'error' => false,
            'networks' => array_map(
                fn (UserSocialNetwork $network): array => $this->serializeNetwork($network),
                $this->repository->findForUser($user)
            ),
        ]);
    }

    #[Route('/catalog', name: 'catalog', methods: ['GET'])]
    public function catalog(Request $request, VerificationsDS $verificationsDS): JsonResponse
    {
        $user = $this->authenticatedUser($request, $verificationsDS);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        return new JsonResponse([
            'error' => false,
            'networks' => $this->catalog->all(),
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request, VerificationsDS $verificationsDS): JsonResponse
    {
        $user = $this->authenticatedUser($request, $verificationsDS);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $payload = $this->getPayload($request);
        $networkType = $this->getStringValue($payload, 'networkType')
            ?? $this->getStringValue($payload, 'network_type');
        $url = $this->getStringValue($payload, 'url');

        $network = $networkType === null ? null : $this->catalog->get($networkType);
        if ($network === null) {
            return $this->errorResponse(
                'network_invalid',
                'Réseau invalide',
                'Le réseau sélectionné n’est pas autorisé.'
            );
        }

        $validationError = $this->urlValidator->getError($network['id'], $url);
        if ($validationError !== null) {
            return $this->errorResponse('url_invalid', 'URL invalide', $validationError);
        }

        if ($this->repository->findOneForUser($user, $network['id']) !== null) {
            return $this->errorResponse(
                'network_already_exists',
                'Réseau déjà enregistré',
                'Ce réseau est déjà enregistré dans votre profil.',
                Response::HTTP_CONFLICT
            );
        }

        $socialNetwork = (new UserSocialNetwork())
            ->setUser($user)
            ->setNetworkType($network['id'])
            ->setUrl(trim($url));

        $this->entityManager->persist($socialNetwork);

        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException) {
            $this->entityManager->clear();

            return $this->errorResponse(
                'network_already_exists',
                'Réseau déjà enregistré',
                'Ce réseau est déjà enregistré dans votre profil.',
                Response::HTTP_CONFLICT
            );
        }

        return new JsonResponse([
            'error' => false,
            'network' => $this->serializeNetwork($socialNetwork),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{networkType}', name: 'update', requirements: ['networkType' => '[a-z0-9-]+'], methods: ['PUT', 'PATCH'])]
    public function update(string $networkType, Request $request, VerificationsDS $verificationsDS): JsonResponse
    {
        $user = $this->authenticatedUser($request, $verificationsDS);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $network = $this->catalog->get($networkType);
        if ($network === null) {
            return $this->errorResponse(
                'network_not_found',
                'Réseau introuvable',
                'Ce réseau n’est pas enregistré dans votre profil.',
                Response::HTTP_NOT_FOUND
            );
        }

        $socialNetwork = $this->repository->findOneForUser($user, $network['id']);
        if ($socialNetwork === null) {
            return $this->errorResponse(
                'network_not_found',
                'Réseau introuvable',
                'Ce réseau n’est pas enregistré dans votre profil.',
                Response::HTTP_NOT_FOUND
            );
        }

        $url = $this->getStringValue($this->getPayload($request), 'url');
        $validationError = $this->urlValidator->getError($network['id'], $url);
        if ($validationError !== null) {
            return $this->errorResponse('url_invalid', 'URL invalide', $validationError);
        }

        $socialNetwork->setUrl(trim($url));
        $this->entityManager->flush();

        return new JsonResponse([
            'error' => false,
            'network' => $this->serializeNetwork($socialNetwork),
        ]);
    }

    #[Route('/{networkType}', name: 'delete', requirements: ['networkType' => '[a-z0-9-]+'], methods: ['DELETE'])]
    public function delete(string $networkType, Request $request, VerificationsDS $verificationsDS): JsonResponse
    {
        $user = $this->authenticatedUser($request, $verificationsDS);
        if ($user instanceof JsonResponse) {
            return $user;
        }

        $network = $this->catalog->get($networkType);
        if ($network === null) {
            return $this->errorResponse(
                'network_not_found',
                'Réseau introuvable',
                'Ce réseau n’est pas enregistré dans votre profil.',
                Response::HTTP_NOT_FOUND
            );
        }

        $socialNetwork = $this->repository->findOneForUser($user, $network['id']);
        if ($socialNetwork === null) {
            return $this->errorResponse(
                'network_not_found',
                'Réseau introuvable',
                'Ce réseau n’est pas enregistré dans votre profil.',
                Response::HTTP_NOT_FOUND
            );
        }

        $this->entityManager->remove($socialNetwork);
        $this->entityManager->flush();

        return new JsonResponse([
            'error' => false,
            'networkType' => $network['id'],
        ]);
    }

    private function authenticatedUser(Request $request, VerificationsDS $verificationsDS): User|JsonResponse
    {
        // Cookie signed first; the existing uid fallback keeps mobile clients compatible.
        // A free-form user_id is never read or used by these endpoints.
        $uid = $this->cookieDS->getWithFallback('uid', $request);
        if ($uid === false || trim((string) $uid) === '') {
            return $this->errorResponse(
                'authentication_required',
                'Authentification requise',
                'Veuillez vous connecter pour gérer vos réseaux publics.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $verificationUser = $verificationsDS->verifUSer($uid);
        if (($verificationUser['error'] ?? true) === true) {
            $status = ($verificationUser['blocked'] ?? false)
                ? Response::HTTP_FORBIDDEN
                : Response::HTTP_UNAUTHORIZED;

            return new JsonResponse([
                'error' => true,
                'code' => ($verificationUser['blocked'] ?? false)
                    ? 'account_blocked'
                    : 'session_invalid',
                'titre' => $verificationUser['titre'] ?? 'Authentification invalide',
                'message' => $verificationUser['message'] ?? 'Votre session n’est plus valide.',
                'deleted' => $verificationUser['deleted'] ?? false,
                'blocked' => $verificationUser['blocked'] ?? false,
            ], $status);
        }

        return $verificationUser['user'];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPayload(Request $request): array
    {
        $formPayload = $request->request->all();
        if ($formPayload !== []) {
            return $formPayload;
        }

        $content = trim($request->getContent());
        if ($content === '') {
            return [];
        }

        $jsonPayload = json_decode($content, true);

        return is_array($jsonPayload) ? $jsonPayload : [];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function getStringValue(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return is_string($value) ? trim($value) : null;
    }

    /**
     * @return array{id: int|null, networkType: string|null, url: string|null, createdAt: string|null, updatedAt: string|null}
     */
    private function serializeNetwork(UserSocialNetwork $network): array
    {
        return [
            'id' => $network->getId(),
            'networkType' => $network->getNetworkType(),
            'url' => $network->getUrl(),
            'createdAt' => $network->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $network->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }

    private function errorResponse(
        string $code,
        string $title,
        string $message,
        int $status = Response::HTTP_BAD_REQUEST
    ): JsonResponse {
        return new JsonResponse([
            'error' => true,
            'code' => $code,
            'titre' => $title,
            'message' => $message,
        ], $status);
    }
}