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
    public function listeFormuleBoost(Request $request, TraitementsDS $traitementsDS): Response
    {
        // Si le client ne précise pas typeBoost (vieilles versions mobile),
        // on renvoie uniquement les formules 'date' payantes (backward-compat).
        $typeBoost = $request->request->get('typeBoost') ?? $request->query->get('typeBoost') ?? 'date';
        return new JsonResponse([
            'error' => false,
            'listeFormulBoost' => $traitementsDS->listeFormulBoost($typeBoost),
            'listeMethodePaiements' => $traitementsDS->listeMethodePaiements(),
        ]);
    }

    #[Route('/freeBoostInfo', name: 'freeBoostInfo', methods: ['POST', 'GET'])]
    public function freeBoostInfo(FormuleBoostRepository $formuleBoostRepository): Response
    {
        $dateFormule  = null;
        $quotaFormule = null;
        foreach ($formuleBoostRepository->findAll() as $formule) {
            if ($formule->isActivated() && intval($formule->getPrix()) === 0) {
                if ($formule->getTypeBoost() === 'date' && $dateFormule === null) {
                    $dateFormule = [
                        'nbrJour' => $formule->getNbrJour(),
                        'titre'   => $formule->getTitre(),
                    ];
                }
                if ($formule->getTypeBoost() === 'quota' && $quotaFormule === null) {
                    $quotaFormule = [
                        'nbContactsMax' => $formule->getNbContactsMax(),
                        'titre'         => $formule->getTitre(),
                    ];
                }
            }
        }
        return new JsonResponse([
            'error' => false,
            'date'  => $dateFormule  ?? ['nbrJour' => 5,  'titre' => 'Boost Gratuit'],
            'quota' => $quotaFormule ?? ['nbContactsMax' => 20, 'titre' => 'Boost Gratuit'],
        ]);
    }

    #[Route('/newBoost', name: 'newBoost', methods: ['POST'])]
    public function newBoost(Request $request, FormuleBoostRepository $formuleBoostRepository, UserRepository $userRepository, BoostRepository $boostRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS, DeletedDSRepository $deletedDSRepository): Response
    {
        $datas = $request->request;
        
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
            $message = "Ce numéro de téléphone ou cette adresse e-mail a déjà été associé(e) à un compte supprimé. Pour continuer, vous devez obligatoirement activer un Boost Contact Payant d’un montant minimum de 500 F.";
        } else if ($verificationsDS->siBoostEnCours($boostRepository->findBy(['user' => $user]))) {
            $error = true;
            $message = "Vous avez déjà un Boost Contact en cours. Il n'est pas possible de programmer un Boost Contact Gratuit.";
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
                $message = "Votre Boost Contact Gratuit limité à ".$formuleQuotaGratuit->getNbContactsMax()." contact(s) a démarré.";
            } else {
                $formuleDateGratuit = $formuleBoostRepository->findOneBy(['typeBoost' => 'date', 'prix' => 0, 'activated' => true]);
                $boost->setFormuleBoost($formuleDateGratuit)
                    ->setUser($user)
                    ->setSource($source)
                    ->setTypeBoost('date')
                    ->setDateDebut(new DateTime())
                    ->setDateExp(new DateTime("+ ".$formuleDateGratuit->getNbrJour()."days"))
                ;
                $error = false;
                $message = "Votre Boost Contact Gratuit de ".$formuleDateGratuit->getNbrJour()." jour(s) à démarrer.";
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
                $message = "Votre Boost Contact Gratuit limité à ".$formuleQuotaGratuit->getNbContactsMax()." contact(s) a démarré.";
            } else {
                $formuleDateGratuit = $formuleBoostRepository->findOneBy(['typeBoost' => 'date', 'prix' => 0, 'activated' => true]);
                $boost->setFormuleBoost($formuleDateGratuit)
                    ->setUser($user)
                    ->setSource($source)
                    ->setTypeBoost('date')
                    ->setDateDebut(new DateTime())
                    ->setDateExp(new DateTime("+ ".$formuleDateGratuit->getNbrJour()."days"))
                ;
                $error = false;
                $message = "Votre Boost Contact Gratuit de ".$formuleDateGratuit->getNbrJour()." jour(s) à démarrer.";
            }
            $this->em->persist($boost);
            $this->em->flush();
        } else {
            $error = true;
            $message = "Demande de Boost Contact Gratuit refusé. Votre précédent Boost Contact est en mode Gratuit. Vous devez donc faire un Boost Contact Payant avant de pouvoir demander un autre Boost Contact Gratuit.";
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
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Votre numéro WhatsApp na pas encore été confirmer. S'il s'agit d'une erreur, contactez-nous sur WhatsApp.",
            ]);
        }

        $formulBoost = $formuleBoostRepository->find($idFormulBoost);
        if(!$formulBoost){
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
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Ce numéro de téléphone ou cette adresse e-mail a déjà été associé(e) à un compte supprimé. Pour continuer, vous devez obligatoirement activer un Boost Contact Payant d’un montant minimum de 500 F."]);
            }
        }

        $verificationNumTel = $verificationsDS->verifFormatNumTel($tel);
        if($verificationNumTel["error"] == true){
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix."]);
        }
        $tel = $verificationNumTel["e164"];

        if(!$tel){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez saisir un numéro de téléphone.',
            ]);
        }

        if(!$valueMethodePaiement) {
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
                        'typeBoost' => $formulBoost->getTypeBoost(),
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
    
                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Excusez-nous svp!',
                        'message' => "Veuillez soumettre une nouvelle fois le formulaire. Merci.",
                    ]);
                }
    
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
                    $formulBoost->getPrix(),
                    $tel,
                    $user->getPseudo(),
                    $user->getMail(),
                    "boost_contact",
                    [
                        'userId' => $user->getId(),
                        'userUid' => $user->getUid(),
                        'formulBoostId' => $formulBoost->getId(),
                        'typeBoost' => $formulBoost->getTypeBoost(),
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

                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Excusez-nous svp!',
                        'message' => "Veuillez soumettre une nouvelle fois le formulaire. Merci.",
                    ]);
                }

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

    #[Route('/listBoost/{uid}/{langUserPhone}', name: 'listBoost', methods: ['POST', "GET"])]
    public function listBoost(User $user, BoostRepository $boostRepository, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        return new JsonResponse($traitementsDS->userBoosts($boostRepository->findBy(['user' => $user])));
    }
}
