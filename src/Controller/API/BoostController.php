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
use App\Repository\DeletedDSRepository;
use App\Repository\MethodePaiementRepository;
use App\Repository\PromotionRepository;
use App\Services\CookieDS;
use App\Utilities\SendMail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/api', name: 'api_')]

class BoostController extends AbstractController
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

    #[Route('/listeFormuleBoost', name: 'listeFormuleBoost', methods: ['POST', 'GET'])]
    public function listeFormuleBoost(TraitementsDS $traitementsDS): Response
    {
        return new JsonResponse([
            'error' => false,
            'listeFormulBoost' => $traitementsDS->listeFormulBoost(),
            'listeMethodePaiements' => $traitementsDS->listeMethodePaiements(),
        ]);
    }

    #[Route('/newBoost', name: 'newBoost', methods: ['POST'])]
    public function newBoost(Request $request, FormuleBoostRepository $formuleBoostRepository, UserRepository $userRepository, BoostRepository $boostRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS, DeletedDSRepository $deletedDSRepository): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;

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

        $source = ($request->request->get('source') === 'web') ? 'web' : 'mobile';
        $typeBoost = ($request->request->get('typeBoost') === 'quota') ? 'quota' : 'date';

        $error = false;
        $lastBoostContact = $boostRepository->findOneBy(['user' => $user], ['id' => 'DESC']);
        
        if (
            count($boostRepository->findBy(['user' => $user])) === 0
            &&
            (
                count($deletedDSRepository->findBy(['tel' => $user->getTel()])) >= 1
                ||
                count($deletedDSRepository->findBy(['mail' => $user->getMail()])) >= 1
            )
        ) {
            $error = true;
            $message = ($langUserPhone == 'fr') ? "Ce numéro de téléphone ou cette adresse e-mail a déjà été associé(e) à un compte supprimé. Pour continuer, vous devez obligatoirement activer un Boost Contact Payant d’un montant minimum de 500 F." : "This phone number or email address was previously linked to a deleted account. To proceed, you are required to activate a paid Boost Contact with a minimum amount of 500 F.";
        } else if ($verificationsDS->siBoostEnCours($boostRepository->findBy(['user' => $user]))) {
            $error = true;
            $message = ($langUserPhone == 'fr') ? "Vous avez déjà un Boost Contact en cours. Il n'est pas possible de programmer un Boost Contact Gratuit." : "You already have a Contact Boost active. It is not possible to schedule another Free Contact Boost.";
        } else if(!$lastBoostContact) {
            $boost = new Boost();
            if ($typeBoost === 'quota') {
                $formuleQuotaGratuit = $formuleBoostRepository->findOneBy(['typeBoost' => 'quota', 'prix' => 0, 'activated' => true]);
                $boost->setFormuleBoost($formuleQuotaGratuit)
                    ->setUser($user)
                    ->setSource($source)
                    ->setTypeBoost('quota')
                    ->setDateDebut(new DateTime())
                ;
                $error = false;
                $message = ($langUserPhone == 'fr') ? "Votre Boost Contact Gratuit limité à 20 contacts a démarré." : "Your free Boost Contact limited to 20 contacts has started.";
            } else {
                $boost->setFormuleBoost($formuleBoostRepository->find(7))
                    ->setUser($user)
                    ->setSource($source)
                    ->setTypeBoost('date')
                    ->setDateDebut(new DateTime())
                    ->setDateExp(new DateTime("+ 5days"))
                ;
                $error = false;
                $message = ($langUserPhone == 'fr') ? "Votre Boost Contact Gratuit de cinq (05) jours à démarrer." : "Your free five (05) day Boost Contact trial is about to begin.";
            }
            $this->em->persist($boost);
            $this->em->flush();
        } else if($lastBoostContact->getMode() == "Payant") {
            $boost = new Boost();
            if ($typeBoost === 'quota') {
                $formuleQuotaGratuit = $formuleBoostRepository->findOneBy(['typeBoost' => 'quota', 'prix' => 0, 'activated' => true]);
                $boost->setFormuleBoost($formuleQuotaGratuit)
                    ->setUser($user)
                    ->setSource($source)
                    ->setTypeBoost('quota')
                    ->setDateDebut(new DateTime())
                ;
                $error = false;
                $message = ($langUserPhone == 'fr') ? "Votre Boost Contact Gratuit limité à 20 contacts a démarré." : "Your free Boost Contact limited to 20 contacts has started.";
            } else {
                $boost->setFormuleBoost($formuleBoostRepository->find(7))
                    ->setUser($user)
                    ->setSource($source)
                    ->setTypeBoost('date')
                    ->setDateDebut(new DateTime())
                    ->setDateExp(new DateTime("+ 5days"))
                ;
                $error = false;
                $message = ($langUserPhone == 'fr') ? "Votre Boost Contact Gratuit de cinq (05) jours à démarrer." : "Your free five (05) day Boost Contact trial is about to begin.";
            }
            $this->em->persist($boost);
            $this->em->flush();
        } else {
            $error = true;
            $message = ($langUserPhone == 'fr') ? "Demande de Boost Contact Gratuit refusé. Votre précédent Boost Contact est en mode Gratuit. Vous devez donc faire un Boost Contact Payant avant de pouvoir demander un autre Boost Contact Gratuit." : "Request for a free Contact Boost denied. Your previous Contact Boost was in Free mode. You must therefore complete a Paid Contact Boost before you can request another Free Contact Boost.";
        }

        return new JsonResponse([
            'error' => $error,
            'message' => $message,
        ]);
    }

    #[Route('/newBoostPayant', name: 'newBoostPayant', methods: ['POST'])]
    public function newBoostPayant(Request $request, FormuleBoostRepository $formuleBoostRepository, BoostRepository $boostRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS, TraitementsDS $traitementsDS, MethodePaiementRepository $methodePaiementRepository, DeletedDSRepository $deletedDSRepository): Response
    {
        $datas = $request->request;        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);
        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;

        $idFormulBoost = $datas->get('idFormulBoost');
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

        if (
            count($boostRepository->findBy(['user' => $user])) === 0
            &&
            (
                count($deletedDSRepository->findBy(['tel' => $user->getTel()])) >= 1
                ||
                count($deletedDSRepository->findBy(['mail' => $user->getMail()])) >= 1
            )
        ) {
            if($formulBoost->getPrix() < 500) {
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => ($langUserPhone == 'fr') ? "Ce numéro de téléphone ou cette adresse e-mail a déjà été associé(e) à un compte supprimé. Pour continuer, vous devez obligatoirement activer un Boost Contact Payant d’un montant minimum de 500 F." : "This phone number or email address was previously linked to a deleted account. To proceed, you are required to activate a paid Boost Contact with a minimum amount of 500 F."]);
            }
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

        if(!$valueMethodePaiement) {
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
                "description" => "Dressur :  Boost Payant : ". $formulBoost->getTitre() ." - ". $formulBoost->getPrix() ."FCFA : Transaction for ". $user->getPseudo() ." ".$user->getMail(),
                "amount" => $formulBoost->getPrix(),
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
                    ->setTransactionFor("boost_contact")
                    ->setIdTransaction($transaction["id"])
                    ->setReference($transaction["reference"])
                    ->setAmount($transaction["amount"])
                    ->setStatus($transaction["status"])
                    ->setCustomerId($transaction["customer_id"])
                    ->setCurrencyId($transaction["currency_id"])
                    ->setAnnotherInfo([
                        'userId' => $user->getId(),
                        'userUid' => $user->getUid(),
                        'formulBoostId' => $formulBoost->getId(),
                        'source' => ($datas->get('source') === 'web') ? 'web' : 'mobile',
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
                    $envPaiementApi->setActivated(false);
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
                    $formulBoost->getPrix(),
                    $tel,
                    $user->getPseudo(),
                    $user->getMail(),
                    "boost_contact",
                    [
                        'userId' => $user->getId(),
                        'userUid' => $user->getUid(),
                        'formulBoostId' => $formulBoost->getId(),
                    ],
                    $user
                );
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $msgError = (string)$th;
                if (strpos($msgError, "Vous avez excédé le nombre de transactions hebdomadaire requis. 10 transactions approuvées sont autorisées par semaine.") !== false) {
                    $envPaiementApi->setCountTransactionApproved(10);
                    $envPaiementApi->setActivated(false);
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
        }

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
}
