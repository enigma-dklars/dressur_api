<?php

namespace App\Controller\API;

use DateTime;
use FedaPay\FedaPay;
use FedaPay\Webhook;
use App\Entity\Boost;
use App\Entity\PromoReseau;
use App\Entity\Promotion;
use FedaPay\Transaction;
use App\Services\VerificationsDS;
use App\Repository\BoostRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use App\Repository\FormuleBoostRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\EnvPaiementApiRepository;
use App\Repository\FormuleDressurBotRepository;
use App\Repository\FormulePromoAffaireRepository;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\PromotionRepository;
use App\Services\TraitementsDS;
use App\Utilities\SendMail;
use App\Utilities\ZefameApi;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

#[Route('/api', name: 'api_')]

class WebhookController extends AbstractController
{
    private $em;
    private $transactionRepository;
    private $formuleBoostRepository;
    private $formulePromoAffaireRepository;
    private $promotionRepository;
    private $formulePromoReseauRepository;
    private $verificationsDS;
    private $boostRepository;
    private $formuleDressurBotRepository;
    private $sendMail;
    private $zefameApi;
    private $traitementsDS;

    public function __construct(EntityManagerInterface $em, TransactionRepository $transactionRepository, FormuleBoostRepository $formuleBoostRepository, PromotionRepository $promotionRepository, FormulePromoReseauRepository $formulePromoReseauRepository, VerificationsDS $verificationsDS, BoostRepository $boostRepository, FormuleDressurBotRepository $formuleDressurBotRepository, FormulePromoAffaireRepository $formulePromoAffaireRepository, SendMail $sendMail, ZefameApi $zefameApi, TraitementsDS $traitementsDS)
    {
        $this->em = $em;
        $this->transactionRepository = $transactionRepository;
        $this->formuleBoostRepository = $formuleBoostRepository;
        $this->formulePromoAffaireRepository = $formulePromoAffaireRepository;
        $this->promotionRepository = $promotionRepository;
        $this->formulePromoReseauRepository = $formulePromoReseauRepository;
        $this->verificationsDS = $verificationsDS;
        $this->boostRepository = $boostRepository;
        $this->formuleDressurBotRepository = $formuleDressurBotRepository;
        $this->sendMail = $sendMail;
        $this->zefameApi = $zefameApi;
        $this->traitementsDS = $traitementsDS;
    }

