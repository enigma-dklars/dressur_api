<?php

namespace App\Controller\API;

use DateTime;
use FedaPay\FedaPay;
use FedaPay\Webhook;
use App\Entity\Boost;
use App\Entity\DSBonusHistorique;
use App\Entity\Promotion;
use FedaPay\Transaction;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\BoostRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Transaction as EntityTransaction;
use App\Entity\User;
use App\Repository\FormulePromoAffaireRepository;
use App\Repository\PromotionRepository;
use App\Repository\UserRepository;
use App\Utilities\SendMail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/api', name: 'api_')]

class PromotionController extends AbstractController
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

    #[Route('/listeFormulePromoAffaire', name: 'listeFormulePromoAffaire', methods: ['POST', 'GET'])]
    public function listeFormulePromoAffaire(TraitementsDS $traitementsDS): Response
    {
        return new JsonResponse([
            'error' => false,
            'listeFormulBoost' => $traitementsDS->listeFormulePromoAffaire(),
        ]);
    }

    #[Route('/newDmdEmploi', name: 'newDmdEmploi', methods: ['POST'])]
    public function newDmdEmploi(Request $request, VerificationsDS $verificationsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository): Response
    {
        $datas = $request->request;

        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $titre_demande_poste_rechercher = $datas->get('titre_demande_poste_rechercher');
        $description_profil_demandeur = $datas->get('description_profil_demandeur');
        $competence_qualification = $datas->get('competence_qualification');
        $niveau_experience = $datas->get('niveau_experience');
        $secteur_activite_rechercher = $datas->get('secteur_activite_rechercher');
        $type_contrat_rechercher = $datas->get('type_contrat_rechercher');
        $localisation_souhaite = $datas->get('localisation_souhaite');
        $salaire_souhaite = $datas->get('salaire_souhaite');
        $langues_parle = $datas->get('langues_parle');
        $lien_portfolio = $datas->get('lien_portfolio');
        $coordonne_demandeur = $datas->get('coordonne_demandeur');

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

        if(!$titre_demande_poste_rechercher || !$description_profil_demandeur || !$competence_qualification|| !$niveau_experience|| !$secteur_activite_rechercher|| !$type_contrat_rechercher|| !$localisation_souhaite|| !$salaire_souhaite|| !$langues_parle|| !$lien_portfolio|| !$coordonne_demandeur){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Please fill in all fields...",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Veuillez svp renseigner tous les champs...",
            ]);
        }

        $promotion = new Promotion();
        $promotion->setUser($user)
            ->setTypePromotionAffaire("dmd_emploi")
            ->setImage("dmd_emploi.jpg")
            ->setAnnotherInfo([
                'titre_demande_poste_rechercher' => $titre_demande_poste_rechercher,
                'description_profil_demandeur' => $description_profil_demandeur,
                'competence_qualification' => $competence_qualification,
                'niveau_experience' => $niveau_experience,
                'secteur_activite_rechercher' => $secteur_activite_rechercher,
                'type_contrat_rechercher' => $type_contrat_rechercher,
                'localisation_souhaite' => $localisation_souhaite,
                'salaire_souhaite' => $salaire_souhaite,
                'langues_parle' => $langues_parle,
                'lien_portfolio' => $lien_portfolio,
                'coordonne_demandeur' => $coordonne_demandeur,
            ])
        ;
        $promotionRepository->save($promotion, true);

        if($sessionDS->get("langUserPhone") != "fr") { 
            return new JsonResponse([
                'error' => false
            ]); 
        }
        return new JsonResponse([
            'error' => false
        ]);
    }
    
    #[Route('/newOffreEmploi', name: 'newOffreEmploi', methods: ['POST'])]
    public function newOffreEmploi(Request $request, VerificationsDS $verificationsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository): Response
    {
        $datas = $request->request;

        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $titre_poste = $datas->get('titre_poste');
        $description_poste = $datas->get('description_poste');
        $competences_requises = $datas->get('competences_requises');
        $type_contrat = $datas->get('type_contrat');
        $lieu_travail = $datas->get('lieu_travail');
        $salaire = $datas->get('salaire');
        $niveau_experience = $datas->get('niveau_experience');
        $horaire_travail = $datas->get('horaire_travail');
        $avantages = $datas->get('avantages');
        $dure_contrat_not_cdi = $datas->get('dure_contrat_not_cdi');
        $contact_emploiyeur = $datas->get('contact_emploiyeur');
        $date_limite_candidature = $datas->get('date_limite_candidature');
        $lien_information_otionel = $datas->get('lien_information_otionel');

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

        if(!$titre_poste || !$description_poste || !$competences_requises|| !$type_contrat|| !$lieu_travail|| !$salaire|| !$niveau_experience|| !$horaire_travail|| !$avantages|| !$dure_contrat_not_cdi|| !$contact_emploiyeur|| !$date_limite_candidature|| !$lien_information_otionel){
            if($sessionDS->get("langUserPhone") != "fr") {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Please fill in all fields...",
                ]);
            }
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Veuillez svp renseigner tous les champs...",
            ]);
        }

        $promotion = new Promotion();
        $promotion->setUser($user)
            ->setTypePromotionAffaire("offre_emploi")
            ->setImage("offre_emploi.png")
            ->setAnnotherInfo([
                'titre_demande_poste_rechercher' => $titre_poste,
                'description_poste' => $description_poste,
                'competences_requises' => $competences_requises,
                'type_contrat' => $type_contrat,
                'lieu_travail' => $lieu_travail,
                'salaire' => $salaire,
                'niveau_experience' => $niveau_experience,
                'horaire_travail' => $horaire_travail,
                'avantages' => $avantages,
                'dure_contrat_not_cdi' => $dure_contrat_not_cdi,
                'contact_emploiyeur' => $contact_emploiyeur,
                'date_limite_candidature' => $date_limite_candidature,
                'lien_information_otionel' => $lien_information_otionel,
            ])
        ;
        $promotionRepository->save($promotion, true);

        if($sessionDS->get("langUserPhone") != "fr") { 
            return new JsonResponse([
                'error' => false
            ]); 
        }
        return new JsonResponse([
            'error' => false
        ]);
    }

    #[Route('/addProduitService', name: 'addProduitService', methods: ['POST'])]
    public function addProduitService(Request $request, VerificationsDS $verificationsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository, FormulePromoAffaireRepository $formulePromoAffaireRepository, TraitementsDS $traitementsDS): Response
    {
        $datas = $request->request;
        $files = $request->files;

        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $idFormulePromoAffaire = $datas->get('idFormulePromoAffaire');
        $uid = $datas->get('uid');
        $text = $datas->get('text');
        $mode = $datas->get('mode');
        $paymentMethod = $datas->get('paymentMethod');
        $tel = $datas->get('tel');

        $image = $files->get('image');

        if ($text === null || $image === null) {
            return new JsonResponse([
                'error' => true,
                'titre' => "Erreur",
                'message' => "Veuillez fournir un texte et une image.",
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

        if($mode == "payant"){
            if ($paymentMethod === null || $tel === null) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => "Erreur",
                    'message' => "Veuillez choisir une methode de paiement et renseigner le numéro du paiement...",
                ]);
            }
        }

        if(!$idFormulePromoAffaire) {
            return new JsonResponse([
                'error' => true,
                'titre' => "Erreur",
                'message' => "Veuillez choisir une formule de promotion affaire.",
            ]);
        } else {
            $formulBoost = $formulePromoAffaireRepository->find($idFormulePromoAffaire);
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

        // Générer un nom de fichier unique
        $fileName = "dressur_pro_".time().'.'.$image->getClientOriginalExtension();

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

        if($mode == "gratuit") {
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
                    'message' => "Votre solde bonus est insuffisant.\nFaite une Promotion Payante ou parrainé des utilisateurs pour augmenté votre solde bonus.",
                ]);
            }

            $user->debitSoldeBonus($formulBoost->getPrix());

            $DSBH = new DSBonusHistorique();
            if($user->getLang() == "fr") {
                $DSBH->setTitre("Promotion Affaire");
            } else {
                $DSBH->setTitre("Business Promotion");
            }
            $DSBH->setUser($user)->setMontant($formulBoost->getPrix() * -1);
            $this->em->persist($DSBH);

            $promotion = new Promotion();
            $promotion
                ->setUser($user)
                ->setFormulePromoAffaire($formulBoost)
                ->setImage($fileName)
                ->setDescription($text)
            ;
            $promotionRepository->save($promotion, true);

            if($sessionDS->get("langUserPhone") != "fr") { 
                return new JsonResponse([
                    'error' => false
                ]); 
            }
            return new JsonResponse([
                'error' => false
            ]);
        } else {
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
                "description" => "Dressur :  Promotion Payante : ". $formulBoost->getTitre() ." - ". $formulBoost->getPrix() ."FCFA : Transaction for ". $user->getPseudo() ." ".$user->getMail(),
                "amount" => $formulBoost->getPrix(),
                "currency" => ["iso" => "XOF"],
                "customer" => [
                    "firstname" => $user->getPseudo(),
                    "lastname" => $user,
                    "email" => $user->getMail(),
                    "phone_number" => [
                        "number" => $tel,
                        "country" => $traitementsDS->getCountryWithMethodePaiement($paymentMethod)
                    ]
                ]
            ];

            try {
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
                        'userId' => $user->getId(),
                        'userUid' => $user->getUid(),
                        'formulePromoAffaire' => $formulBoost->getId(),
                        'image' => $fileName,
                        'description' => $text,
                    ])
                ;
                $this->em->persist($myTransaction);

                $this->em->flush();
                
                $resultat = $traitementsDS->startPaiement($transaction, $paymentMethod);
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

        if($sessionDS->get("langUserPhone") != "fr") { 
            return new JsonResponse([
                'error' => false
            ]); 
        }
        return new JsonResponse([
            'error' => false
        ]);
    }

    #[Route('/editProduitService', name: 'editProduitService', methods: ['POST'])]
    public function editProduitService(Request $request, VerificationsDS $verificationsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository, TraitementsDS $traitementsDS): Response
    {
        $datas = $request->request;
        $files = $request->files;

        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $uid = $datas->get('uid');
        $idPromoAffaire = $datas->get('idPromoAffaire');
        $text = $datas->get('text');
        $image = $files->get('image');

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

        $promotionAffaire = $promotionRepository->find($idPromoAffaire);
        if(!$promotionAffaire) {
            return new JsonResponse([
                'error' => true,
                'titre' => "Erreur",
                'message' => "Promotion introuvable.",
            ]);
        }

        if ($text) {
            $promotionAffaire->setDescription($text)->setStatus(1)->setMotif("");
        }
        
        if ($image) {
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
            $fileName = "dressur_pro_".time().'.'.$image->getClientOriginalExtension();

            // Déplacer l'image vers le dossier de promotion dans le dossier public
            try {
                $image->move($this->getParameter('promotion_directory'), $fileName);
                $promotionAffaire->setImage($fileName)->setStatus(1)->setMotif("");
            } catch (FileException $e) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => $e->getMessage(),
                ]);
            }
        }

        $this->em->flush();

        if($sessionDS->get("langUserPhone") != "fr") { 
            return new JsonResponse([
                'error' => false
            ]); 
        }
        return new JsonResponse([
            'error' => false
        ]);
    }

    #[Route('/newPromo', name: 'newPromo', methods: ['POST'])]
    public function newPromo(Request $request, FormulePromoAffaireRepository $formulePromoAffaireRepository, UserRepository $userRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository): Response
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

        $formulBoost = $formulePromoAffaireRepository->find($idFormulBoost);
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
                'message' => "Votre solde bonus est insuffisant.\nFaite une Promotion Payante ou parrainé des utilisateurs pour augmenté votre solde bonus.",
            ]);
        }
        
        $user->debitSoldeBonus($formulBoost->getPrix());

        $DSBH = new DSBonusHistorique();
        if($user->getLang() == "fr") {
            $DSBH->setTitre("Promotion Affaire");
        } else {
            $DSBH->setTitre("Business Promotion");
        }
        $DSBH->setUser($user)->setMontant($formulBoost->getPrix() * -1);
        $this->em->persist($DSBH);

        $promotion = $promotionRepository->find($idPromotion);

        if($promotion->getStatus() == 2 || $promotion->getStatus() == 4) {
            $promotion->setFormulePromoAffaire($formulBoost)
                ->setDateDebut(new DateTime())
                ->setDateExp(new DateTime("+ ".$formulBoost->getNbrJour()."days"))
                ->setReferencement($formulBoost->getReferencement())
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
    public function newPromoPayant(Request $request, FormulePromoAffaireRepository $formulePromoAffaireRepository, BoostRepository $boostRepository, VerificationsDS $verificationsDS, SessionDS $sessionDS, PromotionRepository $promotionRepository, TraitementsDS $traitementsDS): Response
    {
        $datas = $request->request;        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);
        $uid = $datas->get('uid');

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

        $idPromotion = $datas->get('idPromotion');
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

        $formulBoost = $formulePromoAffaireRepository->find($idFormulBoost);
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

        $array_create_transaction = [
            "description" => "Dressur :  Promotion Payante : ". $formulBoost->getTitre() ." - ". $formulBoost->getPrix() ."FCFA : Transaction for ". $user->getPseudo() ." ".$user->getMail(),
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

        $promotion = $promotionRepository->find($idPromotion);

        if($promotion->getStatus() == 2 || $promotion->getStatus() == 4) {
            $promotion->setFormulePromoAffaire($formulBoost);
            
            try {
                $transaction = Transaction::create($array_create_transaction);
    
                $myTransaction  = new EntityTransaction();
                $myTransaction
                    ->setUser($user)
                    ->setTransactionFor("re_boost_affaire")
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
                        'promotionId' => $promotion->getId(),
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

    #[Route('/listPromotion/{uid}/{langUserPhone}', name: 'listPromotion', methods: ['POST', "GET"])]
    public function listPromotion(User $user, $langUserPhone, TraitementsDS $traitementsDS, SessionDS $sessionDS): Response
    {
        $sessionDS->set("langUserPhone", $langUserPhone);
        
        return new JsonResponse($traitementsDS->userPromos($user->getPromotions()));
    }

    #[Route('/setPromotionToWatch/{id}/{uid}', name: 'setPromotionToWatch', methods: ['POST', "GET"])]
    public function setPromotionToWatch(Promotion $promotion, $uid, UserRepository $userRepository): Response
    {
        $user = $userRepository->findOneBy(['uid' => $uid]);
        $promotion->setToWatch($user, "vue");
        $this->em->flush();        
        return new Response("OK");
    }
}
