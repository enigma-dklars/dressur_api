<?php

namespace App\Services;

use App\Entity\DeveloperApiKey;
use App\Entity\DeveloperIdempotency;
use App\Entity\FormulePromoReseau;
use App\Entity\PromoReseau;
use App\Entity\Transaction;
use App\Entity\User;
use App\Utilities\ZefameApi;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;

class DeveloperOrderService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PromotionReseauPricing $pricing,
        private readonly ZefameApi $zefameApi,
    ) {
    }

    /**
     * @return array{order: PromoReseau, transaction: Transaction, balance: int, amount: int}
     */
    public function createOrder(
        User $user,
        FormulePromoReseau $formule,
        int $quantity,
        string $url,
        ?string $comments,
        DeveloperApiKey $apiKey,
        ?DeveloperIdempotency $idempotency = null,
    ): array {
        $url = trim($url);
        if (!filter_var($url, FILTER_VALIDATE_URL) || !in_array(strtolower((string)parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)) {
            throw new InvalidArgumentException('Le lien de la publication est invalide.');
        }

        $amount = $this->pricing->calculateAmount($formule, $quantity, $user->isVendeur());
        $currentBalance = (int)($user->getSoldeDressur() ?? 0);
        if ($currentBalance < $amount) {
            throw new InsufficientBalanceException($amount, $currentBalance);
        }

        if ($formule->isCommentairesRequis() && ($comments === null || trim($comments) === '')) {
            throw new InvalidArgumentException('Cette formule exige au moins un commentaire.');
        }

        if ($formule->getIdZefame() === null) {
            throw new RuntimeException('Le service est actuellement indisponible.');
        }

        $this->entityManager->beginTransaction();
        try {
            /** @var User $lockedUser */
            $lockedUser = $this->entityManager->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            if (!$lockedUser) {
                throw new RuntimeException('Le compte utilisateur est introuvable.');
            }

            $lockedBalance = (int)($lockedUser->getSoldeDressur() ?? 0);
            if ($lockedBalance < $amount) {
                throw new InsufficientBalanceException($amount, $lockedBalance);
            }

            $providerParameters = [
                'service' => $formule->getIdZefame(),
                'link' => $url,
                'quantity' => $quantity,
                'runs' => 2,
                'interval' => 5,
            ];
            if ($comments !== null && trim($comments) !== '') {
                $providerParameters['comments'] = $comments;
            }

            $providerResult = $this->zefameApi->order($providerParameters);
            if (!isset($providerResult->order) || $providerResult->order === '') {
                // Une réponse vide peut signifier un refus certain ou une création suivie d’un timeout.
                // On conserve donc la réservation idempotente dans le second cas conservateur.
                throw new ProviderUnavailableException($providerResult === null);
            }

            $order = (new PromoReseau())
                ->setFormulePromoReseau($formule)
                ->setUser($lockedUser)
                ->setQteDemander($quantity)
                ->setPrixFixer($amount)
                ->setUrl($url)
                ->setSource('api')
                ->setCommentaires($comments)
                ->setPrixZefame($formule->getPrixZefame() !== null
                    ? round($quantity * $formule->getPrixZefame() / 1000, 5)
                    : null)
                ->setIdZefame((string)$providerResult->order)
                ->setStatus(2);

            $lockedUser->setSoldeDressur($lockedBalance - $amount);

            $transaction = (new Transaction())
                ->setUser($lockedUser)
                ->setTransactionFor('boost_reseau_sociaux')
                ->setAmount($amount)
                ->setStatus('approved')
                ->setReference($order->getReference())
                ->setAnnotherInfo([
                    'source' => 'api',
                    'promoReference' => $order->getReference(),
                    'developerApiKeyId' => $apiKey->getKeyId(),
                    'formuleId' => $formule->getId(),
                    'quantity' => $quantity,
                ]);

            if ($idempotency !== null) {
                $idempotency->setOrderReference($order->getReference())
                    ->setBalanceAfter($lockedBalance - $amount);
                $this->entityManager->persist($idempotency);
            }

            $this->entityManager->persist($order);
            $this->entityManager->persist($transaction);
            $this->entityManager->flush();
            $this->entityManager->commit();

            return [
                'order' => $order,
                'transaction' => $transaction,
                'balance' => $lockedBalance - $amount,
                'amount' => $amount,
            ];
        } catch (\Throwable $exception) {
            try {
                $this->entityManager->rollback();
            } catch (\Throwable) {
            }
            throw $exception;
        }
    }
}

class InsufficientBalanceException extends RuntimeException
{
    public function __construct(private readonly int $requiredAmount, private readonly int $availableBalance)
    {
        parent::__construct('Solde Dressur insuffisant pour cette commande.');
    }

    public function getRequiredAmount(): int
    {
        return $this->requiredAmount;
    }

    public function getAvailableBalance(): int
    {
        return $this->availableBalance;
    }
}

class ProviderUnavailableException extends RuntimeException
{
    public function __construct(private readonly bool $requestMayHaveReachedProvider = false)
    {
        parent::__construct('Service actuellement indisponible.');
    }

    public function mayHaveReachedProvider(): bool
    {
        return $this->requestMayHaveReachedProvider;
    }
}
