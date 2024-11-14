<?php

namespace App\Controller\API;

use App\Entity\User;
use FedaPay\FedaPay;
use FedaPay\Transaction;
use App\Services\SessionDS;
use App\Entity\CampagneMail;
use App\Entity\Promotion;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\BoostRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FormuleBoostRepository;
use App\Repository\PromotionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Transaction as EntityTransaction;
use App\Entity\UserBot;
use App\Repository\CampagneMailRepository;
use App\Repository\FormuleCampagneMailRepository;
use App\Repository\FormuleDressurBotRepository;
use App\Repository\MethodePaiementRepository;
use App\Repository\PromoReseauRepository;
use App\Repository\UserBotRepository;
use App\Repository\UserRepository;
use App\Utilities\SendMail;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]

class DressurBotController extends AbstractController
{
    private $em;
    private $env;
    private $sendMail;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, SendMail $sendMail)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->sendMail = $sendMail;
    }

    #[Route('/dressurUserBot', name: 'dressurUserBot')]
    public function dressurUserBot(Request $request, SendMail $sendMail, UserBotRepository $userBotRepository, VerificationsDS $verificationsDS): Response
    {
        $versionApp = $request->get("versionApp");
        if(strlen($this->env->getVersionDressurBot()) > 0){
            if($this->env->getVersionDressurBot() != $versionApp){
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez utiliser la dernière version de Dressur Bot."]);
            }
        }

        $nom = $request->get("nom");
        $email = $request->get("email");
        $numero = $request->get("numero");
        $signature = $request->get("signature");
        $adresseMac = $request->get("adresseMac");
        $uuidMachine = $request->get("uuidMachine");
        $diskSerialNumber = $request->get("diskSerialNumber");
        $nbrMsgSent = $request->get("nbrMsgSent");

        $userBotFind = $userBotRepository->findOneBy([
            'email' => $email,
            'numero' => $numero,
            'uuidMachine' => $uuidMachine,
            'diskSerialNumber' => $diskSerialNumber,
        ]);

        $verificationNumTel = $verificationsDS->verifFormatNumTel($numero);
        if($verificationNumTel["error"] == true){
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix."]);
        }
        $numero = $verificationNumTel["e164"];

        if (!$verificationsDS->verifMail($email)) {
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir une adresse E-Mail valide.",]); 
        }

        if($userBotFind) {
            $userBotFind->setNbrMsgSent((string)$nbrMsgSent);
            $userBotFind->isUpdated();
            $this->em->flush();
            if($userBotFind->getExpiratedAt() > new DateTime()) {
                return new JsonResponse([
                    'error' => false,
                    'target' => "configPage",
                    'userInfo' => [
                        'nom' => $nom,
                        'email' => $email,
                        'numero' => $numero,
                        'signature' => $userBotFind->getSignature(),
                        'adresseMac' => $adresseMac,
                        'uuidMachine' => $uuidMachine,
                        'diskSerialNumber' => $diskSerialNumber,
                        'createdAt' => $userBotFind->getCreatedAt(),
                        'expiratedAt' => $userBotFind->getExpiratedAt(),
                    ],
                ]);
            } else {
                return new JsonResponse([
                    'error' => false,
                    'target' => "paiementPage",
                    'userInfo' => [
                        'nom' => $nom,
                        'email' => $email,
                        'numero' => $numero,
                        'signature' => $userBotFind->getSignature(),
                        'adresseMac' => $adresseMac,
                        'uuidMachine' => $uuidMachine,
                        'diskSerialNumber' => $diskSerialNumber,
                        'createdAt' => $userBotFind->getCreatedAt(),
                        'expiratedAt' => $userBotFind->getExpiratedAt(),
                    ],
                ]);
            }
        } else {
            $html = $this->renderView('emails/welcomeToDressurBot.html.twig',[
                "nom" => $nom,
                "email" => $email,
                "numero" => $numero,
            ]);
    
            try {
                $newUserBot = new UserBot();
                $newUserBot->setNom($nom)
                    ->setEmail($email)
                    ->setNumero($numero)
                    ->setAdresseMac($adresseMac)
                    ->setUuidMachine($uuidMachine)
                    ->setDiskSerialNumber($diskSerialNumber)
                ;
                $this->em->persist($newUserBot);
                $this->em->flush();

                $sendMail->smtpMail(
                    $email,
                    "BIENVENU SUR DRESSUR BOT",
                    $html,
                    "dressur.ds@gmail.com", 
                    "Dressur Bot No ".time(), 
                );
                
                return new JsonResponse([
                    'error' => false,
                    'target' => "paiementPage",
                    'userInfo' => [
                        'nom' => $nom,
                        'email' => $email,
                        'numero' => $numero,
                        'signature' => $newUserBot->getSignature(),
                        'adresseMac' => $adresseMac,
                        'uuidMachine' => $uuidMachine,
                        'diskSerialNumber' => $diskSerialNumber,
                        'createdAt' => $newUserBot->getCreatedAt(),
                        'expiratedAt' => $newUserBot->getExpiratedAt(),
                    ],
                ]);
            } catch (\Throwable $th) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Mail non envoyer. Veuillez contactez l'assistance de Dressur Bot...",
                ]);
            }
        }
        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Erreur de traitement. Veuillez contactez l'assistance de Dressur Bot...",
        ]);
    }

    #[Route('/listeFormuleDressurBot', name: 'listeFormuleDressurBot', methods: ['POST', 'GET'])]
    public function listeFormuleDressurBot(FormuleBoostRepository $formuleBoostRepository, TraitementsDS $traitementsDS): Response
    {
        return new JsonResponse([
            'error' => false,
            'listeFormuleDressurBot' => $traitementsDS->listeFormuleDressurBot(),
        ]);
    }

    #[Route('/paiementDressurUserBot', name: 'paiementDressurUserBot', methods: ['POST'])]
    public function paiementDressurUserBot(Request $request, FormuleBoostRepository $formuleBoostRepository, BoostRepository $boostRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository, TraitementsDS $traitementsDS, UserBotRepository $userBotRepository, FormuleDressurBotRepository $formuleDressurBotRepository, MethodePaiementRepository $methodePaiementRepository): Response
    {
        $datas = $request->request;        
        $sessionDS->set("langUserPhone", "fr");
        
        $email = $request->get("email");
        $numero = $request->get("numero");
        $adresseMac = $request->get("adresseMac");
        $uuidMachine = $request->get("uuidMachine");
        $diskSerialNumber = $request->get("diskSerialNumber");

        $idFormulDressurBot = $datas->get('idFormulDressurBot');
        $valueMethodePaiement = $datas->get('valueMethodePaiement');
        $tel = $datas->get('tel'); 

        $envPaiementApi = $traitementsDS->getEnvPaiementApiFedaPayDisponible();
        if(!$envPaiementApi) {
            $this->sendMail->sendReport("user bot tel : ".$tel, "Aucun Webhook Disponible pour FedaPay");
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Payment error. Please contact the administrators.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
            ]);
        }
        FedaPay::setApiKey($envPaiementApi->getApiKey());
        FedaPay::setEnvironment($envPaiementApi->getEnvironment());
        
        if(!$idFormulDressurBot){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez choisir une formule.',
            ]);
        }

        if(!$tel){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez saisir un numéro de téléphone.',
            ]);
        }

        $verificationNumTel = $verificationsDS->verifFormatNumTel($tel);
        if($verificationNumTel["error"] == true){
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix."]);
        }
        $tel = $verificationNumTel["e164"];

        $userBotFind = $userBotRepository->findOneBy([
            'email' => $email,
            'numero' => $numero,
            'uuidMachine' => $uuidMachine,
            'diskSerialNumber' => $diskSerialNumber,
        ]);

        $formulDressurBot = $formuleDressurBotRepository->find($idFormulDressurBot);
        if(!$formulDressurBot){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
            ]);
        }

        if(!$valueMethodePaiement){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez choisir une Methode de Paiement...',
            ]);
        }
        $methodePaiementEntity = $methodePaiementRepository->find($valueMethodePaiement);
        if(!$methodePaiementEntity) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Please choose a valide Payment Method...",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez choisir une Methode de Paiement valide...',
            ]);
        }
        if($methodePaiementEntity->getAggregator() == "FedaPay"){
            $envPaiementApi = $traitementsDS->getEnvPaiementApiFedaPayDisponible();
            if(!$envPaiementApi) {
                $this->sendMail->sendReport("user bot tel : ".$tel, "Aucun Webhook Disponible pour FedaPay");
                if($sessionDS->get("langUserPhone") != "fr") {
                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Erreur!',
                        'message' => "Payment error. Please contact the administrators.",
                    ]);
                }
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                ]);
            }
            FedaPay::setApiKey($envPaiementApi->getApiKey());
            FedaPay::setEnvironment($envPaiementApi->getEnvironment());

            $array_create_transaction = [
                "description" => "DressurBot : Activation : ". $formulDressurBot->getTitre() ." - ". $formulDressurBot->getPrix() ."FCFA : Transaction for ". $userBotFind->getNom() ." ".$userBotFind->getEmail(),
                "amount" => $formulDressurBot->getPrix(),
                "currency" => ["iso" => "XOF"],
                "customer" => [
                    "firstname" => $userBotFind->getNom(),
                    "lastname" => $userBotFind->getNom(),
                    "email" => $userBotFind->getEmail(),
                    "phone_number" => [
                        "number" => $tel,
                        "country" => $traitementsDS->getCountryWithMethodePaiement($valueMethodePaiement)
                    ]
                ]
            ];
    
            try {
                $transaction = Transaction::create($array_create_transaction);
        
                $myTransaction  = new EntityTransaction();
                $myTransaction
                    ->setUserBot($userBotFind)
                    ->setTransactionFor("dressur_bot_activation")
                    ->setIdTransaction($transaction["id"])
                    ->setReference($transaction["reference"])
                    ->setAmount($transaction["amount"])
                    ->setStatus($transaction["status"])
                    ->setCustomerId($transaction["customer_id"])
                    ->setCurrencyId($transaction["currency_id"])
                    ->setAnnotherInfo([
                        'userBotId' => $userBotFind->getId(),
                        'formulDressurBotId' => $formulDressurBot->getId(),
                    ])
                ;
                $this->em->persist($myTransaction);
        
                $this->em->flush();
        
                $resultat = $traitementsDS->startPaiementFedaPay($transaction, $methodePaiementEntity);
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $msgError = (string)$th;
                if (strpos($msgError, "Vous avez excédé le nombre de transactions hebdomadaire requis. 10 transactions approuvées sont autorisées par semaine.") !== false) {
                    $envPaiementApi->setCountTransactionApproved(10);
                    $this->em->flush();
    
                    if($sessionDS->get("langUserPhone") != "fr") {
                        return new JsonResponse([
                            'error' => true,
                            'titre' => 'Excuse us please!',
                            'message' => "Please submit the form again. Thank you.",
                        ]);
                    }
                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Excusez-nous svp!',
                        'message' => "Veuillez soumettre une nouvelle fois le formulaire. Merci.",
                    ]);
                }
    
                $this->sendMail->sendReport("DressurBot uUid : ".$userBotFind->getId()." WhatsApp : ".$userBotFind->getNumero(), $th);
                if($sessionDS->get("langUserPhone") != "fr") {
                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Erreur!',
                        'message' => "We encountered an error. You will be contacted by an administrator.",
                    ]);
                }
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        } else {
            // logique de paiement FeexPay
        }

        return new JsonResponse([
            'error' => false,
        ]);
    }
}