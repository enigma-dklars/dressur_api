<?php

namespace App\Controller\API;

use DateTime;
use App\Entity\User;
use FedaPay\FedaPay;
use FedaPay\Webhook;
use App\Entity\Boost;
use App\Entity\PromoReseau;
use FedaPay\Transaction;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use App\Repository\BoostRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use App\Repository\FormuleBoostRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Transaction as EntityTransaction;
use App\Repository\CampagneMailRepository;
use App\Repository\EnvPaiementApiRepository;
use App\Repository\FormuleDressurBotRepository;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\PromotionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/api', name: 'api_')]

class WebhookController extends AbstractController
{
    private $em;
    private $env;
    private $transactionRepository;
    private $formuleBoostRepository;
    private $campagneMailRepository;
    private $promotionRepository;
    private $formulePromoReseauRepository;
    private $verificationsDS;
    private $boostRepository;
    private $formuleDressurBotRepository;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, TransactionRepository $transactionRepository, FormuleBoostRepository $formuleBoostRepository, CampagneMailRepository $campagneMailRepository, PromotionRepository $promotionRepository, FormulePromoReseauRepository $formulePromoReseauRepository, VerificationsDS $verificationsDS, BoostRepository $boostRepository, FormuleDressurBotRepository $formuleDressurBotRepository)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->transactionRepository = $transactionRepository;
        $this->formuleBoostRepository = $formuleBoostRepository;
        $this->campagneMailRepository = $campagneMailRepository;
        $this->promotionRepository = $promotionRepository;
        $this->formulePromoReseauRepository = $formulePromoReseauRepository;
        $this->verificationsDS = $verificationsDS;
        $this->boostRepository = $boostRepository;
        $this->formuleDressurBotRepository = $formuleDressurBotRepository;
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
            // Invalid payload

            return 400;
            exit();
        } catch(\FedaPay\Error\SignatureVerification $e) {
            // Invalid signature

            return 400;
            exit();
        }

        // Handle the event
        switch ($event->name) {
            case 'transaction.approved':
                // Transaction approuvée
                $idTransaction = $event->entity->id;
                $myTransaction = $this->transactionRepository->findOneBy(['idTransaction' => $idTransaction]);
                if($myTransaction){
                    if($myTransaction->getStatus() != "approved") {
                        $transaction = Transaction::retrieve($idTransaction);
                        $myTransaction->setStatus($transaction->status)->isUpdated();
                        
                        if($myTransaction->getTransactionFor() == "boost_contact") {
                            $formuleBoost = $this->formuleBoostRepository->find($myTransaction->getAnnotherInfo()['formulBoostId']);
                            $boost = new Boost();
                            $boost->setFormuleBoost($formuleBoost)
                                ->setMode("Payant")
                                ->setUser($myTransaction->getUser())
                            ;
                            if($this->verificationsDS->siBoostEnCours($this->boostRepository->findBy(['user' => $myTransaction->getUser()]))) {
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
                            $formuleBoost = $this->formuleBoostRepository->find($myTransaction->getAnnotherInfo()['formulBoostId']);
                            $promotion = $this->promotionRepository->find($myTransaction->getAnnotherInfo()['promotionId']);
                            $promotion->setMode("Payant")
                                ->setDateDebut(new DateTime())
                                ->setDateExp(new DateTime("+ ".$formuleBoost->getNbrJour()."days"))
                                ->setStatus(3)
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
                            ;
                            $this->em->persist($boost);
                        }

                        if($myTransaction->getTransactionFor() == "campagne_mail") {
                            $campagneMail = $this->campagneMailRepository->find($myTransaction->getAnnotherInfo()['idCampagneMail']);
                            $campagneMail
                                ->setStatus(3)
                            ;
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
                    }
                } else {
                    $envPaiementApi->isUsedApproved();
                    $this->em->flush();
                }

                return 200;
                exit();            
                break;
            case 'transaction.canceled':
                // Transaction annulée
                $idTransaction = $event->entity->id;
                $myTransaction = $this->transactionRepository->findOneBy(['idTransaction' => $idTransaction]);
                $transaction = Transaction::retrieve($idTransaction);
                $myTransaction->setStatus($transaction->status)->isUpdated();
                $this->em->flush();

                return 200;
                exit();
                break;
            default:
                // action par defaut si ce n'est ni approved ni canceled
                $idTransaction = $event->entity->id;
                $myTransaction = $this->transactionRepository->findOneBy(['idTransaction' => $idTransaction]);
                $transaction = Transaction::retrieve($idTransaction);
                $myTransaction->setStatus($transaction->status)->isUpdated();
                $this->em->flush();
                
                return 200;
                exit();
        }
        return 200;
    }

    #[Route('/whd/{routeWebhook}', name: 'webhookDressur')]
    public function webhookDressur($routeWebhook, EnvPaiementApiRepository $envPaiementApiRepository)
    {
        $envPaiementApi = $envPaiementApiRepository->findOneBy(['routeWebhook' => $routeWebhook]);
        $http_response_code = $this->allWebhookDressur($envPaiementApi);
        http_response_code($http_response_code);
    }

    #[Route('/checkTransaction', name: 'checkTransaction', methods: ['POST'])]
    public function checkTransaction(Request $request, VerificationsDS $verificationsDS, TransactionRepository $transactionRepository, SessionDS $sessionDS, FormuleBoostRepository $formuleBoostRepository): Response
    {
        FedaPay::setApiKey("sk_live_4Q00INMNKwiJcdt17fNJyOUo");
        FedaPay::setEnvironment('live');

        $datas = $request->request; 
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $idTransaction = $datas->get('idTransaction');

        $verificationUser = $this->verificationsDS->verifUSer($uid);
        if($verificationUser["error"] == true){
            return new JsonResponse([
                'error' => true,
                'titre' => $verificationUser["titre"],
                'message' => $verificationUser["message"],
                'deleted' => $verificationUser["deleted"],
                'blocked' => $verificationUser["blocked"],
            ]);
        }
        $user = $verificationUser["user"];

        if(!$user->getTelIsVerified()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Your WhatsApp number has not yet been confirmed. If this is an error, contact us on WhatsApp.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Votre numéro WhatsApp na pas encore été confirmer. S'il s'agit d'une erreur, contactez-nous sur WhatsApp.",
            ]);
        }

        if(!$user->getMailIsVerified()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Please confirm your email address.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Veuillez confirmez votre adresse mail.",
            ]);
        }

        $myTransaction = $this->transactionRepository->findOneBy(['idTransaction' => $idTransaction]);
        if(!$myTransaction){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "We have encountered a problem, contact Assistance by WhatsApp.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
            ]);
        } else {
            if($myTransaction->getStatus() != "approved") {
                $formuleBoost = $this->formuleBoostRepository->find($myTransaction->getAnnotherInfo()['formulBoostId']);

                $transaction = Transaction::retrieve($idTransaction);

                if($transaction->status == "approved") {
                    $myTransaction->setStatus($transaction->status)->isUpdated();

                    $boost = new Boost();
                    $boost->setFormuleBoost($formuleBoost)
                        ->setMode("Payant")
                        ->setUser($user)
                        ->setDateDebut(new DateTime())
                        ->setDateExp(new DateTime("+ ".$formuleBoost->getNbrJour()."days"))
                    ;
                    $this->em->persist($boost);

                    $this->em->flush();

                    if($sessionDS->get("langUserPhone") != "fr") {
                        return new JsonResponse([
                            'error' => false,
                            'transaction' => true,
                            'titre' => 'Transaction Validate...',
                            'message' => "Your Paid Boost is activated...",
                        ]);
                    }
                    return new JsonResponse([
                        'error' => false,
                        'transaction' => true,
                        'titre' => 'Transaction Valider...',
                        'message' => "Votre Boost Payant est activé...",
                    ]);
                } else {
                    $myTransaction->setStatus($transaction->status)->isUpdated();

                    $this->em->flush();

                    if($sessionDS->get("langUserPhone") != "fr") {
                        return new JsonResponse([
                            'error' => true,
                            'titre' => "Transaction ($transaction->status) ...?",
                            'message' => "Please contact Dressur Support by WhatsApp ifthis is an error...",
                        ]);
                    }
                    return new JsonResponse([
                        'error' => false,
                        'transaction' => false,
                        'titre' => "Transaction ($transaction->status) ...?",
                        'message' => "Veuillez contactez l'Assistance Dressur par WhatsApp s'il s'agit d'une erreur...",
                    ]);
                }
            }

            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => "Transaction Validate...",
                    'message' => "Your Paid Boost was already activated...",
                ]);
            }
            return new JsonResponse([
                'error' => false,
                'transaction' => true,
                'titre' => 'Transaction Valider...',
                'message' => "Votre Boost Payant était déja activé...",
            ]);
        }

        if($sessionDS->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Mistake!',
                'message' => "We have encountered a problem, contact Assistance by WhatsApp.",
            ]);
        }
        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
        ]);
    }
}
