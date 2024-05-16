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
use App\Repository\CampagneMailRepository;
use App\Repository\PromotionRepository;
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
                "value" => $boost->getId(),
                "label" => $boost->getTitre(),
                "prix" => intval($boost->getPrix()),
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

        $boost = new Boost();
        $boost->setFormuleBoost($formulBoost)
              ->setUser($user)
        ;
        if ($verificationsDS->siBoostEnCours($boostRepository->findBy(['user' => $user]))) {            
            $lastBoostDateExp = ($boostRepository->findOneBy(['user' => $user], ["id" => "DESC"]))->getDateExp();
            $boost->setDateDebut($lastBoostDateExp)
                ->setDateExp(new DateTime(date('d-m-Y H:i', strtotime("+ ".$formulBoost->getNbrJour()."days ".$lastBoostDateExp->format('d-m-Y H:i')))))
            ;
            $message = ($langUserPhone == 'fr') ? "Votre boost contact a été programmé." : "Your contact boost has been programmed.";
        } else {
            $boost->setDateDebut(new DateTime())
                ->setDateExp(new DateTime("+ ".$formulBoost->getNbrJour()."days"))
            ;
            $message = ($langUserPhone == 'fr') ? "Votre boost contact a démarré." : "Your contact boost has started.";
        }
        $this->em->persist($boost);
        $this->em->flush();

        return new JsonResponse([
            'error' => false,
            'message' => $message,
        ]);
    }

    #[Route('/newBoostPayant', name: 'newBoostPayant', methods: ['POST'])]
    public function newBoostPayant(Request $request, FormuleBoostRepository $formuleBoostRepository, BoostRepository $boostRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS, TraitementsDS $traitementsDS): Response
    {
        FedaPay::setApiKey("sk_live_4Q00INMNKwiJcdt17fNJyOUo");
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

        $array_create_transaction = [
            "description" => "Dressur :  Boost Payant : ". $formulBoost->getTitre() ." - ". $formulBoost->getPrix() ."FCFA : Transaction for ". $user->getPseudo() ." ".$user->getMail(),
            "amount" => $formulBoost->getPrix(),
            "currency" => ["iso" => "XOF"],
            "customer" => [
                "firstname" => $user->getPseudo(),
                "lastname" => $user,
                "email" => $user->getMail(),
                "phone_number" => [
                    "number" => $tel,
                    "country" => $traitementsDS->getCountryWithMethodePaiement($valueMethodePaiement)
                ]
            ]
        ];

        $transaction = Transaction::create($array_create_transaction);

        $myTransaction  = new EntityTransaction();
        $myTransaction
            ->setUser($user)
            ->setTransactionFor("boost_contact")
            ->setIdTransaction($transaction["id"])
            ->setReference($transaction["reference"])
            ->setAmount($transaction["amount"])
            ->setStatus($transaction["status"])
            ->setCustomerId($transaction["customer_id"])
            ->setCurrencyId($transaction["currency_id"])
            ->setAnnotherInfo([
                'formulBoostId' => $formulBoost->getId(),
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
