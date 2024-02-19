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
use App\Repository\PromotionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/api', name: 'api_')]

class PromotionController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1);
    }

    #[Route('/newPromotion', name: 'newPromotion', methods: ['POST'])]
    public function newPromotion(Request $request, VerificationsDS $verificationsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository): Response
    {
        $datas = $request->request;
        $files = $request->files;

        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $text = $datas->get('text');

        $image = $files->get('image');

        if ($text === null || $image === null) {
            return new JsonResponse([
                'error' => true,
                'titre' => "Erreur",
                'message' => "Veuillez fournir un texte et une image.",
            ]);
        }

        $verificationUser = $verificationsDS->verifUSer($uid);
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

        // Vérification et traitement de l'image
        if (!$image->isValid()) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Error during image processing.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Erreur lors du traitement de l'image.",
            ]);
        }

        // Générer un nom de fichier unique
        $fileName = "promotion__".md5(uniqid()).'.'.$image->getClientOriginalExtension();

        // Déplacer l'image vers le dossier de promotion dans le dossier public
        try {
            $image->move($this->getParameter('promotion_directory'), $fileName);
        } catch (FileException $e) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => $e->getMessage(),
            ]);
        }

        $promotion = new Promotion();
        $promotion->setUser($user)
            ->setImage($fileName)
            ->setDescription($text)
        ;
        $promotionRepository->save($promotion, true);

        // Enregistrer le chemin de l'image dans la base de données ou effectuer d'autres opérations nécessaires
        // ...
        if($sessionDS->get("langUserPhone") != "fr") { 
            return new JsonResponse([
                'error' => false
            ]); 
        }
        return new JsonResponse([
            'error' => false
        ]);
    }


    #[Route('/listPromotion/{uid}/{langUserPhone}', name: 'listPromotion', methods: ['POST', "GET"])]
    public function listPromotion(User $user, $langUserPhone, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $sessionDS->set("langUserPhone", $langUserPhone);
        
        return new JsonResponse($traitementsDS->userPromos($user->getPromotions()));
    }

    #[Route('/newPromo', name: 'newPromo', methods: ['POST'])]
    public function newPromo(Request $request, FormuleBoostRepository $formuleBoostRepository, UserRepository $userRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $idFormulBoost = $datas->get('idFormulBoost');
        $idPromotion = $datas->get('idPromotion');

        $verificationUser = $verificationsDS->verifUSer($uid);
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

        $formulBoost = $formuleBoostRepository->find($idFormulBoost);
        if(!$formulBoost){
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

        if($user->getSoldeBonus() < $formulBoost->getPrix()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Whoops!',
                    'message' => "Your bonus balance is insufficient.\nReferred users to increase your bonus balance.",
                ]);                
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => "Votre solde bonus est insuffisant.\nParrainé des utilisateurs pour augmenté votre solde bonus.",
            ]);
        }
        
        $user->debitSoldeBonus($formulBoost->getPrix());

        $promotion = $promotionRepository->find($idPromotion);

        if($promotion->getStatus() == 2){
            $promotion->setFormuleBoost($formulBoost)
                ->setDateDebut(new DateTime())
                ->setDateExp(new DateTime("+ ".$formulBoost->getNbrJour()."days"))
                ->setStatus(3)
            ;
                
            $this->em->flush();

            return new JsonResponse([
                'error' => false,
            ]);
        }
        if($sessionDS->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Whoops!',
                'message' => "This promotion has already been started.",
            ]);                
        }
        return new JsonResponse([
            'error' => true,
            'titre' => 'Oups!',
            'message' => "Cette promotion est déjà été démarrée.",
        ]);      
    }

    #[Route('/newPromoPayant', name: 'newPromoPayant', methods: ['POST'])]
    public function newPromoPayant(Request $request, FormuleBoostRepository $formuleBoostRepository, BoostRepository $boostRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository): Response
    {
        FedaPay::setApiKey("sk_live_Y5QwNfYEjXX6VXp0iqWqhaZX");
        FedaPay::setEnvironment('live');

        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $idPromotion = $datas->get('idPromotion');
        $uid = $datas->get('uid');
        $idFormulBoost = $datas->get('idFormulBoost');
        $valueMethodePaiement = $datas->get('valueMethodePaiement');
        $tel = $datas->get('tel');

        $verificationUser = $verificationsDS->verifUSer($uid);
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

        $formulBoost = $formuleBoostRepository->find($idFormulBoost);
        if(!$formulBoost){
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
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Please enter a valid phone number preceded by its prefix Exp(+229 62005500)."]);
            }
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix Exp(+229 62005500)."]);
        }
        $tel = $verificationNumTel["e164"];

        if(!$tel){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Please enter a phone number.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez saisir un numéro de téléphone.',
            ]);
        }

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

        if($valueMethodePaiement == "mtn" || $valueMethodePaiement == "moov" || $valueMethodePaiement == "sbin") { $country = "bj"; }
        else if($valueMethodePaiement == "mtn_ci" || $valueMethodePaiement == "orange_ci") { $country = "ci"; }
        else if($valueMethodePaiement == "orange_sn" || $valueMethodePaiement == "free_sn") { $country = "sn"; }
        else if($valueMethodePaiement == "moov_tg" || $valueMethodePaiement == "togocel") { $country = "tg"; }
        else if($valueMethodePaiement == "airtel_ne") { $country = "ne"; }
        else if($valueMethodePaiement == "orange_ml") { $country = "ml"; }
        else if($valueMethodePaiement == "mtn_gn") { $country = "gn"; }

        $array_create_transaction = [
            "description" => "Dressur :  Promotion Payante : ". $formulBoost->getTitre() ." - ". $formulBoost->getPrix() ."FCFA : Transaction for ". $user->getPseudo() ." ".$user->getMail(),
            "amount" => $formulBoost->getPrix(),
            "currency" => ["iso" => "XOF"],
            "customer" => [
                "firstname" => $user->getPseudo(),
                "lastname" => $user->getNom() ? $user->getNom() : $user->getPseudo(),
                "email" => $user->getMail(),
                "phone_number" => [
                    "number" => $tel,
                    "country" => $country
                ]
            ]
        ];

        $promotion = $promotionRepository->find($idPromotion);

        if($promotion->getStatus() == 2){
            $promotion->setFormuleBoost($formulBoost);
            
            $transaction = Transaction::create($array_create_transaction);

            $myTransaction  = new EntityTransaction();
            $myTransaction
                ->setUser($user)
                ->setTransactionFor("boost_affaire")
                ->setIdTransaction($transaction["id"])
                ->setReference($transaction["reference"])
                ->setAmount($transaction["amount"])
                ->setStatus($transaction["status"])
                ->setCustomerId($transaction["customer_id"])
                ->setCurrencyId($transaction["currency_id"])
                ->setAnnotherInfo([
                    'formulBoostId' => $formulBoost->getId(),
                    'promotionId' => $promotion->getId(),
                ])
            ;
            $this->em->persist($myTransaction);

            $this->em->flush();

            $token = $transaction->generateToken()->token;
            $mode = $valueMethodePaiement;
            $transaction->sendNowWithToken($mode, $token);

            return new JsonResponse([
                'error' => false,
            ]);
        }
        if($sessionDS->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Whoops!',
                'message' => "This promotion has already been started.",
            ]);                
        }
        return new JsonResponse([
            'error' => true,
            'titre' => 'Oups!',
            'message' => "Cette promotion est déjà été démarrée.",
        ]);
    }

    #[Route('/setPromotionToWatch/{id}', name: 'setPromotionToWatch', methods: ['POST', "GET"])]
    public function setPromotionToWatch(Promotion $promotion): Response
    {
        $promotion->setToWatch();
        $this->em->flush();
        
        return new Response("OK");
    }
}
