<?php

namespace App\Controller\API;

use FedaPay\FedaPay;
use FedaPay\Transaction;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use App\Repository\MethodePaiementRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Transaction as EntityTransaction;
use App\Services\CookieDS;
use App\Utilities\SendMail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]

class VendeurController extends AbstractController
{
    private $em;
    private $env;
    private $sendMail;
    private $cookieDS;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, SendMail $sendMail, CookieDS $cookieDS)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->sendMail = $sendMail;
        $this->cookieDS = $cookieDS;
    }

    #[Route('/vendeur/adhesion', name: 'vendeur_adhesion', methods: ['POST'])]
    public function adhesion(Request $request, TraitementsDS $traitementsDS, VerificationsDS $verificationsDS, SessionDS $sessionDS, UserRepository $userRepository, MethodePaiementRepository $methodePaiementRepository, TransactionRepository $transactionRepository): Response
    {
        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;

        $verificationUser = $verificationsDS->verifUSer($uid);
        if ($verificationUser["error"] == true) {
            return new JsonResponse([
                'error' => true,
                'titre' => $verificationUser["titre"],
                'message' => $verificationUser["message"],
                'deleted' => $verificationUser["deleted"],
                'blocked' => $verificationUser["blocked"],
            ]);
        }
        $user = $verificationUser["user"];

        if ($user->isVendeur() == true) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Déjà vendeur',
                'message' => 'Vous êtes déjà vendeur sur Dressur.',
            ]);
        }

        $fraisAdhesion = 2000;

        $tel = trim((string)$request->request->get('tel', ''));
        if (empty($tel)) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Numéro requis',
                'message' => 'Veuillez renseigner votre numéro de téléphone au format international (ex: +22890000000).',
            ]);
        }

        $montantRecharge = (int)$request->request->get('montantRecharge', 0);
        if ($montantRecharge < 500) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Montant insuffisant',
                'message' => 'Un montant de recharge initial minimum de 500 FCFA est requis pour l\'adhésion.',
            ]);
        }

        $montant = $fraisAdhesion + $montantRecharge;

        $methodePaiementId = $request->request->get('methodePaiementId');
        $methodePaiementEntity = $methodePaiementRepository->find($methodePaiementId);
        if (!$methodePaiementEntity) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez choisir une méthode de paiement valide.',
            ]);
        }

        $anotherInfo = ['userId' => $user->getId(), 'userUid' => $user->getUid(), 'montantRecharge' => $montantRecharge];

        if ($methodePaiementEntity->getAggregator() == "FedaPay") {
            $envPaiementApi = $traitementsDS->getEnvPaiementApiFedaPayDisponible();
            if (!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : " . $uid, "Aucun Webhook Disponible pour FedaPay");
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                ]);
            }
            FedaPay::setApiKey($envPaiementApi->getApiKey());
            FedaPay::setEnvironment($envPaiementApi->getEnvironment());

            try {
                $transaction = Transaction::create([
                    "description" => "Dressur : Adhésion Vendeur - " . $montant . " FCFA : " . $user->getPseudo() . " " . $user->getMail(),
                    "amount" => $montant,
                    "currency" => ["iso" => "XOF"],
                    "customer" => [
                        "firstname" => "Dressur : " . $user->getPseudo(),
                        "lastname" => $user->getNom(),
                        "email" => $user->getMail(),
                        "phone_number" => [
                            "number" => $tel,
                            "country" => $methodePaiementEntity->getCodePays(),
                        ],
                    ],
                ]);

                $myTransaction = (new EntityTransaction())
                    ->setUser($user)
                    ->setTransactionFor("adhesion_vendeur")
                    ->setIdTransaction($transaction["id"])
                    ->setReference($transaction["reference"])
                    ->setAmount($transaction["amount"])
                    ->setStatus($transaction["status"])
                    ->setCustomerId($transaction["customer_id"])
                    ->setCurrencyId($transaction["currency_id"])
                    ->setAnnotherInfo($anotherInfo);
                $this->em->persist($myTransaction);
                $this->em->flush();

                $resultat = $traitementsDS->startPaiementFedaPay($transaction, $methodePaiementEntity);
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $this->sendMail->sendReport("uUid : " . $user->getUid() . " WhatsApp : " . $user->getTel(), $th);
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        } elseif ($methodePaiementEntity->getAggregator() == "KPay") {
            $envPaiementApi = $traitementsDS->getEnvPaiementApiKPayDisponible();
            if (!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : " . $uid, "Aucun Webhook Disponible pour KPay");
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                ]);
            }

            try {
                $resultat = $traitementsDS->startPaiementKPay(
                    $envPaiementApi,
                    $methodePaiementEntity,
                    $montant,
                    $tel,
                    $user->getPseudo(),
                    $user->getMail(),
                    "adhesion_vendeur",
                    $anotherInfo,
                    $user,
                    $request->getSchemeAndHttpHost()
                );
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $this->sendMail->sendReport("uUid : " . $user->getUid() . " WhatsApp : " . $user->getTel(), $th);
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        } else {
            // FeexPay (par défaut)
            $envPaiementApi = $traitementsDS->getEnvPaiementApiFeexPayDisponible();
            if (!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : " . $uid, "Aucun Webhook Disponible pour FeexPay");
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                ]);
            }

            try {
                $resultat = $traitementsDS->startPaiementFeexPay(
                    $envPaiementApi,
                    $methodePaiementEntity,
                    $montant,
                    $tel,
                    $user->getPseudo(),
                    $user->getMail(),
                    "adhesion_vendeur",
                    $anotherInfo,
                    $user,
                    $request->getSchemeAndHttpHost()
                );
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $this->sendMail->sendReport("uUid : " . $user->getUid() . " WhatsApp : " . $user->getTel(), $th);
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        }
    }

    #[Route('/vendeur/recharge', name: 'vendeur_recharge', methods: ['POST'])]
    public function recharge(Request $request, TraitementsDS $traitementsDS, VerificationsDS $verificationsDS, SessionDS $sessionDS, UserRepository $userRepository, MethodePaiementRepository $methodePaiementRepository, TransactionRepository $transactionRepository): Response
    {
        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;

        $verificationUser = $verificationsDS->verifUSer($uid);
        if ($verificationUser["error"] == true) {
            return new JsonResponse([
                'error' => true,
                'titre' => $verificationUser["titre"],
                'message' => $verificationUser["message"],
                'deleted' => $verificationUser["deleted"],
                'blocked' => $verificationUser["blocked"],
            ]);
        }
        $user = $verificationUser["user"];

        if ($user->isVendeur() == false) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Non autorisé',
                'message' => 'Vous devez être vendeur pour recharger votre solde.',
            ]);
        }

        $montant = (int)$request->request->get('montant');
        if ($montant < 500) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Montant insuffisant',
                'message' => 'Le montant minimum de recharge est de 500 FCFA.',
            ]);
        }

        $tel = trim((string)$request->request->get('tel', ''));
        if (empty($tel)) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Numéro requis',
                'message' => 'Veuillez renseigner votre numéro de téléphone au format international (ex: +22890000000).',
            ]);
        }

        $methodePaiementId = $request->request->get('methodePaiementId');
        $methodePaiementEntity = $methodePaiementRepository->find($methodePaiementId);
        if (!$methodePaiementEntity) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez choisir une méthode de paiement valide.',
            ]);
        }

        $anotherInfo = ['userId' => $user->getId(), 'userUid' => $user->getUid(), 'montant' => $montant];

        if ($methodePaiementEntity->getAggregator() == "FedaPay") {
            $envPaiementApi = $traitementsDS->getEnvPaiementApiFedaPayDisponible();
            if (!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : " . $uid, "Aucun Webhook Disponible pour FedaPay");
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                ]);
            }
            FedaPay::setApiKey($envPaiementApi->getApiKey());
            FedaPay::setEnvironment($envPaiementApi->getEnvironment());

            try {
                $transaction = Transaction::create([
                    "description" => "Dressur : Recharge Vendeur - " . $montant . " FCFA : " . $user->getPseudo() . " " . $user->getMail(),
                    "amount" => $montant,
                    "currency" => ["iso" => "XOF"],
                    "customer" => [
                        "firstname" => "Dressur : " . $user->getPseudo(),
                        "lastname" => $user->getNom(),
                        "email" => $user->getMail(),
                        "phone_number" => [
                            "number" => $tel,
                            "country" => $methodePaiementEntity->getCodePays(),
                        ],
                    ],
                ]);

                $myTransaction = (new EntityTransaction())
                    ->setUser($user)
                    ->setTransactionFor("recharge_vendeur")
                    ->setIdTransaction($transaction["id"])
                    ->setReference($transaction["reference"])
                    ->setAmount($transaction["amount"])
                    ->setStatus($transaction["status"])
                    ->setCustomerId($transaction["customer_id"])
                    ->setCurrencyId($transaction["currency_id"])
                    ->setAnnotherInfo($anotherInfo);
                $this->em->persist($myTransaction);
                $this->em->flush();

                $resultat = $traitementsDS->startPaiementFedaPay($transaction, $methodePaiementEntity);
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $this->sendMail->sendReport("uUid : " . $user->getUid() . " WhatsApp : " . $user->getTel(), $th);
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        } elseif ($methodePaiementEntity->getAggregator() == "KPay") {
            $envPaiementApi = $traitementsDS->getEnvPaiementApiKPayDisponible();
            if (!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : " . $uid, "Aucun Webhook Disponible pour KPay");
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                ]);
            }

            try {
                $resultat = $traitementsDS->startPaiementKPay(
                    $envPaiementApi,
                    $methodePaiementEntity,
                    $montant,
                    $tel,
                    $user->getPseudo(),
                    $user->getMail(),
                    "recharge_vendeur",
                    $anotherInfo,
                    $user,
                    $request->getSchemeAndHttpHost()
                );
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $this->sendMail->sendReport("uUid : " . $user->getUid() . " WhatsApp : " . $user->getTel(), $th);
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        } else {
            // FeexPay (par défaut)
            $envPaiementApi = $traitementsDS->getEnvPaiementApiFeexPayDisponible();
            if (!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : " . $uid, "Aucun Webhook Disponible pour FeexPay");
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                ]);
            }

            try {
                $resultat = $traitementsDS->startPaiementFeexPay(
                    $envPaiementApi,
                    $methodePaiementEntity,
                    $montant,
                    $tel,
                    $user->getPseudo(),
                    $user->getMail(),
                    "recharge_vendeur",
                    $anotherInfo,
                    $user,
                    $request->getSchemeAndHttpHost()
                );
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $this->sendMail->sendReport("uUid : " . $user->getUid() . " WhatsApp : " . $user->getTel(), $th);
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        }
    }
}