    /**
     * Logique métier partagée : active le service acheté selon le type de transaction.
     * Appelé par webhookFedaPay, webhookFeexPay et forceProcessTransaction.
     * Le statut de $myTransaction doit déjà être mis à jour par l'appelant.
     * La gestion beginTransaction/flush/commit est à la charge de l'appelant.
     */
    private function allWebhookDressur($myTransaction): void
    {
        if ($myTransaction->getTransactionFor() == "boost_contact") {
            $formuleBoost = $this->formuleBoostRepository->find($myTransaction->getAnnotherInfo()['formulBoostId']);
            $typeBoost = $myTransaction->getAnnotherInfo()['typeBoost'] ?? 'date';
            $boost = new Boost();
            $boost->setFormuleBoost($formuleBoost)
                ->setMode("Payant")
                ->setUser($myTransaction->getUser())
                ->setSource($myTransaction->getAnnotherInfo()['source'] ?? 'mobile')
                ->setTypeBoost($typeBoost)
            ;
            if ($typeBoost === 'quota') {
                $boost->setDateDebut(new DateTime());
            } elseif ($this->verificationsDS->siBoostEnCours($this->boostRepository->findBy(['user' => $myTransaction->getUser()]))) {
                $lastBoostDateExp = ($this->boostRepository->findOneBy(['user' => $myTransaction->getUser()], ["id" => "DESC"]))->getDateExp();
                $boost->setDateDebut($lastBoostDateExp)
                    ->setDateExp(new DateTime(date('d-m-Y H:i', strtotime("+ ".$formuleBoost->getNbrJour()."days ".$lastBoostDateExp->format('d-m-Y H:i')))))
                ;
            } else {
                $boost->setDateDebut(new DateTime())
                    ->setDateExp(new DateTime("+ ".$formuleBoost->getNbrJour()."days"))
                ;
            }
            $this->traitementsDS->addNotification("Paiement confirmer. Boost Contact enregistrer.", $myTransaction->getUser());
            $this->em->persist($boost);
        }

        if ($myTransaction->getTransactionFor() == "boost_affaire") {
            $formulePromoAffaire = $this->formulePromoAffaireRepository->find($myTransaction->getAnnotherInfo()['formulePromoAffaire']);
            $inProgrammeRecompense  = $myTransaction->getAnnotherInfo()['inProgrammeRecompense']  ?? false;
            $publishOnDressurStatus = $myTransaction->getAnnotherInfo()['publishOnDressurStatus'] ?? false;
            $promotion = new Promotion();
            $promotion
                ->setMode("Payant")
                ->setUser($myTransaction->getUser())
                ->setFormulePromoAffaire($formulePromoAffaire)
                ->setImage($myTransaction->getAnnotherInfo()['image'])
                ->setDescription($myTransaction->getAnnotherInfo()['description'])
                ->setInProgrammeRecompense($inProgrammeRecompense)
                ->setPublishOnDressurStatus($publishOnDressurStatus)
                ->setSource($myTransaction->getAnnotherInfo()['source'] ?? 'mobile')
            ;
            $this->em->persist($promotion);

            $user = $myTransaction->getUser();
            $htmlAdmin = $this->renderView('emails/promo_affaire_admin_notif.html.twig', [
                'user_nom'                  => $user->getNom(),
                'user_mail'                 => $user->getMail(),
                'user_tel'                  => $user->getTel() ?? '—',
                'formule_titre'             => $formulePromoAffaire->getTitre(),
                'formule_prix'              => $formulePromoAffaire->getPrix(),
                'formule_nbr_jour'          => $formulePromoAffaire->getNbrJour(),
                'description'               => $myTransaction->getAnnotherInfo()['description'] ?? '—',
                'in_programme_recompense'   => $inProgrammeRecompense,
                'publish_on_dressur_status' => $publishOnDressurStatus,
            ]);
            $this->sendMail->smtpMail(
                $_ENV['ADMIN_EMAIL'],
                "Nouvelle Promotion Affaire en attente — " . $user->getNom(),
                $htmlAdmin
            );
            $this->traitementsDS->addNotification("Paiement confirmer. Promotion Affaire enregistrer. En attente d'approbation.", $myTransaction->getUser());
        }

        if ($myTransaction->getTransactionFor() == "re_boost_affaire") {
            $formulePromoAffaire = $this->formulePromoAffaireRepository->find($myTransaction->getAnnotherInfo()['formulBoostId']);
            $inProgrammeRecompense  = $myTransaction->getAnnotherInfo()['inProgrammeRecompense']  ?? false;
            $publishOnDressurStatus = $myTransaction->getAnnotherInfo()['publishOnDressurStatus'] ?? false;
            $promotion = $this->promotionRepository->find($myTransaction->getAnnotherInfo()['promotionId']);
            $promotion->setMode("Payant")
                ->setDateDebut(new DateTime())
                ->setDateExp(new DateTime("+ ".$formulePromoAffaire->getNbrJour()."days"))
                ->setReferencement($formulePromoAffaire->getReferencement())
                ->setStatus(3)
                ->setInProgrammeRecompense($inProgrammeRecompense)
                ->setPublishOnDressurStatus($publishOnDressurStatus)
                ->setSource($myTransaction->getAnnotherInfo()['source'] ?? 'mobile')
            ;
            $this->traitementsDS->addNotification("Paiement confirmer. Promotion Affaire enregistrer et démarrer.", $myTransaction->getUser());
        }

        if ($myTransaction->getTransactionFor() == "boost_reseau_sociaux") {
            $formulePromoReseau = $this->formulePromoReseauRepository->find($myTransaction->getAnnotherInfo()['idFormulePromoReseau']);
            $boost = new PromoReseau();
            $boost->setFormulePromoReseau($formulePromoReseau)
                ->setUser($myTransaction->getUser())
                ->setQteDemander($myTransaction->getAnnotherInfo()['qteDemander'])
                ->setPrixFixer($myTransaction->getAnnotherInfo()['prixQteDemander'])
                ->setUrl($myTransaction->getAnnotherInfo()['lien'])
                ->setSource($myTransaction->getAnnotherInfo()['source'] ?? 'mobile')
                ->setPrixZefame($formulePromoReseau->getPrixZefame() !== null
                    ? round((int)$myTransaction->getAnnotherInfo()['qteDemander'] * $formulePromoReseau->getPrixZefame() / 1000, 5)
                    : null)
            ;
            $this->em->persist($boost);

            $formule = $boost->getFormulePromoReseau();
            $formuleLower = mb_strtolower($formule, 'UTF-8');
            if (((strpos($formuleLower, 'commentaires') === false && strpos($formuleLower, 'customisés') === false)
                    OR
                    (strpos($formuleLower, 'commentaires') === false && strpos($formuleLower, 'likes') === false)
                ) && !empty($boost->getFormulePromoReseau()->getIdZefame())) {
                $resultZefame = $this->zefameApi->order([
                    'service'  => $boost->getFormulePromoReseau()->getIdZefame(),
                    'link'     => $boost->getUrl(),
                    'quantity' => $boost->getQteDemander(),
                    'runs'     => 2,
                    'interval' => 5,
                ]);
                if (isset($resultZefame->order)) {
                    $boost->setIdZefame($resultZefame->order)->setStatus(2);
                } elseif (isset($resultZefame->error)) {
                    $this->sendMail->sendReport("Error Promo Reseau --- ID = ".$boost->getId(), $resultZefame->error);
                } else {
                    $this->sendMail->sendReport("Error Promo Reseau --- ID = ".$boost->getId(), (string)$resultZefame);
                }
            } else {
                $this->sendMail->sendReport("Promo Reseau en attente --- ID = ".$boost->getId(), "Impossible de demarrer la promo reseau directement... surrement une demande de commentaire");
            }
            $this->traitementsDS->addNotification("Paiement confirmer. Promotion Reseau enregistrer et démarrer.", $myTransaction->getUser());
        }

        if ($myTransaction->getTransactionFor() == "dressur_bot_activation") {
            $formuleDressurBot = $this->formuleDressurBotRepository->find($myTransaction->getAnnotherInfo()['formulDressurBotId']);
            $userBot = $myTransaction->getUserBot();
            $userBot->setExpiratedAt(new DateTime("+ ".$formuleDressurBot->getNbrJour()."days"))
                ->setSignature($formuleDressurBot->getSignature())
            ;
        }

        if ($myTransaction->getTransactionFor() == "adhesion_vendeur") {
            $user = $myTransaction->getUser();
            $user->setVendeur(true);
            $this->traitementsDS->addNotification(
                "Paiement confirmé. Vous êtes maintenant vendeur sur Dressur !",
                $user
            );
        }

        if ($myTransaction->getTransactionFor() == "recharge_vendeur") {
            $user = $myTransaction->getUser();
            $montant = (int)($myTransaction->getAnnotherInfo()['montant'] ?? 0);
            $nouveauSolde = ($user->getSoldeProgrammeRecompense() ?? 0) + $montant;
            $user->setSoldeProgrammeRecompense($nouveauSolde);
            $this->traitementsDS->addNotification(
                "Solde rechargé de {$montant} FCFA. Nouveau solde : {$nouveauSolde} FCFA.",
                $user
            );
        }
    }

