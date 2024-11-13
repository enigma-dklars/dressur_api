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

    public function __construct(EntityManagerInterface $em, EnvRepository $env, SendMail $sendMail)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->sendMail = $sendMail;
    }


    #[Route('/listeFormulePromoReseau', name: 'listeFormulePromoReseau', methods: ['POST', 'GET'])]
    public function listeFormulePromoReseau(Request $request, SessionDS $sessionDS, TraitementsDS $traitementsDS): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);
        
        return new JsonResponse([
            'error' => false,
            'listeFormulePromoReseau' => $traitementsDS->listeFormulePromoReseau(),
        ]);
    }

    #[Route('/listPromoReseau/{uid}/{langUserPhone}', name: 'listPromoReseau', methods: ['POST', "GET"])]
    public function listPromoReseau(User $user, $langUserPhone, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $sessionDS->set("langUserPhone", $langUserPhone);
        
        return new JsonResponse($traitementsDS->userPromoReseaus($user->getPromoReseaus()));
    }

    #[Route('/newPromoReseau', name: 'newPromoReseau', methods: ['POST', 'GET'])]
    public function newPromoReseau(Request $request, SessionDS $sessionDS, FormulePromoReseauRepository $formulePromoReseauRepository, VerificationsDS $verificationsDS, UserRepository $userRepository, TraitementsDS $traitementsDS, PromoReseauRepository $promoReseauRepository, MethodePaiementRepository $methodePaiementRepository): Response
    {
        $datas = $request->request;        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);
        $uid = $datas->get('uid');
        
        $idFormulePromoReseau = $datas->get('idFormulePromoReseau');
        $qteDemander = $datas->get('qteDemander');
        $prixQteDemander = $datas->get('prixQteDemander');
        $lien = $datas->get('lien');
        $valueMethodePaiement = $datas->get('valueMethodePaiement'); // mon_argent
        $tel = $datas->get('tel');
        
        if(!$idFormulePromoReseau || !$qteDemander || !$prixQteDemander || !$lien || !$valueMethodePaiement || !$tel){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Please fill in all the information requested in the form.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez renseigner toutes les informations demandées dans le formulaire.',
            ]);
        }
        if($prixQteDemander > 20000){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "For transactions over 20,000 FCFA, please contact Dressur Support.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => "Pour les transactions de plus de 20.000 FCFA, veuillez svp contacter l'Assistance Dressur.",
            ]);
        }
        $url_promo_en_attente = $promoReseauRepository->findBy(['status' => 1, 'url' => $lien]);
        $url_promo_en_cours = $promoReseauRepository->findBy(['status' => 2, 'url' => $lien]);
        if($url_promo_en_attente || $url_promo_en_cours) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => "One of your promotions initiated with this URL has not yet been completed. Please wait until the end before starting another one. THANKS.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => "Une de vos promotions initiée avec cette URL n'est pas encore terminée. Veuillez attendre la fin avant de démarrer une autre. Merci.",
            ]);
        }

        $user = $userRepository->findOneBy(['uid' => $uid]);

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

        $formulePromoReseau = $formulePromoReseauRepository->find($idFormulePromoReseau);
        if(!$formulePromoReseau) {
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

        $verificationNumTel = $verificationsDS->verifFormatNumTel($tel);
        if($verificationNumTel["error"] == true){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Please enter a valid phone number preceded by its prefix."]);
            }
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix."]);
        }
        $tel = $verificationNumTel["e164"];

        if(!$valueMethodePaiement){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Please choose a Payment Method...",
                ]);
            }
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
            $envPaiementApi = $traitementsDS->getEnvPaiementApiDisponible();
            if(!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : ".$uid, "Aucun Webhook Disponible");
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
                "description" => "Dressur :  Boost Réseau Sociaux : ". $formulePromoReseau->getTitre() ." - ". $prixQteDemander ."FCFA : Transaction for ". $user->getPseudo() ." ".$user->getMail(),
                "amount" => $prixQteDemander,
                "currency" => ["iso" => "XOF"],
                "customer" => [
                    "firstname" => $user->getPseudo(),
                    "lastname" => $user->getNom(),
                    "email" => $user->getMail(),
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
                    ])
                ;
                $this->em->persist($myTransaction);
                $this->em->flush();
        
                $resultat = $traitementsDS->startPaiement($transaction, $valueMethodePaiement);
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
    
                $this->sendMail->sendReport("uUid : ".$user->getUid()." WhatsApp : ".$user->getTel(), $th);
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
