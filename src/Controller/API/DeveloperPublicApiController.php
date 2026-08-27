<?php

namespace App\Controller\API;

use App\Entity\DeveloperIdempotency;
use App\Entity\FormulePromoReseau;
use App\Entity\PromoReseau;
use App\Entity\User;
use App\Repository\DeveloperIdempotencyRepository;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\PromoReseauRepository;
use App\Services\DeveloperApiKeyService;
use App\Services\DeveloperOrderService;
use App\Services\DeveloperApiProtectionService;
use App\Services\DeveloperApiAuditService;
use App\Services\DeveloperOrderStatusService;
use App\Services\InsufficientBalanceException;
use App\Services\ProviderUnavailableException;
use App\Services\PromotionReseauPricing;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/v1/developer', name: 'api_v1_developer_')]
class DeveloperPublicApiController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly DeveloperApiKeyService $keyService,
        private readonly FormulePromoReseauRepository $formuleRepository,
        private readonly PromoReseauRepository $promoRepository,
        private readonly DeveloperIdempotencyRepository $idempotencyRepository,
        private readonly PromotionReseauPricing $pricing,
        private readonly TraitementsDS $traitementsDS,
        private readonly DeveloperOrderService $orderService,
        private readonly DeveloperApiAuditService $auditService,
    ) {
    }

    #[Route('/catalog', name: 'catalog', methods: ['GET'])]
    public function catalog(Request $request): JsonResponse
    {
        $apiKey = $this->authenticate($request, DeveloperApiKeyService::SCOPE_CATALOG_READ);
        if ($apiKey instanceof JsonResponse) {
            return $apiKey;
        }

        $user = $apiKey->getDeveloperProfile()->getUser();
        $isVendeur = $user?->isVendeur() ?? false;
        $services = [];
        foreach ($this->formuleRepository->findBy(['available' => true]) as $formule) {
            if ($formule->getParent() === null || $formule->getIdZefame() === null) {
                continue;
            }

            try {
                $referenceQuantity = (int)$formule->getQte();
                $referencePrice = $this->pricing->calculateAmount($formule, $referenceQuantity, $isVendeur);
            } catch (\InvalidArgumentException) {
                continue;
            }

            $services[] = [
                'id' => $formule->getId(),
                'title' => $this->getFullTitle($formule),
                'description' => $formule->getDescription(),
                'referenceQuantity' => $formule->getQte(),
                'minimumQuantity' => $formule->getQteMin(),
                'maximumQuantity' => $formule->getQteMax(),
                'price' => $referencePrice,
                'currency' => 'XOF',
                'commentsRequired' => $formule->isCommentairesRequis(),
            ];
        }

        $this->flushKeyUsage($apiKey, $request, Response::HTTP_OK);

        return new JsonResponse([
            'error' => false,
            'services' => $services,
        ]);
    }

    #[Route('/balance', name: 'balance', methods: ['GET'])]
    public function balance(Request $request): JsonResponse
    {
        $apiKey = $this->authenticate($request, DeveloperApiKeyService::SCOPE_BALANCE_READ);
        if ($apiKey instanceof JsonResponse) {
            return $apiKey;
        }

        $user = $apiKey->getDeveloperProfile()->getUser();
        $this->flushKeyUsage($apiKey, $request, Response::HTTP_OK);

        return new JsonResponse([
            'error' => false,
            'balance' => (int)($user?->getSoldeDressur() ?? 0),
            'currency' => 'XOF',
        ]);
    }

    #[Route('/orders', name: 'orders_list', methods: ['GET'])]
    public function orders(Request $request): JsonResponse
    {
        $apiKey = $this->authenticate($request, DeveloperApiKeyService::SCOPE_ORDERS_READ);
        if ($apiKey instanceof JsonResponse) {
            return $apiKey;
        }

        $user = $apiKey->getDeveloperProfile()->getUser();
        $limit = min(100, max(1, (int)$request->query->get('limit', 50)));
        $orders = $this->promoRepository->findBy(['user' => $user, 'source' => 'api'], ['id' => 'DESC'], $limit);
        $this->flushKeyUsage($apiKey, $request, Response::HTTP_OK);

        return new JsonResponse([
            'error' => false,
            'orders' => array_map(fn (PromoReseau $order): array => $this->serializeOrder($order), $orders),
        ]);
    }

    #[Route('/orders/{reference}/status', name: 'order_status', methods: ['GET'])]
    public function status(string $reference, Request $request, DeveloperApiProtectionService $protectionService, DeveloperOrderStatusService $statusService): JsonResponse
    {
        $apiKey = $this->authenticate($request, DeveloperApiKeyService::SCOPE_STATUS_READ);
        if ($apiKey instanceof JsonResponse) {
            return $apiKey;
        }

        $user = $apiKey->getDeveloperProfile()->getUser();
        $rate = $protectionService->consume(
            $request,
            $apiKey->getKeyId(),
            $protectionService->statusLimits($request, $apiKey->getKeyId(), $user?->getId() ?? 0)
        );
        if (!$rate['allowed']) {
            $response = $this->errorResponse('Trop de demandes de statut. Veuillez respecter le délai indiqué.', Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->set('Retry-After', (string)$rate['retryAfter']);
            return $response;
        }

        $order = $this->promoRepository->findOneBy([
            'reference' => $reference,
            'user' => $user,
            'source' => 'api',
        ]);
        if (!$order) {
            return $this->errorResponse('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        $result = $statusService->getStatuses([$order]);
        $this->flushKeyUsage($apiKey, $request, Response::HTTP_OK);
        $status = $result['statuses'][$reference] ?? null;

        return new JsonResponse([
            'error' => false,
            'order' => $status,
            'providerAvailable' => $result['providerAvailable'],
        ]);
    }

    #[Route('/orders/status-batch', name: 'orders_status_batch', methods: ['POST'])]
    public function statusBatch(Request $request, DeveloperApiProtectionService $protectionService, DeveloperOrderStatusService $statusService): JsonResponse
    {
        $apiKey = $this->authenticate($request, DeveloperApiKeyService::SCOPE_STATUS_READ);
        if ($apiKey instanceof JsonResponse) {
            return $apiKey;
        }

        $user = $apiKey->getDeveloperProfile()->getUser();
        $rate = $protectionService->consume(
            $request,
            $apiKey->getKeyId(),
            $protectionService->statusLimits($request, $apiKey->getKeyId(), $user?->getId() ?? 0)
        );
        if (!$rate['allowed']) {
            $response = $this->errorResponse('Trop de demandes de statut. Veuillez respecter le délai indiqué.', Response::HTTP_TOO_MANY_REQUESTS);
            $response->headers->set('Retry-After', (string)$rate['retryAfter']);
            return $response;
        }

        $payload = json_decode($request->getContent(), true);
        $references = is_array($payload) ? ($payload['references'] ?? []) : [];
        if (!is_array($references)) {
            return $this->errorResponse('references doit être une liste.', Response::HTTP_BAD_REQUEST);
        }
        $references = array_values(array_unique(array_filter($references, static fn ($value): bool => is_string($value) && preg_match('/^pr_[a-f0-9]{24}$/', $value) === 1)));
        if ($references === [] || count($references) > 50) {
            return $this->errorResponse('La requête doit contenir entre 1 et 50 références valides.', Response::HTTP_BAD_REQUEST);
        }

        $orders = $this->promoRepository->findBy([
            'reference' => $references,
            'user' => $user,
            'source' => 'api',
        ]);
        $result = $statusService->getStatuses($orders);
        $this->flushKeyUsage($apiKey, $request, Response::HTTP_OK);

        return new JsonResponse([
            'error' => false,
            'statuses' => $result['statuses'],
            'providerAvailable' => $result['providerAvailable'],
        ]);
    }

    #[Route('/orders/{reference}', name: 'order_show', methods: ['GET'])]
    public function order(string $reference, Request $request): JsonResponse
    {
        $apiKey = $this->authenticate($request, DeveloperApiKeyService::SCOPE_ORDERS_READ);
        if ($apiKey instanceof JsonResponse) {
            return $apiKey;
        }

        $user = $apiKey->getDeveloperProfile()->getUser();
        $order = $this->promoRepository->findOneBy([
            'reference' => $reference,
            'user' => $user,
            'source' => 'api',
        ]);
        if (!$order) {
            return $this->errorResponse('Commande introuvable.', Response::HTTP_NOT_FOUND);
        }

        $this->flushKeyUsage($apiKey, $request, Response::HTTP_OK);
        return new JsonResponse([
            'error' => false,
            'order' => $this->serializeOrder($order),
        ]);
    }

    #[Route('/orders', name: 'order_create', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        $apiKey = $this->authenticate($request, DeveloperApiKeyService::SCOPE_ORDERS_WRITE);
        if ($apiKey instanceof JsonResponse) {
            return $apiKey;
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->errorResponse('Le corps de la requête doit être un objet JSON.', Response::HTTP_BAD_REQUEST);
        }

        $idempotencyKey = trim((string)$request->headers->get('Idempotency-Key', ''));
        if (!preg_match('/^[A-Za-z0-9._:-]{1,160}$/', $idempotencyKey)) {
            return $this->errorResponse('L’en-tête Idempotency-Key est obligatoire et invalide.', Response::HTTP_BAD_REQUEST);
        }

        $profile = $apiKey->getDeveloperProfile();
        ksort($payload);
        $requestHash = hash('sha256', (string)json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $existing = $this->idempotencyRepository->findOneBy([
            'developerProfile' => $profile,
            'idempotencyKey' => $idempotencyKey,
        ]);
        if ($existing) {
            if ($existing->isExpired()) {
                $this->entityManager->remove($existing);
                $this->entityManager->flush();
            } elseif ($existing->getRequestHash() !== $requestHash) {
                return $this->errorResponse('Cette Idempotency-Key a déjà été utilisée avec une requête différente.', Response::HTTP_UNPROCESSABLE_ENTITY);
            } elseif ($existing->getResponseBody() !== []) {
                return new JsonResponse($existing->getResponseBody(), $existing->getResponseStatus());
            } elseif ($existing->getOrderReference() !== null) {
                $replayOrder = $this->promoRepository->findOneBy([
                    'reference' => $existing->getOrderReference(),
                    'user' => $profile->getUser(),
                    'source' => 'api',
                ]);
                if ($replayOrder) {
                    $replayBody = [
                        'error' => false,
                        'order' => $this->serializeOrder($replayOrder),
                        'amount' => $replayOrder->getPrixFixer(),
                        'currency' => 'XOF',
                        'balance' => $existing->getBalanceAfter(),
                    ];
                    $existing->setResponseBody($replayBody)->setResponseStatus(Response::HTTP_CREATED);
                    $this->entityManager->flush();
                    return new JsonResponse($replayBody, Response::HTTP_CREATED);
                }
                return $this->errorResponse('Cette requête est déjà en cours de finalisation. Réessayez avec la même clé.', Response::HTTP_CONFLICT);
            } else {
                return $this->errorResponse('Cette requête est déjà en cours de traitement.', Response::HTTP_CONFLICT);
            }
        }

        $reservation = (new DeveloperIdempotency())
            ->setDeveloperProfile($profile)
            ->setIdempotencyKey($idempotencyKey)
            ->setRequestHash($requestHash)
            ->setResponseBody([])
            ->setResponseStatus(Response::HTTP_ACCEPTED);
        $this->entityManager->persist($reservation);
        try {
            $this->entityManager->flush();
        } catch (\Throwable) {
            $existing = $this->idempotencyRepository->findOneBy([
                'developerProfile' => $profile,
                'idempotencyKey' => $idempotencyKey,
            ]);
            if ($existing && $existing->getResponseBody() !== []) {
                return new JsonResponse($existing->getResponseBody(), $existing->getResponseStatus());
            }
            return $this->errorResponse('Cette requête est déjà en cours de traitement.', Response::HTTP_CONFLICT);
        }

        try {
            $formuleId = $payload['formuleId'] ?? $payload['serviceId'] ?? null;
            $quantityValue = $payload['quantity'] ?? null;
            $url = $payload['link'] ?? $payload['url'] ?? null;
            if (!is_int($formuleId) && !(is_string($formuleId) && ctype_digit($formuleId))) {
                throw new \InvalidArgumentException('formuleId est obligatoire.');
            }
            if (!is_int($quantityValue) && !(is_string($quantityValue) && ctype_digit($quantityValue))) {
                throw new \InvalidArgumentException('quantity est obligatoire et doit être un entier positif.');
            }
            if (!is_string($url) || trim($url) === '') {
                throw new \InvalidArgumentException('link est obligatoire.');
            }

            $formule = $this->formuleRepository->find((int)$formuleId);
            if (!$formule || $formule->getParent() === null || !$formule->isAvailable()) {
                throw new \InvalidArgumentException('Le service demandé est indisponible.');
            }

            $quantity = (int)$quantityValue;
            $comments = $this->prepareComments($payload['comments'] ?? null, $formule, $quantity);
            $result = $this->orderService->createOrder(
                $profile->getUser(),
                $formule,
                $quantity,
                $url,
                $comments,
                $apiKey,
                $reservation,
            );

            $responseBody = [
                'error' => false,
                'order' => $this->serializeOrder($result['order']),
                'amount' => $result['amount'],
                'currency' => 'XOF',
                'balance' => $result['balance'],
            ];
            $reservation->setResponseBody($responseBody)->setResponseStatus(Response::HTTP_CREATED);
            $this->entityManager->flush();
            return new JsonResponse($responseBody, Response::HTTP_CREATED);
        } catch (InsufficientBalanceException $exception) {
            $this->entityManager->remove($reservation);
            $this->entityManager->flush();
            return new JsonResponse([
                'error' => true,
                'code' => 'insufficient_balance',
                'message' => 'Solde Dressur insuffisant pour cette commande.',
                'requiredAmount' => $exception->getRequiredAmount(),
                'availableBalance' => $exception->getAvailableBalance(),
                'currency' => 'XOF',
            ], Response::HTTP_PAYMENT_REQUIRED);
        } catch (ProviderUnavailableException $exception) {
            if (!$exception->mayHaveReachedProvider()) {
                $this->entityManager->remove($reservation);
                $this->entityManager->flush();
            } else {
                $this->storeIdempotentError($reservation, Response::HTTP_SERVICE_UNAVAILABLE);
            }
            return $this->errorResponse('Service actuellement indisponible. Veuillez patienter ou contacter les administrateurs.', Response::HTTP_SERVICE_UNAVAILABLE);
        } catch (\InvalidArgumentException $exception) {
            $this->entityManager->remove($reservation);
            $this->entityManager->flush();
            return $this->errorResponse($exception->getMessage(), Response::HTTP_BAD_REQUEST);
        } catch (\Throwable) {
            // Une erreur interne après la réservation ne doit jamais permettre un second appel
            // fournisseur avec la même clé. La réservation reste donc rejouable jusqu’à expiration.
            $this->storeIdempotentError($reservation, Response::HTTP_SERVICE_UNAVAILABLE);
            return $this->errorResponse('Service actuellement indisponible. Veuillez patienter ou contacter les administrateurs.', Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    private function storeIdempotentError(DeveloperIdempotency $reservation, int $status): void
    {
        try {
            $reservation->setResponseBody([
                'error' => true,
                'message' => 'Service actuellement indisponible. Veuillez patienter ou contacter les administrateurs.',
            ])->setResponseStatus($status);
            $this->entityManager->flush();
        } catch (\Throwable) {
            // Le verrou unique de la clé reste la dernière protection contre le rejeu concurrent.
        }
    }

    private function authenticate(Request $request, string $scope): mixed
    {
        $apiKey = $this->keyService->authenticate($request);
        if (!$apiKey) {
            return $this->errorResponse('Clé API invalide, révoquée ou expirée.', Response::HTTP_UNAUTHORIZED);
        }
        if (!$this->keyService->hasScope($apiKey, $scope)) {
            return $this->errorResponse('Cette clé API ne possède pas le scope requis.', Response::HTTP_FORBIDDEN);
        }

        return $apiKey;
    }

    private function flushKeyUsage($apiKey, Request $request, int $responseStatus): void
    {
        $this->auditService->record($apiKey, $request, $responseStatus);
        $this->entityManager->flush();
    }

    private function prepareComments(mixed $rawComments, FormulePromoReseau $formule, int $quantity): ?string
    {
        if (!$formule->isCommentairesRequis()) {
            return null;
        }
        if (!is_array($rawComments)) {
            throw new \InvalidArgumentException('comments doit être une liste JSON de commentaires.');
        }
        $lines = [];
        foreach ($rawComments as $comment) {
            if (!is_string($comment)) {
                throw new \InvalidArgumentException('Chaque commentaire doit être une chaîne de caractères.');
            }
            $lines[] = $comment;
        }

        return $this->traitementsDS->preparerCommentairesPourQuantite(implode("\n", $lines), $quantity);
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeOrder(PromoReseau $order): array
    {
        $formule = $order->getFormulePromoReseau();
        return [
            'reference' => $order->getReference(),
            'serviceId' => $formule?->getId(),
            'title' => $formule ? $this->getFullTitle($formule) : null,
            'quantity' => $order->getQteDemander(),
            'amount' => $order->getPrixFixer(),
            'currency' => 'XOF',
            'link' => $order->getUrl(),
            'status' => $this->statusLabel($order->getStatus()),
            'statusNumber' => $order->getStatus(),
            'source' => $order->getSource(),
            'commentsRequired' => $formule?->isCommentairesRequis() ?? false,
            'createdAt' => $order->getCreatedAt()?->format(DATE_ATOM),
            'updatedAt' => $order->getUpdatedAt()?->format(DATE_ATOM),
        ];
    }

    private function getFullTitle(FormulePromoReseau $formule): string
    {
        $parentTitle = $formule->getParent()?->getTitre();
        return $parentTitle ? $parentTitle . ' : ' . $formule->getTitre() : (string)$formule->getTitre();
    }

    private function statusLabel(?int $status): string
    {
        return match ($status) {
            0 => 'invalid_url',
            1 => 'pending',
            2 => 'in_progress',
            3 => 'completed',
            default => 'unknown',
        };
    }

    private function errorResponse(string $message, int $status): JsonResponse
    {
        return new JsonResponse([
            'error' => true,
            'message' => $message,
        ], $status);
    }
}