    #[Route('/whd/{routeWebhook}', name: 'webhookFedaPay')]
    public function webhookFedaPay($routeWebhook, EnvPaiementApiRepository $envPaiementApiRepository): Response
    {
        try {
            $envPaiementApi = $envPaiementApiRepository->findOneBy(['routeWebhook' => $routeWebhook]);

            FedaPay::setApiKey($envPaiementApi->getApiKey());
            FedaPay::setEnvironment($envPaiementApi->getEnvironment());
            $endpoint_secret = $envPaiementApi->getEndpointSecret();

            $payload = @file_get_contents('php://input');
            $sig_header = $_SERVER['HTTP_X_FEDAPAY_SIGNATURE'];
            $event = null;

            try {
                $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
            } catch (\UnexpectedValueException $e) {
                $this->sendMail->sendReport('Error webhookFedaPay : WebhookController', $e . '<br><br><br>');
                return new Response("Invalid payload", 400);
            } catch (\FedaPay\Error\SignatureVerification $e) {
                $this->sendMail->sendReport('Error webhookFedaPay : WebhookController', $e . '<br><br><br>');
                return new Response("Invalid signature", 400);
            }

            try {
                switch ($event->name) {
                    case 'transaction.approved':
                        $idTransaction = $event->entity->id;
                        $myTransaction = $this->transactionRepository->findOneBy(['idTransaction' => $idTransaction]);

                        if (!$myTransaction) {
                            return new Response("Transaction non trouvee", 200);
                        }
                        if (!$myTransaction->getUser() && $myTransaction->getTransactionFor() !== 'dressur_bot_activation') {
                            return new Response("Transaction sans utilisateur", 200);
                        }
                        if (!in_array($myTransaction->getStatus(), ["pending", "canceled"])) {
                            return new Response("Transaction deja traitee", 200);
                        }

                        $transaction = Transaction::retrieve($idTransaction);
                        $myTransaction->setStatus($transaction->status)->isUpdated();

                        $this->em->beginTransaction();
                        $this->allWebhookDressur($myTransaction);
                        $envPaiementApi->isUsedApproved();
                        $this->em->flush();
                        $this->em->commit();
                        return new Response("Transaction approved traitée", 200);

                    default:
                        $idTransaction = $event->entity->id;
                        $myTransaction = $this->transactionRepository->findOneBy(['idTransaction' => $idTransaction]);
                        $transaction = Transaction::retrieve($idTransaction);
                        $myTransaction->setStatus($transaction->status)->isUpdated();
                        $this->em->flush();
                        return new Response("Transaction ".$transaction->status, 200);
                }
            } catch (\Throwable $th) {
                try { $this->em->rollback(); } catch (\Throwable $ignored) {}
                $this->sendMail->sendReport("Error in webhookFedaPay function", $th."<br><br><br>");
                return new Response('Internal error but webhook acknowledged', 200);
            }
        } catch (\Throwable $th) {
            $this->sendMail->sendReport("Error webhookFedaPay : ".$routeWebhook, $th."<br><br><br>");
            return new Response('Erreur : report is sent by mail', 200);
        }
    }

