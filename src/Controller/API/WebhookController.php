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

    public function __construct(EntityManagerInterface $em, TransactionRepository $transactionRepository, FormuleBoostRepository $formuleBoostRepository, PromotionRepository $promotionRepository, FormulePromoReseauRepository $formulePromoReseauRepository, VerificationsDS $verificationsDS, BoostRepository $boostRepository, FormuleDressurBotRepository $formuleDressurBotRepository, FormulePromoAffaireRepository $formulePromoAffaireRepository, SendMail $sendMail, ZefameApi $zefameApi)
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
    }

    public function allWebhookDressur($envPaiementApi) {
        FedaPay::setApiKey($envPaiementApi->getApiKey());
        FedaPay::setEnvironment($envPaiementApi->getEnvironment());
        $endpoint_secret = $envPaiementApi->getEndpointSecret();

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_X_FEDAPAY_SIGNATURE'];
        $event = null;

        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            return [400, "Invalid payload"];
        } catch(\FedaPay\Error\SignatureVerification $e) {
            return [400, "Invalid signature"];
        }

        try {
            switch ($event->name) {
                case 'transaction.approved':
                    $idTransaction = $event->entity->id;
                    $myTransaction = $this->transactionRepository->findOneBy(['idTransaction' => $idTransaction]);

                    if (!$myTransaction || !$myTransaction->getUser()) {
                        return [200, "Transaction sans utilisateur"];
                    }

                    if($myTransaction){
                        if(in_array($myTransaction->getStatus(), ["pending", "canceled"])) {
                            $this->em->beginTransaction();
                            
                            $transaction = Transaction::retrieve($idTransaction);
                            $myTransaction->setStatus($transaction->status)->isUpdated();
                            
                            if($myTransaction->getTransactionFor() == "boost_contact") {
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
                                } elseif($this->verificationsDS->siBoostEnCours($this->boostRepository->findBy(['user' => $myTransaction->getUser()]))) {
                                    $lastBoostDateExp = ($this->boostRepository->findOneBy(['user' => $myTransaction->getUser()], ["id" => "DESC"]))->getDateExp();
                                    $boost->setDateDebut($lastBoostDateExp)
                                        ->setDateExp(new DateTime(date('d-m-Y H:i', strtotime("+ ".$formuleBoost->getNbrJour()."days ".$lastBoostDateExp->format('d-m-Y H:i')))))
                                    ;
                                } else {
                                    $boost->setDateDebut(new DateTime())
                                        ->setDateExp(new DateTime("+ ".$formuleBoost->getNbrJour()."days"))
                                    ;
                                }
                                $this->em->persist($boost);
                            }

                            if($myTransaction->getTransactionFor() == "boost_affaire") {
                                $formulePromoAffaire = $this->formulePromoAffaireRepository->find($myTransaction->getAnnotherInfo()['formulePromoAffaire']);
                                $inProgrammeRecompense = false;
                                $publishOnDressurStatus = false;
                                if(isset($myTransaction->getAnnotherInfo()['inProgrammeRecompense'])) {
                                    $inProgrammeRecompense = $myTransaction->getAnnotherInfo()['inProgrammeRecompense'];
                                }
                                if(isset($myTransaction->getAnnotherInfo()['publishOnDressurStatus'])) {
                                    $publishOnDressurStatus = $myTransaction->getAnnotherInfo()['publishOnDressurStatus'];
                                }
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
                            }

                            if($myTransaction->getTransactionFor() == "re_boost_affaire") {
                                $formulePromoAffaire = $this->formulePromoAffaireRepository->find($myTransaction->getAnnotherInfo()['formulBoostId']);
                                $inProgrammeRecompense = false;
                                $publishOnDressurStatus = false;
                                if(isset($myTransaction->getAnnotherInfo()['inProgrammeRecompense'])) {
                                    $inProgrammeRecompense = $myTransaction->getAnnotherInfo()['inProgrammeRecompense'];
                                }
                                if(isset($myTransaction->getAnnotherInfo()['publishOnDressurStatus'])) {
                                    $publishOnDressurStatus = $myTransaction->getAnnotherInfo()['publishOnDressurStatus'];
                                }
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
                            }

                            if($myTransaction->getTransactionFor() == "boost_reseau_sociaux") {
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

                                    $idServiveZefame = $boost->getFormulePromoReseau()->getIdZefame();
                                    $linkPromo = $boost->getUrl();
                                    $qte = $boost->getQteDemander();
                                    $resultZefame = $this->zefameApi->order([
                                        'service' => $idServiveZefame, 
                                        'link' => $linkPromo, 
                                        'quantity' => $qte, 
                                        'runs' => 2, 
                                        'interval' => 5
                                    ]);

                                    if(isset($resultZefame->order)){
                                        $boost->setIdZefame($resultZefame->order)
                                            ->setStatus(2)
                                        ;
                                    } else if(isset($resultZefame->error)){
                                        $this->sendMail->sendReport("Error Promo Reseau --- ID = ".$boost->getId(), $resultZefame->error);
                                    } else {
                                        $this->sendMail->sendReport("Error Promo Reseau --- ID = ".$boost->getId(), (string)$resultZefame);
                                    }
                                } else {
                                    $this->sendMail->sendReport("Promo Reseau en attente --- ID = ".$boost->getId(), "Impossible de demarrer la promo reseau directement... surrement une demande de commentaire");
                                }
                            }

                            if($myTransaction->getTransactionFor() == "dressur_bot_activation") {
                                $formuleDressurBot = $this->formuleDressurBotRepository->find($myTransaction->getAnnotherInfo()['formulDressurBotId']);
                                $userBot = $myTransaction->getUserBot();
                                $userBot->setExpiratedAt(new DateTime("+ ".$formuleDressurBot->getNbrJour()."days"))
                                    ->setSignature($formuleDressurBot->getSignature())
                                ;
                            }

                            $envPaiementApi->isUsedApproved();
                            $this->em->flush();
                            $this->em->commit();
                            return [200, "Transaction approved traitée"];
                        }
                        return [200, "Transaction trouvée mais pas en statut pending ni canceled"];
                    }
                    return [200, "Transaction non trouvée"];
                    break;
                default:
                    $idTransaction = $event->entity->id;
                    $myTransaction = $this->transactionRepository->findOneBy(['idTransaction' => $idTransaction]);
                    $transaction = Transaction::retrieve($idTransaction);
                    $myTransaction->setStatus($transaction->status)->isUpdated();
                    $this->em->flush();
                    
                    return [200, "Transaction ".$transaction->status];
            }
            return [200, "After switch case"];
        } catch (\Throwable $th) {
            $this->em->rollback();
            $this->sendMail->sendReport("Error in allWebhookDressur function", $th."<br><br><br>");
            return [200, 'Internal error but webhook acknowledged'];
        }
    }

    #[Route('/whd/{routeWebhook}', name: 'webhookDressur')]
    public function webhookDressur($routeWebhook, EnvPaiementApiRepository $envPaiementApiRepository): Response
    {
        try {
            $envPaiementApi = $envPaiementApiRepository->findOneBy(['routeWebhook' => $routeWebhook]);
            $http_response_code = $this->allWebhookDressur($envPaiementApi);
            return new Response($http_response_code[1], $http_response_code[0]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport("Error webhookDressur : ".$routeWebhook, $th."<br><br><br>");
            return new Response('Erreur : report is sent by mail', 200);
        }
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
            $this->em->beginTransaction();
            $myTransaction->setStatus($fedaTransaction->status)->isUpdated();

            if ($myTransaction->getTransactionFor() === 'boost_contact') {
                $formuleBoost = $this->formuleBoostRepository->find($myTransaction->getAnnotherInfo()['formulBoostId']);
                $typeBoost = $myTransaction->getAnnotherInfo()['typeBoost'] ?? 'date';
                $boost = new Boost();
                $boost->setFormuleBoost($formuleBoost)
                    ->setMode('Payant')
                    ->setUser($myTransaction->getUser())
                    ->setSource($myTransaction->getAnnotherInfo()['source'] ?? 'mobile')
                    ->setTypeBoost($typeBoost);
                if ($typeBoost === 'quota') {
                    $boost->setDateDebut(new DateTime());
                } elseif ($this->verificationsDS->siBoostEnCours($this->boostRepository->findBy(['user' => $myTransaction->getUser()]))) {
                    $lastBoostDateExp = ($this->boostRepository->findOneBy(['user' => $myTransaction->getUser()], ['id' => 'DESC']))->getDateExp();
                    $boost->setDateDebut($lastBoostDateExp)
                        ->setDateExp(new DateTime(date('d-m-Y H:i', strtotime('+ ' . $formuleBoost->getNbrJour() . 'days ' . $lastBoostDateExp->format('d-m-Y H:i')))));
                } else {
                    $boost->setDateDebut(new DateTime())
                        ->setDateExp(new DateTime('+ ' . $formuleBoost->getNbrJour() . 'days'));
                }
                $this->em->persist($boost);
            }

            if ($myTransaction->getTransactionFor() === 'boost_affaire') {
                $formulePromoAffaire = $this->formulePromoAffaireRepository->find($myTransaction->getAnnotherInfo()['formulePromoAffaire']);
                $inProgrammeRecompense = $myTransaction->getAnnotherInfo()['inProgrammeRecompense'] ?? false;
                $publishOnDressurStatus = $myTransaction->getAnnotherInfo()['publishOnDressurStatus'] ?? false;
                $promotion = new Promotion();
                $promotion->setMode('Payant')
                    ->setUser($myTransaction->getUser())
                    ->setFormulePromoAffaire($formulePromoAffaire)
                    ->setImage($myTransaction->getAnnotherInfo()['image'])
                    ->setDescription($myTransaction->getAnnotherInfo()['description'])
                    ->setInProgrammeRecompense($inProgrammeRecompense)
                    ->setPublishOnDressurStatus($publishOnDressurStatus)
                    ->setSource($myTransaction->getAnnotherInfo()['source'] ?? 'mobile');
                $this->em->persist($promotion);
            }

            if ($myTransaction->getTransactionFor() === 're_boost_affaire') {
                $formulePromoAffaire = $this->formulePromoAffaireRepository->find($myTransaction->getAnnotherInfo()['formulBoostId']);
                $inProgrammeRecompense = $myTransaction->getAnnotherInfo()['inProgrammeRecompense'] ?? false;
                $publishOnDressurStatus = $myTransaction->getAnnotherInfo()['publishOnDressurStatus'] ?? false;
                $promotion = $this->promotionRepository->find($myTransaction->getAnnotherInfo()['promotionId']);
                $promotion->setMode('Payant')
                    ->setDateDebut(new DateTime())
                    ->setDateExp(new DateTime('+ ' . $formulePromoAffaire->getNbrJour() . 'days'))
                    ->setReferencement($formulePromoAffaire->getReferencement())
                    ->setStatus(3)
                    ->setInProgrammeRecompense($inProgrammeRecompense)
                    ->setPublishOnDressurStatus($publishOnDressurStatus)
                    ->setSource($myTransaction->getAnnotherInfo()['source'] ?? 'mobile');
            }

            if ($myTransaction->getTransactionFor() === 'boost_reseau_sociaux') {
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
                        : null);
                $this->em->persist($boost);

                $formule = $boost->getFormulePromoReseau();
                $formuleLower = mb_strtolower($formule, 'UTF-8');
                if (((strpos($formuleLower, 'commentaires') === false && strpos($formuleLower, 'customises') === false)
                        OR
                        (strpos($formuleLower, 'commentaires') === false && strpos($formuleLower, 'likes') === false)
                    ) && !empty($boost->getFormulePromoReseau()->getIdZefame())) {
                    $idServiveZefame = $boost->getFormulePromoReseau()->getIdZefame();
                    $resultZefame = $this->zefameApi->order([
                        'service' => $idServiveZefame,
                        'link' => $boost->getUrl(),
                        'quantity' => $boost->getQteDemander(),
                        'runs' => 2,
                        'interval' => 5,
                    ]);
                    if (isset($resultZefame->order)) {
                        $boost->setIdZefame($resultZefame->order)->setStatus(2);
                    } elseif (isset($resultZefame->error)) {
                        $this->sendMail->sendReport('Error Promo Reseau --- ID = ' . $boost->getId(), $resultZefame->error);
                    } else {
                        $this->sendMail->sendReport('Error Promo Reseau --- ID = ' . $boost->getId(), (string)$resultZefame);
                    }
                } else {
                    $this->sendMail->sendReport('Promo Reseau en attente --- ID = ' . $boost->getId(), 'Impossible de demarrer la promo reseau directement');
                }
            }


            if ($myTransaction->getTransactionFor() === 'dressur_bot_activation') {
                $formuleDressurBot = $this->formuleDressurBotRepository->find($myTransaction->getAnnotherInfo()['formulDressurBotId']);
                $userBot = $myTransaction->getUserBot();
                $userBot->setExpiratedAt(new DateTime('+ ' . $formuleDressurBot->getNbrJour() . 'days'))
                    ->setSignature($formuleDressurBot->getSignature());
            }

            $usedEnv->isUsedApproved();
            $this->em->flush();
            $this->em->commit();

            return new JsonResponse(['error' => false, 'message' => 'Transaction traitee avec succes (type : ' . $myTransaction->getTransactionFor() . ').']);
        } catch (\Throwable $th) {
            $this->em->rollback();
            $this->sendMail->sendReport('Error forceProcessTransaction --- ID = ' . $id, $th . '<br><br><br>');
            return new JsonResponse(['error' => true, 'message' => 'Erreur lors du traitement : ' . $th->getMessage()]);
        }
    }
}
