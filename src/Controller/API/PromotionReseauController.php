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
use App\Repository\PromoReseauRepository;
use App\Repository\PromotionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/api', name: 'api_')]

class PromotionReseauController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1);
    }


    #[Route('/listeFormulePromoReseau', name: 'listeFormulePromoReseau', methods: ['POST', 'GET'])]
    public function listeFormulePromoReseau(Request $request, SessionDS $sessionDS, FormulePromoReseauRepository $formulePromoReseauRepository): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $listeFormulePromoReseau = [];
        foreach ($formulePromoReseauRepository->findBy(['parent' => NULL, 'available' => true]) as $formule) {
            $lesFormulesFils = [];
            foreach ($formulePromoReseauRepository->findBy(['parent' => $formule, 'available' => true]) as $formuleFils) {
                $prix_service_fcfa = $formuleFils->getPrix() * 1.2 * 1.3 * 700;
                $prix_service_fcfa = round($prix_service_fcfa) + 1;
                if($langUserPhone == 'fr') {
                    $description_service = "💰 ".$formuleFils->getQte()." ".$formuleFils->getTitre()." pour ".$prix_service_fcfa." FCFA\n\nQuantité Min : ".$formuleFils->getQteMin()." - Max : ".$formuleFils->getQteMax()."\n\n".$formuleFils->getDescription();
                } else {
                    $description_service = "💰 ".$formuleFils->getQte()." ".$formuleFils->getTitre()." for ".$prix_service_fcfa." FCFA\n\nQuantity Min : ".$formuleFils->getQteMin()." - Max : ".$formuleFils->getQteMax()."\n\n".$formuleFils->getDescriptionEn();
                }
                array_push($lesFormulesFils, [
                    "value" => $formuleFils->getId(),
                    "label" => $formuleFils->getTitre(),
                    "id" => $formuleFils->getId(),
                    "titre" => $formuleFils->getTitre(),
                    "prix" => $prix_service_fcfa,
                    "qte" => $formuleFils->getQte(),
                    "qteMin" => $formuleFils->getQteMin(),
                    "qteMax" => $formuleFils->getQteMax(),
                    "description" => $description_service,
                ]);
            }

            array_push($listeFormulePromoReseau, [
                "id" => $formule->getId(),
                "titre" => $formule->getTitre(),
                "iconFlutterName" => $formule->getIconFlutterName(),
                "lesFormulesFils" => $lesFormulesFils,
            ]);
        }
        return new JsonResponse([
            'error' => false,
            'listeFormulePromoReseau' => $listeFormulePromoReseau,
        ]);
    }

    #[Route('/listPromoReseau/{uid}/{langUserPhone}', name: 'listPromoReseau', methods: ['POST', "GET"])]
    public function listPromoReseau(User $user, $langUserPhone, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $sessionDS->set("langUserPhone", $langUserPhone);
        
        return new JsonResponse($traitementsDS->userPromoReseaus($user->getPromoReseaus()));
    }

    #[Route('/newPromoReseau', name: 'newPromoReseau', methods: ['POST', 'GET'])]
    public function newPromoReseau(Request $request, SessionDS $sessionDS, FormulePromoReseauRepository $formulePromoReseauRepository, VerificationsDS $verificationsDS, UserRepository $userRepository, TraitementsDS $traitementsDS, PromoReseauRepository $promoReseauRepository): Response
    {
        FedaPay::setApiKey("sk_live_4Q00INMNKwiJcdt17fNJyOUo");
        FedaPay::setEnvironment('live');

        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);
        
        $uid = $datas->get('uid');
        $idFormulePromoReseau = $datas->get('idFormulePromoReseau');
        $qteDemander = $datas->get('qteDemander');
        $prixQteDemander = $datas->get('prixQteDemander');
        $lien = $datas->get('lien');
        $valueMethodePaiement = $datas->get('valueMethodePaiement');
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
                return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Please enter a valid phone number preceded by its prefix Exp(+229 62005500)."]);
            }
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix Exp(+229 62005500)."]);
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
                'idFormulePromoReseau' => $idFormulePromoReseau,
                'qteDemander' => $qteDemander,
                'prixQteDemander' => $prixQteDemander,
                'lien' => $lien,
                'tel' => $tel,
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
}