    #[Route('/wfd/{routeWebhook}', name: 'webhookFeexPay', methods: ['GET', 'POST'])]
    public function webhookFeexPay(Request $request, $routeWebhook): Response
    {
        try {
            $reference = $request->query->get('reference') ?? $request->request->get('reference');
            $status    = $request->query->get('status')    ?? $request->request->get('status');

            if (!$reference) {
                $payload = json_decode($request->getContent(), true);
                $reference = $payload['reference'] ?? null;
                $status    = $payload['status']    ?? $status;
            }

            if (!$reference) {
                $this->sendMail->sendReport("FeexPay webhook sans reference : ".$routeWebhook, $request->getContent()."<br>GET: ".json_encode($request->query->all())."<br>POST: ".json_encode($request->request->all()));
                return new Response('Missing reference', 200);
            }

            $myTransaction = $this->transactionRepository->findOneBy(['idTransaction' => $reference]);
            if (!$myTransaction) {
                return new Response('Transaction non trouvee', 200);
            }

            if (!in_array(strtolower($myTransaction->getStatus()), ['pending', 'canceled'])) {
                return new Response('Transaction deja traitee', 200);
            }

            $normalizedStatus = strtoupper((string)$status);
            if ($normalizedStatus !== 'SUCCESSFUL') {
                $myTransaction->setStatus(strtolower($normalizedStatus ?: 'failed'))->isUpdated();
                $this->em->flush();
                return new Response('Statut : ' . $normalizedStatus, 200);
            }

            $myTransaction->setStatus('approved')->isUpdated();

            $this->em->beginTransaction();
            $this->allWebhookDressur($myTransaction);
            $this->em->flush();
            $this->em->commit();
            return new Response('FeexPay transaction activée', 200);
        } catch (\Throwable $th) {
            try { $this->em->rollback(); } catch (\Throwable $ignored) {}
            $this->sendMail->sendReport('Error webhookFeexPay : ' . $routeWebhook, $th . '<br><br><br>');
            return new Response('Erreur interne — rapport envoyé', 200);
        }
    }

