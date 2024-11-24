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
use App\Repository\MethodePaiementRepository;
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
    public function listeFormuleCampagneMail(FormuleCampagneMailRepository $formuleCampagneMailRepository, TraitementsDS $traitementsDS): Response
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
            'listeMethodePaiements' => $traitementsDS->listeMethodePaiements(),
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
    public function newCampageMailPayant(Request $request, FormuleBoostRepository $formuleBoostRepository, BoostRepository $boostRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository, CampagneMailRepository $campagneMailRepository, TraitementsDS $traitementsDS, MethodePaiementRepository $methodePaiementRepository): Response
    {
        $datas = $request->request;        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);
        $uid = $datas->get('uid');

        $idCampagneMail = $datas->get('idCampagneMail');
        $valueMethodePaiement = $datas->get('valueMethodePaiement'); // mon_argent
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

        if($campagneMail->getFormuleCampagneMail()->getPrix() > 20000){
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

        $verificationNumTel = $verificationsDS->verifFormatNumTel($tel);
        if($verificationNumTel["error"] == true){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Please enter a valid phone number preceded by its prefix."]);
            }
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix."]);
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
        if($campagneMail->getStatus() == 2) {
            if($methodePaiementEntity->getAggregator() == "FedaPay"){
                $envPaiementApi = $traitementsDS->getEnvPaiementApiFedaPayDisponible();
                if(!$envPaiementApi) {
                    $this->sendMail->sendReport("uUid : ".$uid, "Aucun Webhook Disponible pour FedaPay");
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
                    "description" => "Dressur :  Promotion Payante : ". $campagneMail->getTitre() ." - ". $campagneMail->getFormuleCampagneMail()->getPrix() ."FCFA : Transaction for ". $user->getPseudo() ." ".$user->getMail(),
                    "amount" => $campagneMail->getFormuleCampagneMail()->getPrix(),
                    "currency" => ["iso" => "XOF"],
                    "customer" => [
                        "firstname" => $user->getPseudo(),
                        "lastname" => $user,
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
                        ->setTransactionFor("campagne_mail")
                        ->setIdTransaction($transaction["id"])
                        ->setReference($transaction["reference"])
                        ->setAmount($transaction["amount"])
                        ->setStatus($transaction["status"])
                        ->setCustomerId($transaction["customer_id"])
                        ->setCurrencyId($transaction["currency_id"])
                        ->setAnnotherInfo([
                            'userId' => $user->getId(),
                            'userUid' => $user->getUid(),
                            'idCampagneMail' => $idCampagneMail,
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
            } else {
                // logique fait de paiement FeexPay
                $envPaiementApi = $traitementsDS->getEnvPaiementApiFeexPayDisponible();
                if(!$envPaiementApi) {
                    $this->sendMail->sendReport("uUid : ".$uid, "Aucun Webhook Disponible pour FeexPay");
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
        
                try {
                    $resultat = $traitementsDS->startPaiementFeexPay(
                        $envPaiementApi, 
                        $methodePaiementEntity, 
                        $campagneMail->getFormuleCampagneMail()->getPrix(),
                        $tel,
                        $user->getPseudo(),
                        $user->getMail(),
                        "campagne_mail",
                        [
                            'userId' => $user->getId(),
                            'userUid' => $user->getUid(),
                            'idCampagneMail' => $idCampagneMail,
                        ],
                        $user
                    );
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

                return new JsonResponse([
                    'error' => false,
                ]);
            }
        }

        if($sessionDS->get("langUserPhone") != "fr") {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Whoops!',
                'message' => "This campaign has already been started.",
            ]);                
        }
        return new JsonResponse([
            'error' => true,
            'titre' => 'Oups!',
            'message' => "Cette campagne est déjà été démarrée.",
        ]);
    }
}