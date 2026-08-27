<?php

namespace App\Controller\API;

use App\Entity\Transaction as EntityTransaction;
use App\Repository\DeveloperProfileRepository;
use App\Repository\EnvRepository;
use App\Repository\MethodePaiementRepository;
use App\Repository\PromoReseauRepository;
use App\Repository\TransactionRepository;
use App\Services\CookieDS;
use App\Services\DeveloperAccessService;
use App\Services\TraitementsDS;
use App\Services\VerificationsDS;
use App\Utilities\SendMail;
use FedaPay\FedaPay;
use FedaPay\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/developpeur', name: 'api_developpeur_')]
class DeveloperController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly EnvRepository $envRepository,
        private readonly CookieDS $cookieDS,
        private readonly SendMail $sendMail,
    ) {
    }

    #[Route('/conditions', name: 'conditions', methods: ['GET'])]
    public function conditions(
        Request $request,
        VerificationsDS $verificationsDS,
        DeveloperAccessService $developerAccessService,
    ): JsonResponse {
        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $verification = $verificationsDS->verifUSer($uid);
        if ($verification['error'] === true) {
            return new JsonResponse($verification);
        }

        $user = $verification['user'];
        $profile = $user->getDeveloperProfile();

        return new JsonResponse([
            'error' => false,
            'active' => $profile?->isActive() ?? false,
            'status' => $profile?->getStatus() ?? 'inactive',
            'minimumRecharge' => $developerAccessService->getMinimumRecharge(),
            'activationConfigured' => $developerAccessService->isActivationConfigured(),
            'eligibility' => $developerAccessService->getEligibility($user),
            'conditionsVersion' => DeveloperAccessService::CONDITIONS_VERSION,
        ]);
    }

    #[Route('/historique', name: 'historique', methods: ['GET'])]
    public function historique(Request $request, VerificationsDS $verificationsDS, PromoReseauRepository $promoRepository): JsonResponse
    {
        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $verification = $verificationsDS->verifUSer($uid);
        if (($verification['error'] ?? true) === true) {
            return new JsonResponse($verification, Response::HTTP_UNAUTHORIZED);
        }

        $user = $verification['user'];
        $orders = $promoRepository->findBy(['user' => $user, 'source' => 'api'], ['id' => 'DESC'], 100);
        return new JsonResponse([
            'error' => false,
            'orders' => array_map(static function ($order): array {
                return [
                    'reference' => $order->getReference(),
                    'quantity' => $order->getQteDemander(),
                    'amount' => $order->getPrixFixer(),
                    'statusNumber' => $order->getStatus(),
                    'createdAt' => $order->getCreatedAt()?->format(DATE_ATOM),
                ];
            }, $orders),
        ]);
    }

    #[Route('/activation', name: 'activation', methods: ['POST'])]
    public function activation(
        Request $request,
        TraitementsDS $traitementsDS,
        VerificationsDS $verificationsDS,
        MethodePaiementRepository $methodePaiementRepository,
        TransactionRepository $transactionRepository,
        DeveloperAccessService $developerAccessService,
    ): JsonResponse {
        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $verification = $verificationsDS->verifUSer($uid);
        if ($verification['error'] === true) {
            return new JsonResponse($verification);
        }

        $user = $verification['user'];
        if ($user->isDeveloper()) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Déjà développeur',
                'message' => 'Votre compte possède déjà le statut développeur.',
            ], Response::HTTP_CONFLICT);
        }

        $minimumRecharge = $developerAccessService->getMinimumRecharge();
        if ($minimumRecharge <= 0) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Activation indisponible',
                'message' => 'L’activation développeur est temporairement indisponible. Veuillez contacter l’assistance par WhatsApp.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        foreach ($developerAccessService->getEligibility($user) as $condition => $value) {
            if ($condition !== 'conditionsAccepted' && $value !== true) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Conditions non remplies',
                    'message' => 'Veuillez compléter et confirmer votre compte avant de demander le statut développeur.',
                    'eligibility' => $developerAccessService->getEligibility($user),
                ], Response::HTTP_BAD_REQUEST);
            }
        }

        $conditionsAccepted = filter_var(
            $request->request->get('conditionsAccepted', false),
            FILTER_VALIDATE_BOOLEAN
        );
        if (!$conditionsAccepted) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Conditions requises',
                'message' => 'Vous devez accepter les conditions d’utilisation de l’API développeur.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $amountValue = $request->request->get('montantRecharge');
        if (!is_scalar($amountValue) || !preg_match('/^[1-9]\d*$/', trim((string)$amountValue))) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Montant invalide',
                'message' => 'Veuillez saisir un montant de recharge valide.',
            ], Response::HTTP_BAD_REQUEST);
        }
        $amount = (int)trim((string)$amountValue);
        if ($amount < $minimumRecharge) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Recharge insuffisante',
                'message' => sprintf('Le montant minimum de recharge développeur est de %d FCFA.', $minimumRecharge),
                'minimumRecharge' => $minimumRecharge,
            ], Response::HTTP_BAD_REQUEST);
        }

        $methodId = $request->request->get('methodePaiementId');
        $paymentMethod = $methodePaiementRepository->find($methodId);
        if (!$paymentMethod) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Moyen de paiement invalide',
                'message' => 'Veuillez choisir un moyen de paiement valide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $pendingTransaction = $transactionRepository->findOneBy([
            'user' => $user,
            'transactionFor' => 'activation_developpeur',
            'status' => 'pending',
        ]);
        if ($pendingTransaction) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Paiement déjà en cours',
                'message' => 'Une demande d’activation développeur est déjà en cours de paiement.',
            ], Response::HTTP_CONFLICT);
        }

        $tel = trim((string)$request->request->get('tel', $user->getTel() ?? ''));
        if ($tel === '') {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Numéro requis',
                'message' => 'Veuillez renseigner un numéro de téléphone valide.',
            ], Response::HTTP_BAD_REQUEST);
        }

        $profile = $developerAccessService->getOrCreateProfile($user);
        $acceptedAt = new \DateTime();
        $profile
            ->setStatus('pending')
            ->setConditionsVersion(DeveloperAccessService::CONDITIONS_VERSION)
            ->setConditionsAcceptedAt($acceptedAt)
            ->setActivationAmount($amount);

        $anotherInfo = [
            'userId' => $user->getId(),
            'userUid' => $user->getUid(),
            'montantRecharge' => $amount,
            'minimumRecharge' => $minimumRecharge,
            'conditionsVersion' => DeveloperAccessService::CONDITIONS_VERSION,
            'conditionsAcceptedAt' => $acceptedAt->format(DATE_ATOM),
        ];

        try {
            if ($paymentMethod->getAggregator() === 'FedaPay') {
                $envPaymentApi = $traitementsDS->getEnvPaiementApiFedaPayDisponible();
                if (!$envPaymentApi) {
                    throw new \RuntimeException('Aucune configuration FedaPay disponible.');
                }
                FedaPay::setApiKey($envPaymentApi->getApiKey());
                FedaPay::setEnvironment($envPaymentApi->getEnvironment());
                $externalTransaction = Transaction::create([
                    'description' => 'Dressur : Activation développeur - ' . $amount . ' FCFA : ' . $user->getPseudo(),
                    'amount' => $amount,
                    'currency' => ['iso' => 'XOF'],
                    'customer' => [
                        'firstname' => 'Dressur : ' . $user->getPseudo(),
                        'lastname' => $user->getNom(),
                        'email' => $user->getMail(),
                        'phone_number' => [
                            'number' => $tel,
                            'country' => $paymentMethod->getCodePays(),
                        ],
                    ],
                ]);

                $transaction = (new EntityTransaction())
                    ->setUser($user)
                    ->setTransactionFor('activation_developpeur')
                    ->setIdTransaction($externalTransaction['id'])
                    ->setReference($externalTransaction['reference'])
                    ->setAmount($externalTransaction['amount'])
                    ->setStatus($externalTransaction['status'])
                    ->setCustomerId($externalTransaction['customer_id'])
                    ->setCurrencyId($externalTransaction['currency_id'])
                    ->setAnnotherInfo($anotherInfo);
                $this->entityManager->persist($transaction);
                $this->entityManager->flush();

                return new JsonResponse($traitementsDS->startPaiementFedaPay($externalTransaction, $paymentMethod));
            }

            $envPaymentApi = $paymentMethod->getAggregator() === 'KPay'
                ? $traitementsDS->getEnvPaiementApiKPayDisponible()
                : $traitementsDS->getEnvPaiementApiFeexPayDisponible();
            if (!$envPaymentApi) {
                throw new \RuntimeException('Aucune configuration de paiement disponible.');
            }

            $result = $paymentMethod->getAggregator() === 'KPay'
                ? $traitementsDS->startPaiementKPay($envPaymentApi, $paymentMethod, $amount, $tel, $user->getPseudo(), $user->getMail(), 'activation_developpeur', $anotherInfo, $user, $request->getSchemeAndHttpHost())
                : $traitementsDS->startPaiementFeexPay($envPaymentApi, $paymentMethod, $amount, $tel, $user->getPseudo(), $user->getMail(), 'activation_developpeur', $anotherInfo, $user, $request->getSchemeAndHttpHost());

            return new JsonResponse($result);
        } catch (\Throwable $exception) {
            $this->sendMail->sendReport('Erreur activation développeur : ' . $user->getUid(), $exception);

            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur de paiement',
                'message' => 'Nous avons rencontré une erreur. Veuillez réessayer ou contacter l’assistance.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
