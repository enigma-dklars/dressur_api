<?php

namespace App\Controller\API;

use App\Entity\User;
use FedaPay\FedaPay;
use FedaPay\Transaction;
use App\Services\SessionDS;
use App\Entity\CampagneMail;
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
use App\Repository\CampagneMailRepository;
use App\Repository\FormuleCampagneMailRepository;
use App\Utilities\SendMail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]

class CampagneMailController extends AbstractController
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

    #[Route('/listeFormuleCampagneMail', name: 'listeFormuleCampagneMail', methods: ['POST', 'GET'])]
    public function listeFormuleCampagneMail(FormuleCampagneMailRepository $formuleCampagneMailRepository): Response
    {
        $listeFormuleCampagneMail = [];
        foreach ($formuleCampagneMailRepository->findAll() as $boost) {
            array_push($listeFormuleCampagneMail, [
                "id" => $boost->getId(),
                "value" => $boost->getId(),
                "label" => $boost->getTitre(),
                "prix" => $boost->getPrix(),
                "nombre_mail" => $boost->getNombreMail(),
            ]);
        }
        return new JsonResponse([
            'error' => false,
            'listeFormuleCampagneMail' => $listeFormuleCampagneMail,
        ]);
    }

    #[Route('/newCampagneMail', name: 'newCampagneMail', methods: ['POST'])]
    public function newCampagneMail(Request $request, VerificationsDS $verificationsDS, SessionDS $sessionDS, FormuleCampagneMailRepository $formuleCampagneMailRepository): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $idFormuleCampagneMail = $datas->get('idFormuleCampagneMail');
        $titre = $datas->get('titre');
        $sujet = $datas->get('sujet');
        $replyto = $datas->get('replyto');
        $sendto = $datas->get('sendto');
        $contentmail = $datas->get('contentmail');

        $formuleCampagneMail = $formuleCampagneMailRepository->find($idFormuleCampagneMail);
        if(!$formuleCampagneMail){
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

        // compter le nombre d'adresse mail qui doivent recevoir la campagne
        $nbr_sendto = count(explode(",", str_replace(" ", "", $sendto)));
        if(!($nbr_sendto <= $formuleCampagneMail->getNombreMail())) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'With this email campaign formula, you cannot exceed '.$formuleCampagneMail->getNombreMail().' recipient email addresses.',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Avec cette formule de campagne mail, vous ne pouvez pas dépasser '.$formuleCampagneMail->getNombreMail().' adresses mail destinataire.',
            ]);
        }

        if(!$titre or !$sujet or !$replyto or !$sendto or !$contentmail) {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Please complete all fields!',
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez bien remplir tous les champs!',
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

        $campagneMail  = new CampagneMail();
        $campagneMail->setUser($user)
            ->setTitre($titre)
            ->setSujet($sujet)
            ->setReplyto($replyto)
            ->setSendto($sendto)
            ->setContentmail($contentmail)
            ->setFormuleCampagneMail($formuleCampagneMail)
        ;
        $this->em->persist($campagneMail);
        $this->em->flush();

        return new JsonResponse([
            'error' => false,
        ]);
    }

    #[Route('/listCampagneMail/{uid}/{langUserPhone}', name: 'listCampagneMail', methods: ['POST', "GET"])]
    public function listCampagneMail(User $user, $langUserPhone, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $sessionDS->set("langUserPhone", $langUserPhone);
        
        return new JsonResponse($traitementsDS->userCampagneMail($user->getCampagneMails()));
    }

    #[Route('/newCampageMailPayant/paiement', name: 'newCampageMailPayant', methods: ['POST'])]
    public function newCampageMailPayant(Request $request, FormuleBoostRepository $formuleBoostRepository, BoostRepository $boostRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository, CampagneMailRepository $campagneMailRepository, TraitementsDS $traitementsDS): Response
    {
        FedaPay::setApiKey("sk_live_4Q00INMNKwiJcdt17fNJyOUo");
        FedaPay::setEnvironment('live');

        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $idCampagneMail = $datas->get('idCampagneMail');
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

        $campagneMail = $campagneMailRepository->find($idCampagneMail);
        if(!$campagneMail){
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

        if($valueMethodePaiement == "mtn") {
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Mistake!',
                    'message' => "Payments by MTN from the application are currently experiencing disruptions. Please use Moov or Celtis for your payments if possible. If this is not possible, contact Dressur support by WhatsApp Please. THANKS.",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => "Les paiements par MTN depuis l'application rencontre en ce moment des perturbations. Veuillez si possible utiliser Moov ou Celtis pour vos paiements. Si ce n'est pas possible, contactez l'assistance Dressur par WhatsApp Svp. Merci.",
            ]);
        }

        $array_create_transaction = [
            "description" => "Dressur :  Promotion Payante : ". $campagneMail->getTitre() ." - ". $campagneMail->getFormuleCampagneMail()->getPrix() ."FCFA : Transaction for ". $user->getPseudo() ." ".$user->getMail(),
            "amount" => $campagneMail->getFormuleCampagneMail()->getPrix(),
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


        if($campagneMail->getStatus() == 2){
            
            $transaction = Transaction::create($array_create_transaction);

            $myTransaction  = new EntityTransaction();
            $myTransaction
                ->setUser($user)
                ->setTransactionFor("campagne_mail")
                ->setIdTransaction($transaction["id"])
                ->setReference($transaction["reference"])
                ->setAmount($transaction["amount"])
                ->setStatus($transaction["status"])
                ->setCustomerId($transaction["customer_id"])
                ->setCurrencyId($transaction["currency_id"])
                ->setAnnotherInfo([
                    'idCampagneMail' => $idCampagneMail
                ])
            ;
            $this->em->persist($myTransaction);

            $this->em->flush();

            try {
                $token = $transaction->generateToken()->token;
                $mode = $valueMethodePaiement;
                $transaction->sendNowWithToken($mode, $token);
            } catch (\Throwable $th) {
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
}