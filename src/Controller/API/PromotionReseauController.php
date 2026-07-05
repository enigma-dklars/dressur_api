<?php

namespace App\Controller\API;

use DateTime;
use FedaPay\FedaPay;
use FedaPay\Webhook;
use App\Entity\Boost;
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
use App\Entity\User;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\MethodePaiementRepository;
use App\Repository\PromoReseauRepository;
use App\Repository\PromotionRepository;
use App\Services\CookieDS;
use App\Utilities\SendMail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/api', name: 'api_')]

class PromotionReseauController extends AbstractController
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


    #[Route('/listeFormulePromoReseau', name: 'listeFormulePromoReseau', methods: ['POST', 'GET'])]
    public function listeFormulePromoReseau(Request $request, SessionDS $sessionDS, TraitementsDS $traitementsDS): Response
    {                
        return new JsonResponse([
            'error' => false,
            'listeFormulePromoReseau' => $traitementsDS->listeFormulePromoReseau(),
            'listeMethodePaiements' => $traitementsDS->listeMethodePaiements(),
        ]);
    }

    #[Route('/listPromoReseau/{uid}/{langUserPhone}', name: 'listPromoReseau', methods: ['POST', "GET"])]
    public function listPromoReseau(User $user, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        return new JsonResponse($traitementsDS->userPromoReseaus($user->getPromoReseaus(), $user));
    }

    #[Route('/newPromoReseau', name: 'newPromoReseau', methods: ['POST', 'GET'])]
    public function newPromoReseau(Request $request, SessionDS $sessionDS, FormulePromoReseauRepository $formulePromoReseauRepository, VerificationsDS $verificationsDS, UserRepository $userRepository, TraitementsDS $traitementsDS, PromoReseauRepository $promoReseauRepository, MethodePaiementRepository $methodePaiementRepository): Response
    {
        $datas = $request->request;        
        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        
        $idFormulePromoReseau = $datas->get('idFormulePromoReseau');
        $qteDemander = $datas->get('qteDemander');
        $prixQteDemander = $datas->get('prixQteDemander');
        $lien = $datas->get('lien');
        $valueMethodePaiement = $datas->get('valueMethodePaiement'); // mon_argent
        $tel = $datas->get('tel');
        
        if(!$idFormulePromoReseau || !$qteDemander || !$prixQteDemander || !$lien || !$valueMethodePaiement || !$tel){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez renseigner toutes les informations demandées dans le formulaire.',
            ]);
        }
        
        $url_promo_en_attente = $promoReseauRepository->findBy(['status' => 1, 'url' => $lien]);
        $url_promo_en_cours = $promoReseauRepository->findBy(['status' => 2, 'url' => $lien]);
        if($url_promo_en_attente || $url_promo_en_cours) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => "Une de vos promotions initiée avec cette URL n'est pas encore terminée. Veuillez attendre la fin avant de démarrer une autre. Merci.",
            ]);
        }

        $user = $userRepository->findOneBy(['uid' => $uid]);

        if(!$user->getTelIsVerified()){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Votre numéro WhatsApp na pas encore été confirmer. S'il s'agit d'une erreur, contactez-nous sur WhatsApp.",
            ]);
        }

        $formulePromoReseau = $formulePromoReseauRepository->find($idFormulePromoReseau);
        if(!$formulePromoReseau) {

            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
            ]);
        }

        $verificationNumTel = $verificationsDS->verifFormatNumTel($tel);
        if($verificationNumTel["error"] == true){
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix."]);
        }
        $tel = $verificationNumTel["e164"];

        if(!$valueMethodePaiement){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez choisir une Methode de Paiement...',
            ]);
        }
        $methodePaiementEntity = $methodePaiementRepository->find($valueMethodePaiement);
        if(!$methodePaiementEntity) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez choisir une Methode de Paiement valide...',
            ]);
        }
        if($methodePaiementEntity->getAggregator() == "FedaPay"){
            $envPaiementApi = $traitementsDS->getEnvPaiementApiFedaPayDisponible();
            if(!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : ".$uid, "Aucun Webhook Disponible pour FedaPay");
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                ]);
            }
            FedaPay::setApiKey($envPaiementApi->getApiKey());
            FedaPay::setEnvironment($envPaiementApi->getEnvironment());

            $array_create_transaction = [
                "description" => "Dressur :  Boost Réseau Sociaux : ". $formulePromoReseau->getTitre() ." - ". $prixQteDemander ."FCFA : Transaction for ". $user->getPseudo() ." ".$user->getMail(),
                "amount" => $prixQteDemander,
                "currency" => ["iso" => "XOF"],
                "customer" => [
                    "firstname" => "Dressur : ".$user->getPseudo(),
                    "lastname" => $user->getNom(),
                    "email" => $user->getMail(),
                    "phone_number" => [
                        "number" => $tel,
                        "country" => $methodePaiementEntity->getCodePays()
                    ]
                ]
            ];
    
            try {
                $transaction = Transaction::create($array_create_transaction);
        
                $myTransaction  = new EntityTransaction();
                $myTransaction
                    ->setUser($user)
                    ->setTransactionFor("boost_reseau_sociaux")
                    ->setIdTransaction($transaction["id"])
                    ->setReference($transaction["reference"])
                    ->setAmount($transaction["amount"])
                    ->setStatus($transaction["status"])
                    ->setCustomerId($transaction["customer_id"])
                    ->setCurrencyId($transaction["currency_id"])
                    ->setAnnotherInfo([
                        'userId' => $user->getId(),
                        'userUid' => $user->getUid(),
                        'idFormulePromoReseau' => $idFormulePromoReseau,
                        'qteDemander' => $qteDemander,
                        'prixQteDemander' => $prixQteDemander,
                        'lien' => $lien,
                        'tel' => $tel,
                        'source' => ($datas->get('source') === 'web') ? 'web' : 'mobile',
                    ])
                ;
                $this->em->persist($myTransaction);
                $this->em->flush();
        
                $resultat = $traitementsDS->startPaiementFedaPay($transaction, $methodePaiementEntity);
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $this->sendMail->sendReport("uUid : ".$user->getUid()." WhatsApp : ".$user->getTel(), $th);

                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        } else {
            // logique fait de paiement FeexPay
            $envPaiementApi = $traitementsDS->getEnvPaiementApiFeexPayDisponible();
            if(!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : ".$uid, "Aucun Webhook Disponible pour FeexPay");
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
                    $prixQteDemander,
                    $tel,
                    $user->getPseudo(),
                    $user->getMail(),
                    "boost_reseau_sociaux",
                    [
                        'userId' => $user->getId(),
                        'userUid' => $user->getUid(),
                        'idFormulePromoReseau' => $idFormulePromoReseau,
                        'qteDemander' => $qteDemander,
                        'prixQteDemander' => $prixQteDemander,
                        'lien' => $lien,
                        'tel' => $tel,
                        'source' => ($datas->get('source') === 'web') ? 'web' : 'mobile',
                    ],
                    $user,
                    $request->getSchemeAndHttpHost()
                );
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $this->sendMail->sendReport("uUid : ".$user->getUid()." WhatsApp : ".$user->getTel(), $th);
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        }

        return new JsonResponse([
            'error' => false,
        ]);
    }
}
