<?php

namespace App\Controller\API;

use DateTime;
use App\Entity\User;
use FedaPay\FedaPay;
use FedaPay\Webhook;
use App\Entity\Boost;
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
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/api', name: 'api_')]

class BoostController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1);
    }

    #[Route('/listeFormuleBoost', name: 'listeFormuleBoost', methods: ['POST', 'GET'])]
    public function listeFormuleBoost(FormuleBoostRepository $formuleBoostRepository): Response
    {
        $listeFormulBoost = [];
        foreach ($formuleBoostRepository->findAll() as $boost) {
            array_push($listeFormulBoost, [
                "id" => $boost->getId(),
                "label" => $boost->getTitre(),
                "prix" => $boost->getPrix(),
                "jours" => $boost->getNbrJour(),
            ]);
        }
        return new JsonResponse([
            'error' => false,
            'listeFormulBoost' => $listeFormulBoost,
        ]);
    }

    #[Route('/newBoost', name: 'newBoost', methods: ['POST'])]
    public function newBoost(Request $request, FormuleBoostRepository $formuleBoostRepository, UserRepository $userRepository, BoostRepository $boostRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        $datas = $request->request;
        
        $programBoost = false;
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $idFormulBoost = $datas->get('idFormulBoost');

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
                    'message' => "We have encountered a problem, contact WhatsPerson Assistance by WhatsApp.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré un problème, contactez l'Assistance WhatsPerson par WhatsApp.",
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

        if ($verificationsDS->siBoostEnCours($boostRepository->findBy(['user' => $user]))) {
            $programBoost = true;
        }
        
        $user->debitSoldeBonus($formulBoost->getPrix());

        $boost = new Boost();
        $boost->setFormuleBoost($formulBoost)
              ->setUser($user)
        ;
        if($programBoost == false) {
            $boost->setDateDebut(new DateTime())
                ->setDateExp(new DateTime("+ ".$formulBoost->getNbrJour()."days"));
        } else {
            $lastBoostDateExp = ($boostRepository->findOneBy(['user' => $user], ["id" => "DESC"]))->getDateExp();
            $boost->setDateDebut($lastBoostDateExp)
                ->setDateExp(new DateTime(date('d-m-Y H:i', strtotime("+ ".$formulBoost->getNbrJour()."days ".$lastBoostDateExp->format('d-m-Y H:i')))))
            ;
        }
        $this->em->persist($boost);
        $this->em->flush();

        return new JsonResponse([
            'error' => false,
        ]);
    }

    #[Route('/newBoostPayant', name: 'newBoostPayant', methods: ['POST'])]
    public function newBoostPayant(Request $request, FormuleBoostRepository $formuleBoostRepository, BoostRepository $boostRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS): Response
    {
        FedaPay::setApiKey("sk_live_Y5QwNfYEjXX6VXp0iqWqhaZX");
        FedaPay::setEnvironment('live');

        $datas = $request->request;

        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

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

        if(!$this->env->getDoBoostPayant()){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Paid boosts are temporarily unavailable. Do a free boost instead."]);
            }
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Les boosts payants sont momentanément indisponibles. Faite plutôt un boost gratuit."]);
        }

        $formulBoost = $formuleBoostRepository->find($idFormulBoost);
        if(!$formulBoost){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "We have encountered a problem, contact WhatsPerson Assistance by WhatsApp.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré un problème, contactez l'Assistance WhatsPerson par WhatsApp.",
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

        if ($verificationsDS->siBoostEnCours($boostRepository->findBy(['user' => $user]))) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "You already have a Contact Boost in progress...\nWait for it to finish before starting another.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Oups!',
                'message' => "Vous avez déja un Boost Contact en cours...\nAttendez la fin de ce dernier avant de démarrer un autre.",
            ]);
        }

        if($valueMethodePaiement == "mtn" || $valueMethodePaiement == "moov") { $country = "bj"; }
        else if($valueMethodePaiement == "mtn_ci" || $valueMethodePaiement == "orange_ci") { $country = "ci"; }
        else if($valueMethodePaiement == "orange_sn" || $valueMethodePaiement == "free_sn") { $country = "sn"; }
        else if($valueMethodePaiement == "moov_tg" || $valueMethodePaiement == "togocel") { $country = "tg"; }
        else if($valueMethodePaiement == "airtel_ne") { $country = "ne"; }
        else if($valueMethodePaiement == "orange_ml") { $country = "ml"; }
        else if($valueMethodePaiement == "mtn_gn") { $country = "gn"; }

        $array_create_transaction = [
            "description" => "WhatsPerson : Boost Payant : ". $formulBoost->getTitre() ." - ". $formulBoost->getPrix() ."FCFA : Transaction for ". $user->getPseudo() ." ".$user->getMail(),
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

        $transaction = Transaction::create($array_create_transaction);

        $myTransaction  = new EntityTransaction();
        $myTransaction->setFormuleBoost($formulBoost)
                      ->setUser($user)
                      ->setIdTransaction($transaction["id"])
                      ->setReference($transaction["reference"])
                      ->setAmount($transaction["amount"])
                      ->setStatus($transaction["status"])
                      ->setCustomerId($transaction["customer_id"])
                      ->setCurrencyId($transaction["currency_id"])
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

    #[Route('/checkTransaction', name: 'checkTransaction', methods: ['POST'])]
    public function checkTransaction(Request $request, VerificationsDS $verificationsDS, TransactionRepository $transactionRepository, SessionDS $sessionDS): Response
    {
        FedaPay::setApiKey("sk_live_Y5QwNfYEjXX6VXp0iqWqhaZX");
        FedaPay::setEnvironment('live');

        $datas = $request->request;

        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $idTransaction = $datas->get('idTransaction');

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

        $myTransaction = $transactionRepository->findOneBy(['idTransaction' => $idTransaction]);
        if(!$myTransaction){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "We have encountered a problem, contact WhatsPerson Assistance by WhatsApp.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré un problème, contactez l'Assistance WhatsPerson par WhatsApp.",
            ]);
        } else {
            if($myTransaction->getStatus() != "approved") {
                $formuleBoost = $myTransaction->getFormuleBoost();

                $transaction = Transaction::retrieve($idTransaction);

                if ($transaction->status == "approved") {
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
                            'message' => "Please contact WhatsPerson Support by WhatsApp if this is an error...",
                        ]);
                    }
                    return new JsonResponse([
                        'error' => false,
                        'transaction' => false,
                        'titre' => "Transaction ($transaction->status) ...?",
                        'message' => "Veuillez contactez l'Assistance WhatsPerson par WhatsApp s'il s'agit d'une erreur...",
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
                'message' => "We have encountered a problem, contact WhatsPerson Assistance by WhatsApp.",
            ]);
        }
        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Nous avons rencontré un problème, contactez l'Assistance WhatsPerson par WhatsApp.",
        ]);
    }

    #[Route('/webhookWhatsperson', name: 'webhookWhatsperson')]
    public function webhookWhatsperson(TransactionRepository $transactionRepository)
    {
        FedaPay::setApiKey("sk_live_Y5QwNfYEjXX6VXp0iqWqhaZX");
        FedaPay::setEnvironment('live');

        // You can find your endpoint's secret key in your webhook settings
        $endpoint_secret = 'wh_live_NJkrpSjT4UM2FaRO7zSEn_gN';

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_X_FEDAPAY_SIGNATURE'];
        $event = null;

        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload

            http_response_code(400);
            exit();
        } catch(\FedaPay\Error\SignatureVerification $e) {
            // Invalid signature

            http_response_code(400);
            exit();
        }

        // Handle the event
        switch ($event->name) {
            case 'transaction.approved':
                // Transaction approuvée
                $idTransaction = $event->entity->id;
                $myTransaction = $transactionRepository->findOneBy(['idTransaction' => $idTransaction]);
                if($myTransaction){
                    if($myTransaction->getStatus() != "approved") {
                        
                        $formuleBoost = $myTransaction->getFormuleBoost();
                        $transaction = Transaction::retrieve($idTransaction);
                        $myTransaction->setStatus($transaction->status)->isUpdated();

                        if (!$myTransaction->getPromotion()) {
                            $boost = new Boost();
                            $boost->setFormuleBoost($formuleBoost)
                                ->setMode("Payant")
                                ->setUser($myTransaction->getUser())
                                ->setDateDebut(new DateTime())
                                ->setDateExp(new DateTime("+ ".$formuleBoost->getNbrJour()."days"))
                            ;
                            $this->em->persist($boost);  
                        } else {
                            $promotion = $myTransaction->getPromotion();
                            $promotion->setMode("Payant")
                                ->setDateDebut(new DateTime())
                                ->setDateExp(new DateTime("+ ".$formuleBoost->getNbrJour()."days"))
                                ->setStatus(3)
                            ;
                        }                              
                        $this->em->flush();
                    }
                }

                http_response_code(400);
                exit();
            
                break;
            case 'transaction.canceled':
                // Transaction annulée
                $idTransaction = $event->entity->id;
                $myTransaction = $transactionRepository->findOneBy(['idTransaction' => $idTransaction]);
                $transaction = Transaction::retrieve($idTransaction);
                $myTransaction->setStatus($transaction->status)->isUpdated();
                $this->em->flush();
                break;
            default:
                http_response_code(400);
                exit();
        }

        http_response_code(200);
    }

    #[Route('/listBoost/{uid}/{langUserPhone}', name: 'listBoost', methods: ['POST', "GET"])]
    public function listBoost(User $user, $langUserPhone, BoostRepository $boostRepository, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $sessionDS->set("langUserPhone", $langUserPhone);

        return new JsonResponse($traitementsDS->userBoosts($boostRepository->findBy(['user' => $user])));
    }

    #[Route('/fauxBoostTousUser', name: 'fauxBoostTousUser', methods: ['GET'])]
    public function fauxBoostTousUser(FormuleBoostRepository $formuleBoostRepository, UserRepository $userRepository): Response
    {
        $formulBoost = $formuleBoostRepository->find(1);
        $users = $userRepository->findAll();
        foreach ($users as $user) {
            $boost = new Boost();
            $boost->setFormuleBoost($formulBoost)
                ->setUser($user)
                ->setDateDebut(new DateTime())
                ->setDateExp(new DateTime("+ ".$formulBoost->getNbrJour()."days"))
            ;
            $this->em->persist($boost);
        }
        $this->em->flush();
        return new Response("OK");
    }
}
