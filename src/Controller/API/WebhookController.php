<?php

namespace App\Controller\API;

use DateTime;
use App\Entity\User;
use FedaPay\FedaPay;
use FedaPay\Webhook;
use App\Entity\Boost;
use App\Entity\PromoReseau;
use App\Entity\Promotion;
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
use App\Repository\FormulePromoAffaireRepository;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\PromotionRepository;
use App\Utilities\SendMail;
use App\Utilities\ZefameApi;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/api', name: 'api_')]

class WebhookController extends AbstractController
{
    private $em;
    private $transactionRepository;
    private $formuleBoostRepository;
    private $formulePromoAffaireRepository;
    private $campagneMailRepository;
    private $promotionRepository;
    private $formulePromoReseauRepository;
    private $verificationsDS;
    private $boostRepository;
    private $formuleDressurBotRepository;
    private $sendMail;
    private $zefameApi;

    public function __construct(EntityManagerInterface $em, TransactionRepository $transactionRepository, FormuleBoostRepository $formuleBoostRepository, CampagneMailRepository $campagneMailRepository, PromotionRepository $promotionRepository, FormulePromoReseauRepository $formulePromoReseauRepository, VerificationsDS $verificationsDS, BoostRepository $boostRepository, FormuleDressurBotRepository $formuleDressurBotRepository, FormulePromoAffaireRepository $formulePromoAffaireRepository, SendMail $sendMail, ZefameApi $zefameApi)
    {
        $this->em = $em;
        $this->transactionRepository = $transactionRepository;
        $this->formuleBoostRepository = $formuleBoostRepository;
        $this->formulePromoAffaireRepository = $formulePromoAffaireRepository;
        $this->campagneMailRepository = $campagneMailRepository;
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
                    if($myTransaction->getStatus() == "pending") {
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
                            $formulePromoAffaire = $this->formulePromoAffaireRepository->find($myTransaction->getAnnotherInfo()['formulePromoAffaire']);
                            $promotion = new Promotion();
                            $promotion
                                ->setMode("Payant")
                                ->setUser($myTransaction->getUser())
                                ->setFormulePromoAffaire($formulePromoAffaire)
                                ->setImage($myTransaction->getAnnotherInfo()['image'])
                                ->setDescription($myTransaction->getAnnotherInfo()['description'])
                            ;
                            $this->em->persist($promotion);
                        }

                        if($myTransaction->getTransactionFor() == "re_boost_affaire") {
                            $formulePromoAffaire = $this->formulePromoAffaireRepository->find($myTransaction->getAnnotherInfo()['formulBoostId']);
                            $promotion = $this->promotionRepository->find($myTransaction->getAnnotherInfo()['promotionId']);
                            $promotion->setMode("Payant")
                                ->setDateDebut(new DateTime())
                                ->setDateExp(new DateTime("+ ".$formulePromoAffaire->getNbrJour()."days"))
                                ->setReferencement($formulePromoAffaire->getReferencement())
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
                            $this->em->flush();

                            $formule = $boost->getFormulePromoReseau();
                            $formuleLower = mb_strtolower($formule, 'UTF-8');
                            if (strpos($formuleLower, 'commentaires') === false && strpos($formuleLower, 'customisés') === false) {
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
                    $myTransaction  = new EntityTransaction();
                    $myTransaction
                        ->setTransactionFor("paiement_direct")
                        ->setIdTransaction($idTransaction)
                        ->setStatus("approved")
                    ;
                    $this->em->persist($myTransaction);
                    
                    $envPaiementApi->isUsedApproved();
                    $this->em->flush();
                }

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
    public function webhookDressur($routeWebhook, Request $request, EnvPaiementApiRepository $envPaiementApiRepository)
    {
        try {
            $envPaiementApi = $envPaiementApiRepository->findOneBy(['routeWebhook' => $routeWebhook]);
            $http_response_code = $this->allWebhookDressur($envPaiementApi);
            http_response_code($http_response_code);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport("Error webhookDressur : ".$routeWebhook, $th."<br><br><br>LE CONTENU DE REQUESTE<br><br><br>".dump($request));
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                'messageError' => (string)$th,
            ]);
        }
    }
}