    #[Route('/wkp/{routeWebhook}', name: 'webhookKPay', methods: ['POST'])]
    public function webhookKPay(Request $request, $routeWebhook, EnvPaiementApiRepository $envPaiementApiRepository): Response
    {
        try {
            $envPaiementApi = $envPaiementApiRepository->findOneBy(['routeWebhook' => $routeWebhook, 'aggregator' => 'KPay']);
            if (!$envPaiementApi) {
                $this->sendMail->sendReport('KPay webhook : configuration introuvable', 'routeWebhook: ' . $routeWebhook);
                return new Response('Configuration KPay introuvable', 200);
            }

            $rawBody = $request->getContent();
            $signature = $request->headers->get('X-KPAY-Signature');
            $secret = $envPaiementApi->getEndpointSecret();

            if (!$signature || !$secret) {
                $this->sendMail->sendReport('KPay webhook : signature ou secret manquant', 'routeWebhook: ' . $routeWebhook);
                return new Response('Missing signature', 400);
            }

            $expectedSignature = hash_hmac('sha256', $rawBody, $secret);
            if (!hash_equals($expectedSignature, (string)$signature)) {
                $this->sendMail->sendReport('KPay webhook : signature invalide', 'routeWebhook: ' . $routeWebhook . '<br>Body: ' . htmlspecialchars($rawBody));
                return new Response('Invalid signature', 400);
            }

            $payload = json_decode($rawBody, true);
            $event = $payload['event'] ?? null;
            $reference = $payload['reference'] ?? null;
            $status = $payload['status'] ?? null;

            if (!$reference) {
                $this->sendMail->sendReport('KPay webhook sans reference : ' . $routeWebhook, '<pre>' . htmlspecialchars($rawBody) . '</pre>');
                return new Response('Missing reference', 200);
            }

            $myTransaction = $this->transactionRepository->findOneBy(['idTransaction' => $reference]);
            if (!$myTransaction) {
                return new Response('Transaction non trouvee', 200);
            }

            if (!in_array($myTransaction->getStatus(), ['pending', 'canceled'])) {
                return new Response('Transaction deja traitee', 200);
            }

            if ($event !== 'payment.completed' || strtoupper((string)$status) !== 'COMPLETED') {
                $myTransaction->setStatus(strtolower((string)$status ?: 'failed'))->isUpdated();
                $this->em->flush();
                return new Response('Statut : ' . $status, 200);
            }

            $myTransaction->setStatus('approved')->isUpdated();

            $this->em->beginTransaction();
            $this->allWebhookDressur($myTransaction);
            $this->em->flush();
            $this->em->commit();
            return new Response('KPay transaction activee', 200);
        } catch (\Throwable $th) {
            try { $this->em->rollback(); } catch (\Throwable $ignored) {}
            $this->sendMail->sendReport('Error webhookKPay : ' . $routeWebhook, $th . '<br><br><br>');
            return new Response('Erreur interne — rapport envoyé', 200);
        }
    }

    /**
     * Pages de retour navigateur après un paiement KPay en mode GATEWAY. N'écrivent
     * jamais en base : uniquement un message d'attente/confirmation pour le client.
     * La seule source de vérité pour la confirmation reste webhookKPay (POST serveur-à-serveur).
     */
    #[Route('/wkp-return/{routeWebhook}', name: 'webhookKPayReturn', methods: ['GET'])]
    public function webhookKPayReturn(Request $request, $routeWebhook, EnvPaiementApiRepository $envPaiementApiRepository): Response
    {
        return $this->renderKPayGatewayFeedback($request, $routeWebhook, $envPaiementApiRepository, false);
    }

    #[Route('/wkp-cancel/{routeWebhook}', name: 'webhookKPayCancel', methods: ['GET'])]
    public function webhookKPayCancel(Request $request, $routeWebhook, EnvPaiementApiRepository $envPaiementApiRepository): Response
    {
        return $this->renderKPayGatewayFeedback($request, $routeWebhook, $envPaiementApiRepository, true);
    }

    private function renderKPayGatewayFeedback(Request $request, $routeWebhook, EnvPaiementApiRepository $envPaiementApiRepository, bool $isCancel): Response
    {
        $envPaiementApi = $envPaiementApiRepository->findOneBy(['routeWebhook' => $routeWebhook, 'aggregator' => 'KPay']);

        $status = $request->query->get('status');
        $reference = $request->query->get('reference');
        $externalId = $request->query->get('externalId');
        $ts = $request->query->get('ts');
        $sig = $request->query->get('sig');

        $verified = false;
        if ($envPaiementApi && $status && $reference && $ts && $sig) {
            $stringToSign = $status . '|' . $reference . '|' . ($externalId ?? '') . '|' . $ts;
            $expected = hash_hmac('sha256', $stringToSign, (string)$envPaiementApi->getEndpointSecret());
            $verified = hash_equals($expected, (string)$sig) && ((time() * 1000) - (int)$ts) < 600000;
        }

        $message = $isCancel
            ? "Paiement annulé."
            : (($verified && strtoupper((string)$status) === 'COMPLETED')
                ? "Paiement reçu, merci ! Vous pouvez fermer cette page."
                : "Paiement en cours de vérification. Vous serez notifié une fois confirmé.");

        return new Response(
            '<html><body style="font-family:sans-serif;text-align:center;padding:40px;"><h2>' . htmlspecialchars($message) . '</h2></body></html>',
            200
        );
    }

    #[Route('/admin/force-process/{id}', name: 'forceProcessTransaction', methods: ['POST'])]
    public function forceProcessTransaction(int $id, EnvPaiementApiRepository $envPaiementApiRepository): JsonResponse
    {
        $myTransaction = $this->transactionRepository->find($id);

        if (!$myTransaction) {
            return new JsonResponse(['error' => true, 'message' => 'Transaction introuvable.']);
        }

        $idTransactionFeda = $myTransaction->getIdTransaction();
        if (!$idTransactionFeda) {
            return new JsonResponse(['error' => true, 'message' => 'Aucun identifiant FedaPay lie a cette transaction.']);
        }

        if (!in_array($myTransaction->getStatus(), ['pending', 'canceled'])) {
            return new JsonResponse(['error' => true, 'message' => 'Cette transaction a deja ete traitee (statut actuel : ' . $myTransaction->getStatus() . ').']);
        }

        $envPaiementApis = $envPaiementApiRepository->findAll();
        $fedaTransaction = null;
        $usedEnv = null;

        foreach ($envPaiementApis as $envApi) {
            try {
                FedaPay::setApiKey($envApi->getApiKey());
                FedaPay::setEnvironment($envApi->getEnvironment());
                $fedaTransaction = Transaction::retrieve((int)$idTransactionFeda);
                $usedEnv = $envApi;
                break;
            } catch (\Throwable $th) {
                $this->sendMail->sendReport('Error forceProcessTransaction : WebhookController', $th . '<br><br><br>');
                continue;
            }
        }

        if (!$fedaTransaction) {
            return new JsonResponse(['error' => true, 'message' => 'Impossible de recuperer la transaction sur FedaPay. Verifiez la configuration des APIs.']);
        }

        if ($fedaTransaction->status !== 'approved') {
            return new JsonResponse(['error' => true, 'message' => 'Le paiement nest pas approuve sur FedaPay. Statut reel : ' . $fedaTransaction->status]);
        }

        try {
            $myTransaction->setStatus($fedaTransaction->status)->isUpdated();

            $this->em->beginTransaction();
            $this->allWebhookDressur($myTransaction);
            $usedEnv->isUsedApproved();
            $this->em->flush();
            $this->em->commit();

            return new JsonResponse(['error' => false, 'message' => 'Transaction traitee avec succes (type : ' . $myTransaction->getTransactionFor() . ').']);
        } catch (\Throwable $th) {
            try { $this->em->rollback(); } catch (\Throwable $ignored) {}
            $this->sendMail->sendReport('Error forceProcessTransaction --- ID = ' . $id, $th . '<br><br><br>');
            return new JsonResponse(['error' => true, 'message' => 'Erreur lors du traitement : ' . $th->getMessage()]);
        }
    }
}
