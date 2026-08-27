<?php

namespace App\Controller\API;

use DateTime;
use FedaPay\FedaPay;
use FedaPay\Webhook;
use App\Entity\Boost;
use App\Entity\Promotion;
use FedaPay\Transaction;
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
use App\Repository\MethodePaiementRepository;
use App\Repository\PromotionRepository;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\PromotionBilling;
use App\Services\PromotionImageValidator;
use App\Services\ProgrammeRecompenseBudget;
use App\Utilities\SendMail;
use App\Utilities\UuidGenerator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/api', name: 'api_')]

class PromotionController extends AbstractController
{
    private $em;
    private $env;
    private $sendMail;
    private $cookieDS;
    private ProgrammeRecompenseBudget $programmeRecompenseBudget;
    private PromotionBilling $promotionBilling;
    private PromotionImageValidator $promotionImageValidator;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        EnvRepository $env,
        SendMail $sendMail,
        CookieDS $cookieDS,
        ProgrammeRecompenseBudget $programmeRecompenseBudget,
        PromotionBilling $promotionBilling,
        PromotionImageValidator $promotionImageValidator,
        LoggerInterface $logger
    )
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->sendMail = $sendMail;
        $this->cookieDS = $cookieDS;
        $this->programmeRecompenseBudget = $programmeRecompenseBudget;
        $this->promotionBilling = $promotionBilling;
        $this->promotionImageValidator = $promotionImageValidator;
        $this->logger = $logger;
    }

    #[Route('/listeFormulePromoAffaire', name: 'listeFormulePromoAffaire', methods: ['POST', 'GET'])]
    public function listeFormulePromoAffaire(TraitementsDS $traitementsDS): Response
    {
        return new JsonResponse([
            'error' => false,
            'listeFormulBoost' => $traitementsDS->listeFormulePromoAffaire(),
            'listeMethodePaiements' => $traitementsDS->listeMethodePaiements(),
        ]);
    }

    #[Route('/newDmdEmploi', name: 'newDmdEmploi', methods: ['POST'])]
    public function newDmdEmploi(Request $request, VerificationsDS $verificationsDS, PromotionRepository $promotionRepository): Response
    {
        return new JsonResponse([
            'error' => true,
            'titre' => 'Oups!',
            'message' => "Le service de publication de demande d'emploi est temporairement indisponible. Merci pour votre compréhension.",
        ]);

        $datas = $request->request;

                

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
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
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Votre numéro WhatsApp na pas encore été confirmer. S'il s'agit d'une erreur, contactez-nous sur WhatsApp.",
            ]);
        }

        if(!$titre_demande_poste_rechercher || !$description_profil_demandeur || !$competence_qualification|| !$niveau_experience|| !$secteur_activite_rechercher|| !$type_contrat_rechercher|| !$localisation_souhaite|| !$salaire_souhaite|| !$langues_parle|| !$lien_portfolio|| !$coordonne_demandeur){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Veuillez svp renseigner tous les champs...",
            ]);
        }

        $source = ($request->request->get('source') === 'web') ? 'web' : 'mobile';

        $promotion = new Promotion();
        $promotion->setUser($user)
            ->setSource($source)
            ->setTypePromotionAffaire("dmd_emploi")
            ->setImage("dmd_emploi.png")
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

        return new JsonResponse([
            'error' => false
        ]);
    }
    
    #[Route('/newOffreEmploi', name: 'newOffreEmploi', methods: ['POST'])]
    public function newOffreEmploi(Request $request, VerificationsDS $verificationsDS, PromotionRepository $promotionRepository): Response
    {
        return new JsonResponse([
            'error' => true,
            'titre' => 'Oups!',
            'message' => "Le service de publication d'offre d'emploi est temporairement indisponible. Merci pour votre compréhension.",
        ]);

        $datas = $request->request;

                

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
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
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Votre numéro WhatsApp na pas encore été confirmer. S'il s'agit d'une erreur, contactez-nous sur WhatsApp.",
            ]);
        }

        if(!$titre_poste || !$description_poste || !$competences_requises|| !$type_contrat|| !$lieu_travail|| !$salaire|| !$niveau_experience|| !$horaire_travail|| !$avantages|| !$dure_contrat_not_cdi|| !$contact_emploiyeur|| !$date_limite_candidature|| !$lien_information_otionel){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Veuillez svp renseigner tous les champs...",
            ]);
        }

        $source = ($request->request->get('source') === 'web') ? 'web' : 'mobile';

        $promotion = new Promotion();
        $promotion->setUser($user)
            ->setSource($source)
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

        return new JsonResponse([
            'error' => false
        ]);
    }

    #[Route('/addProduitService', name: 'addProduitService', methods: ['POST'])]
    public function addProduitService(Request $request, VerificationsDS $verificationsDS, PromotionRepository $promotionRepository, FormulePromoAffaireRepository $formulePromoAffaireRepository, TraitementsDS $traitementsDS, MethodePaiementRepository $methodePaiementRepository): Response
    {
        $filesystem = new Filesystem();
        $uploadDir = $this->getParameter('promotion_directory');
        if (!$filesystem->exists($uploadDir)) {
            $filesystem->mkdir($uploadDir, 0775);
        }

        $datas = $request->request;
        $files = $request->files;

                

        $factureLignes = [];

        $inProgrammeRecompense = false;
        $publishOnDressurStatus = false;
        $boostFacebook = false;
        $montantBoostFacebook = 0;

        $inProgrammeRecompense = $this->requestBoolean($datas->get('inProgrammeRecompense'));
        try {
            $rewardBudgetData = $this->programmeRecompenseBudget->resolve(
                $inProgrammeRecompense,
                $datas->has('rewardBudget'),
                $datas->get('rewardBudget'),
                $this->isCustomRewardBudget($datas),
                $datas->get('totalViewsGoal')
            );
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Montant invalide',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
        $rewardBudget = $rewardBudgetData['amount'];
        if ($rewardBudgetData['legacy']) {
            $this->logger->notice('Ancienne requête Programme Récompense reçue sans rewardBudget.', [
                'route' => 'api_addProduitService',
            ]);
        }
        if ($datas->get('publishOnDressurStatus') !== null) {
            $publishOnDressurStatus = ((int)$datas->get('publishOnDressurStatus') == 1);
        }
        if ($datas->get('boostFacebook') !== null) {
            $boostFacebook = ((int)$datas->get('boostFacebook') == 1);
            $montantBoostFacebook = max(0, (int)$datas->get('montantBoostFacebook'));
            if ($boostFacebook && $montantBoostFacebook < 700) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Le montant minimum pour le boost Facebook est de 700 FCFA.',
                ]);
            }
        }

        $idFormulePromoAffaire = $datas->get('idFormulePromoAffaire');
        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $text = $datas->get('text');
        $mode = $datas->get('mode');
        $paymentMethod = $datas->get('paymentMethod'); // mon_argent
        $tel = $datas->get('tel');
        $whatsappContact = $datas->get('whatsappContact') ?: null;

        $image = $files->get('image');

        if ($text === null || $image === null) {
            return new JsonResponse([
                'error' => true,
                'titre' => "Erreur",
                'message' => "Veuillez fournir un texte et une image.",
            ]);
        }

        $imageValidation = $this->promotionImageValidator->validate($image);
        if (!$imageValidation['valid']) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Image invalide',
                'message' => $imageValidation['message'],
            ], Response::HTTP_BAD_REQUEST);
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
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Votre numéro WhatsApp na pas encore été confirmer. S'il s'agit d'une erreur, contactez-nous sur WhatsApp.",
            ]);
        }

        // Générer un nom de fichier unique
        $fileName = "dressur_pro_".UuidGenerator::v4().'.'.$imageValidation['extension'];

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

        if(!$paymentMethod){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez choisir une Methode de Paiement...',
            ]);
        }
        $methodePaiementEntity = $methodePaiementRepository->find($paymentMethod);
        if(!$methodePaiementEntity) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Veuillez choisir une Methode de Paiement valide...',
            ]);
        }

        // ── Montant total facturé, partagé par tous les moyens de paiement ────
        $montantTotal = $this->promotionBilling->calculateTotal(
            $formulBoost->getPrix(),
            $inProgrammeRecompense,
            $rewardBudget,
            $publishOnDressurStatus,
            $formulBoost->getNbrJour(),
            $boostFacebook,
            $montantBoostFacebook
        );
        $restrictionMessage = $traitementsDS->validateUserTransaction($user, (int) $montantTotal);
        if ($restrictionMessage !== null) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Montant minimum requis',
                'message' => $restrictionMessage,
            ]);
        }

        // ── Paiement via solde ────────────────────────────────────────────────
        if ($user->getSoldeDressur() >= $montantTotal) {
            $myTransaction = (new EntityTransaction())
                ->setUser($user)
                ->setTransactionFor("boost_affaire")
                ->setAmount($montantTotal)
                ->setAnnotherInfo([
                    'userId'                => $user->getId(),
                    'userUid'               => $user->getUid(),
                    'formulePromoAffaire'   => $formulBoost->getId(),
                    'image'                 => $fileName,
                    'description'           => $text,
                    'inProgrammeRecompense' => $inProgrammeRecompense,
                    'rewardBudget'          => $rewardBudget,
                    'publishOnDressurStatus'=> $publishOnDressurStatus,
                    'boostFacebook'         => $boostFacebook,
                    'montantBoostFacebook'  => $montantBoostFacebook,
                    'source'                => ($datas->get('source') === 'web') ? 'web' : 'mobile',
                    'whatsappContact'       => $whatsappContact,
                ]);
            $this->em->persist($myTransaction);
            $traitementsDS->payerViaSolde($myTransaction, $user, $montantTotal);
            $this->em->flush();
            return new JsonResponse([
                'error'      => false,
                'direct'     => true,
                'solde_used' => true,
                'titre'      => 'Succès',
                'message'    => 'Solde débité de '.(int)$montantTotal.' FCFA. Promotion Affaire enregistrée.',
            ]);
        }
        // ─────────────────────────────────────────────────────────────────────

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

            $factureLignes[] = "Promotion Affaire";

            if ($inProgrammeRecompense) {
                $factureLignes[] = "Programme Récompense";
            }

            if ($publishOnDressurStatus) {
                $factureLignes[] = "Publication Statut Dressur";
            }

            if ($boostFacebook) {
                $factureLignes[] = "Boost Page Facebook";
            }

            $array_create_transaction = [
                "description" => implode(" + ", $factureLignes),
                "amount" => $montantTotal,
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
                        'inProgrammeRecompense' => $inProgrammeRecompense,
                        'rewardBudget' => $rewardBudget,
                        'publishOnDressurStatus' => $publishOnDressurStatus,
                        'boostFacebook' => $boostFacebook,
                        'montantBoostFacebook' => $montantBoostFacebook,
                        'source' => ($datas->get('source') === 'web') ? 'web' : 'mobile',
                        'whatsappContact' => $whatsappContact,
                    ])
                ;
                $this->em->persist($myTransaction);

                $this->em->flush();
                
                $resultat = $traitementsDS->startPaiementFedaPay($transaction, $methodePaiementEntity);
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $this->sendMail->sendReport("uUid : ".$user->getUid()." WhatsApp : ".$user->getTel(), $th);
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        } elseif ($methodePaiementEntity->getAggregator() == "KPay") {
            $envPaiementApi = $traitementsDS->getEnvPaiementApiKPayDisponible();
            if(!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : ".$uid, "Aucun Webhook Disponible pour KPay");
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                ]);
            }

            try {
                $resultat = $traitementsDS->startPaiementKPay(
                    $envPaiementApi, 
                    $methodePaiementEntity, 
                    $montantTotal,
                    $tel,
                    $user->getPseudo(),
                    $user->getMail(),
                    "boost_affaire",
                    [
                        'userId' => $user->getId(),
                        'userUid' => $user->getUid(),
                        'formulePromoAffaire' => $formulBoost->getId(),
                        'image' => $fileName,
                        'description' => $text,
                        'inProgrammeRecompense' => $inProgrammeRecompense,
                        'rewardBudget' => $rewardBudget,
                        'publishOnDressurStatus' => $publishOnDressurStatus,
                        'boostFacebook' => $boostFacebook,
                        'montantBoostFacebook' => $montantBoostFacebook,
                        'source' => ($datas->get('source') === 'web') ? 'web' : 'mobile',
                        'whatsappContact' => $whatsappContact,
                    ],
                    $user,
                    $request->getSchemeAndHttpHost()
                );
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
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
                    $montantTotal,
                    $tel,
                    $user->getPseudo(),
                    $user->getMail(),
                    "boost_affaire",
                    [
                        'userId' => $user->getId(),
                        'userUid' => $user->getUid(),
                        'formulePromoAffaire' => $formulBoost->getId(),
                        'image' => $fileName,
                        'description' => $text,
                        'inProgrammeRecompense' => $inProgrammeRecompense,
                        'rewardBudget' => $rewardBudget,
                        'publishOnDressurStatus' => $publishOnDressurStatus,
                        'boostFacebook' => $boostFacebook,
                        'montantBoostFacebook' => $montantBoostFacebook,
                        'source' => ($datas->get('source') === 'web') ? 'web' : 'mobile',
                        'whatsappContact' => $whatsappContact,
                    ],
                    $user,
                    $request->getSchemeAndHttpHost()
                );
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
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

    #[Route('/addSiteApplication', name: 'addSiteApplication', methods: ['POST'])]
    public function addSiteApplication(Request $request, VerificationsDS $verificationsDS, TraitementsDS $traitementsDS, MethodePaiementRepository $methodePaiementRepository): Response
    {
        $filesystem = new Filesystem();
        $uploadDir = $this->getParameter('promotion_directory');
        if (!$filesystem->exists($uploadDir)) {
            $filesystem->mkdir($uploadDir, 0775);
        }

        $datas = $request->request;
        $files = $request->files;

        $uid             = $this->cookieDS->getWithFallback('uid', $request) ?: null;
        $sousType        = $datas->get('sousType');
        $nom             = $datas->get('nom');
        $description     = $datas->get('description');
        $url             = $datas->get('url');
        $methodePaiement = $datas->get('methodePaiement');
        $tel             = $datas->get('tel');
        $image           = $files->get('image');

        // Prix fixe pour ce type de promotion
        $montantSolde = 7750;

        $imageValidation = $this->promotionImageValidator->validate($image);
        if (!$imageValidation['valid']) {
            return new JsonResponse([
                'error'   => true,
                'titre'   => 'Image invalide',
                'message' => $imageValidation['message'],
            ], Response::HTTP_BAD_REQUEST);
        }

        $verificationUser = $verificationsDS->verifUSer($uid);
        if ($verificationUser["error"] == true) {
            return new JsonResponse([
                'error'   => true,
                'titre'   => $verificationUser["titre"],
                'message' => $verificationUser["message"],
                'deleted' => $verificationUser["deleted"],
                'blocked' => $verificationUser["blocked"],
            ]);
        }
        $user = $verificationUser["user"];

        if (!$user->getTelIsVerified()) {
            return new JsonResponse([
                'error'   => true,
                'titre'   => 'Erreur!',
                'message' => "Votre numéro WhatsApp na pas encore été confirmer. S'il s'agit d'une erreur, contactez-nous sur WhatsApp.",
            ]);
        }

        // Upload image
        $fileName = "dressur_pro_" . UuidGenerator::v4() . '.' . $imageValidation['extension'];
        try {
            $image->move($this->getParameter('promotion_directory'), $fileName);
        } catch (FileException $e) {
            return new JsonResponse([
                'error'   => true,
                'titre'   => 'Erreur!',
                'message' => $e->getMessage(),
            ]);
        }

        if (!$methodePaiement) {
            return new JsonResponse([
                'error'   => true,
                'titre'   => 'Attention!',
                'message' => 'Veuillez choisir une Methode de Paiement...',
            ]);
        }
        $methodePaiementEntity = $methodePaiementRepository->find($methodePaiement);
        if (!$methodePaiementEntity) {
            return new JsonResponse([
                'error'   => true,
                'titre'   => 'Attention!',
                'message' => 'Veuillez choisir une Methode de Paiement valide...',
            ]);
        }

        $source = ($datas->get('source') === 'web') ? 'web' : 'mobile';

        $annotherInfo = [
            'userId'      => $user->getId(),
            'userUid'     => $user->getUid(),
            'image'       => $fileName,
            'description' => $description,
            'nom'         => $nom,
            'url'         => $url,
            'sousType'    => $sousType,
            'source'      => $source,
        ];

        $restrictionMessage = $traitementsDS->validateUserTransaction($user, (int) $montantSolde);
        if ($restrictionMessage !== null) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Montant minimum requis',
                'message' => $restrictionMessage,
            ]);
        }

        // ── Paiement via solde ────────────────────────────────────────────────
        if ($user->getSoldeDressur() >= $montantSolde) {
            $myTransaction = (new EntityTransaction())
                ->setUser($user)
                ->setTransactionFor("promo_site_app")
                ->setAmount($montantSolde)
                ->setAnnotherInfo($annotherInfo);
            $this->em->persist($myTransaction);
            $traitementsDS->payerViaSolde($myTransaction, $user, $montantSolde);
            $this->em->flush();
            return new JsonResponse([
                'error'      => false,
                'direct'     => true,
                'solde_used' => true,
                'titre'      => 'Succès',
                'message'    => 'Solde débité de ' . (int)$montantSolde . ' FCFA. Promotion Sites & Applications enregistrée.',
            ]);
        }
        // ─────────────────────────────────────────────────────────────────────

        if ($methodePaiementEntity->getAggregator() == "FedaPay") {
            $envPaiementApi = $traitementsDS->getEnvPaiementApiFedaPayDisponible();
            if (!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : " . $uid, "Aucun Webhook Disponible pour FedaPay");
                return new JsonResponse([
                    'error'   => true,
                    'titre'   => 'Erreur!',
                    'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                ]);
            }
            FedaPay::setApiKey($envPaiementApi->getApiKey());
            FedaPay::setEnvironment($envPaiementApi->getEnvironment());

            $array_create_transaction = [
                "description" => "Promotion Sites & Applications",
                "amount"      => $montantSolde,
                "currency"    => ["iso" => "XOF"],
                "customer"    => [
                    "firstname"    => "Dressur : " . $user->getPseudo(),
                    "lastname"     => $user->getNom(),
                    "email"        => $user->getMail(),
                    "phone_number" => [
                        "number"  => $tel,
                        "country" => $methodePaiementEntity->getCodePays(),
                    ],
                ],
            ];

            try {
                $transaction = Transaction::create($array_create_transaction);

                $myTransaction = (new EntityTransaction())
                    ->setUser($user)
                    ->setTransactionFor("promo_site_app")
                    ->setIdTransaction($transaction["id"])
                    ->setReference($transaction["reference"])
                    ->setAmount($transaction["amount"])
                    ->setStatus($transaction["status"])
                    ->setCustomerId($transaction["customer_id"])
                    ->setCurrencyId($transaction["currency_id"])
                    ->setAnnotherInfo($annotherInfo);
                $this->em->persist($myTransaction);
                $this->em->flush();

                $resultat = $traitementsDS->startPaiementFedaPay($transaction, $methodePaiementEntity);
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $this->sendMail->sendReport("uUid : " . $user->getUid() . " WhatsApp : " . $user->getTel(), $th);
                return new JsonResponse([
                    'error'   => true,
                    'titre'   => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        } elseif ($methodePaiementEntity->getAggregator() == "KPay") {
            $envPaiementApi = $traitementsDS->getEnvPaiementApiKPayDisponible();
            if (!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : " . $uid, "Aucun Webhook Disponible pour KPay");
                return new JsonResponse([
                    'error'   => true,
                    'titre'   => 'Erreur!',
                    'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                ]);
            }

            try {
                $resultat = $traitementsDS->startPaiementKPay(
                    $envPaiementApi,
                    $methodePaiementEntity,
                    $montantSolde,
                    $tel,
                    $user->getPseudo(),
                    $user->getMail(),
                    "promo_site_app",
                    $annotherInfo,
                    $user,
                    $request->getSchemeAndHttpHost()
                );
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $this->sendMail->sendReport("uUid : " . $user->getUid() . " WhatsApp : " . $user->getTel(), $th);
                return new JsonResponse([
                    'error'   => true,
                    'titre'   => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        } else {
            // FeexPay
            $envPaiementApi = $traitementsDS->getEnvPaiementApiFeexPayDisponible();
            if (!$envPaiementApi) {
                $this->sendMail->sendReport("uUid : " . $uid, "Aucun Webhook Disponible pour FeexPay");
                return new JsonResponse([
                    'error'   => true,
                    'titre'   => 'Erreur!',
                    'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                ]);
            }

            try {
                $resultat = $traitementsDS->startPaiementFeexPay(
                    $envPaiementApi,
                    $methodePaiementEntity,
                    $montantSolde,
                    $tel,
                    $user->getPseudo(),
                    $user->getMail(),
                    "promo_site_app",
                    $annotherInfo,
                    $user,
                    $request->getSchemeAndHttpHost()
                );
                return new JsonResponse($resultat);
            } catch (\Throwable $th) {
                $this->sendMail->sendReport("uUid : " . $user->getUid() . " WhatsApp : " . $user->getTel(), $th);
                return new JsonResponse([
                    'error'   => true,
                    'titre'   => 'Erreur!',
                    'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                ]);
            }
        }

        return new JsonResponse(['error' => false]);
    }

    #[Route('/editProduitService', name: 'editProduitService', methods: ['POST'])]
    public function editProduitService(Request $request, VerificationsDS $verificationsDS, PromotionRepository $promotionRepository, TraitementsDS $traitementsDS): Response
    {
        $filesystem = new Filesystem();
        $uploadDir = $this->getParameter('promotion_directory');
        if (!$filesystem->exists($uploadDir)) {
            $filesystem->mkdir($uploadDir, 0775);
        }
        
        $datas = $request->request;
        $files = $request->files;

                

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
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

        $whatsappContact = $datas->get('whatsappContact');
        if ($whatsappContact) {
            $promotionAffaire->setWhatsappContact($whatsappContact);
        }

        if ($text) {
            $promotionAffaire->setDescription($text)->setStatus(1)->setMotif("");
        }
        
        if ($image) {
            $imageValidation = $this->promotionImageValidator->validate($image);
            if (!$imageValidation['valid']) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Image invalide',
                    'message' => $imageValidation['message'],
                ], Response::HTTP_BAD_REQUEST);
            }

            // Générer un nom de fichier unique
            $fileName = "dressur_pro_".UuidGenerator::v4().'.'.$imageValidation['extension'];

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

        $htmlAdmin = $this->renderView('emails/promo_affaire_edit_admin_notif.html.twig', [
            'user_nom'             => $user->getNom(),
            'user_mail'            => $user->getMail(),
            'user_tel'             => $user->getTel() ?? '—',
            'promotion_id'         => $promotionAffaire->getId(),
            'description_modifiee' => (bool) $text,
            'image_modifiee'       => (bool) $image,
            'nouvelle_description' => $text ?? '',
        ]);
        $this->sendMail->smtpMail(
            $_ENV['ADMIN_EMAIL'],
            "Promotion Affaire modifiée — " . $user->getNom() . " (#" . $promotionAffaire->getId() . ")",
            $htmlAdmin
        );

        return new JsonResponse([
            'error' => false
        ]);
    }

    #[Route('/newPromo', name: 'newPromo', methods: ['POST'])]
    public function newPromo(Request $request, FormulePromoAffaireRepository $formulePromoAffaireRepository, UserRepository $userRepository, VerificationsDS $verificationsDS, PromotionRepository $promotionRepository): Response
    {
        $datas = $request->request;
        
                

        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;
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
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Votre numéro WhatsApp na pas encore été confirmer. S'il s'agit d'une erreur, contactez-nous sur WhatsApp.",
            ]);
        }

        $formulBoost = $formulePromoAffaireRepository->find($idFormulBoost);
        if(!$formulBoost){
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
            ]);
        }

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

        return new JsonResponse([
            'error' => true,
            'titre' => 'Oups!',
            'message' => "Cette promotion est déjà été démarrée.",
        ]);      
    }

    #[Route('/newPromoPayant', name: 'newPromoPayant', methods: ['POST'])]
    public function newPromoPayant(Request $request, FormulePromoAffaireRepository $formulePromoAffaireRepository, BoostRepository $boostRepository, VerificationsDS $verificationsDS, PromotionRepository $promotionRepository, TraitementsDS $traitementsDS, MethodePaiementRepository $methodePaiementRepository): Response
    {
        $datas = $request->request;        
                
        $uid = $this->cookieDS->getWithFallback('uid', $request) ?: null;

        $factureLignes = [];

        $inProgrammeRecompense = false;
        $publishOnDressurStatus = false;
        $boostFacebook = false;
        $montantBoostFacebook = 0;

        $inProgrammeRecompense = $this->requestBoolean($datas->get('inProgrammeRecompense'));
        try {
            $rewardBudgetData = $this->programmeRecompenseBudget->resolve(
                $inProgrammeRecompense,
                $datas->has('rewardBudget'),
                $datas->get('rewardBudget'),
                $this->isCustomRewardBudget($datas),
                $datas->get('totalViewsGoal')
            );
        } catch (\InvalidArgumentException $exception) {
            return new JsonResponse([
                'error' => true,
                'titre' => 'Montant invalide',
                'message' => $exception->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
        $rewardBudget = $rewardBudgetData['amount'];
        if ($rewardBudgetData['legacy']) {
            $this->logger->notice('Ancienne requête Programme Récompense reçue sans rewardBudget.', [
                'route' => 'api_newPromoPayant',
            ]);
        }
        if ($datas->get('publishOnDressurStatus') !== null) {
            $publishOnDressurStatus = ((int)$datas->get('publishOnDressurStatus') == 1);
        }
        if ($datas->get('boostFacebook') !== null) {
            $boostFacebook = ((int)$datas->get('boostFacebook') == 1);
            $montantBoostFacebook = max(0, (int)$datas->get('montantBoostFacebook'));
            if ($boostFacebook && $montantBoostFacebook < 700) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Le montant minimum pour le boost Facebook est de 700 FCFA.',
                ]);
            }
        }

        $idPromotion = $datas->get('idPromotion');
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

        $formulBoost = $formulePromoAffaireRepository->find($idFormulBoost);
        if(!$formulBoost){

            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Nous avons rencontré un problème, contactez l'Assistance par WhatsApp.",
            ]);
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

        if(!$valueMethodePaiement){
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

        $promotion = $promotionRepository->find($idPromotion);
        if($promotion->getStatus() == 2 || $promotion->getStatus() == 4) {
            $promotion->setFormulePromoAffaire($formulBoost);

            // ── Montant total facturé, partagé par tous les moyens de paiement ────
            $montantTotal = $this->promotionBilling->calculateTotal(
                $formulBoost->getPrix(),
                $inProgrammeRecompense,
                $rewardBudget,
                $publishOnDressurStatus,
                $formulBoost->getNbrJour(),
                $boostFacebook,
                $montantBoostFacebook
            );
            $restrictionMessage = $traitementsDS->validateUserTransaction($user, (int) $montantTotal);
            if ($restrictionMessage !== null) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Montant minimum requis',
                    'message' => $restrictionMessage,
                ]);
            }

            // ── Paiement via solde ────────────────────────────────────────────────
            if ($user->getSoldeDressur() >= $montantTotal) {
                $myTransaction = (new EntityTransaction())
                    ->setUser($user)
                    ->setTransactionFor("re_boost_affaire")
                    ->setAmount($montantTotal)
                    ->setAnnotherInfo([
                        'userId'                => $user->getId(),
                        'userUid'               => $user->getUid(),
                        'formulBoostId'         => $formulBoost->getId(),
                        'promotionId'           => $promotion->getId(),
                        'inProgrammeRecompense' => $inProgrammeRecompense,
                        'rewardBudget'         => $rewardBudget,
                        'publishOnDressurStatus'=> $publishOnDressurStatus,
                        'boostFacebook'         => $boostFacebook,
                        'montantBoostFacebook'  => $montantBoostFacebook,
                        'source'                => ($datas->get('source') === 'web') ? 'web' : 'mobile',
                    ]);
                $this->em->persist($myTransaction);
                $traitementsDS->payerViaSolde($myTransaction, $user, $montantTotal);
                $this->em->flush();
                return new JsonResponse([
                    'error'      => false,
                    'direct'     => true,
                    'solde_used' => true,
                    'titre'      => 'Succès',
                    'message'    => 'Solde débité de '.(int)$montantTotal.' FCFA. Promotion Affaire relancée.',
                ]);
            }
            // ─────────────────────────────────────────────────────────────────────

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

                $factureLignes[] = "Promotion Affaire";

                if ($inProgrammeRecompense) {
                    $factureLignes[] = "Programme Récompense";
                }

                if ($publishOnDressurStatus) {
                    $factureLignes[] = "Publication Statut Dressur";
                }

                if ($boostFacebook) {
                    $factureLignes[] = "Boost Page Facebook";
                }

                $array_create_transaction = [
                    "description" => implode(" + ", $factureLignes),
                    "amount" => $montantTotal,
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
                            'inProgrammeRecompense' => $inProgrammeRecompense,
                            'rewardBudget' => $rewardBudget,
                            'publishOnDressurStatus' => $publishOnDressurStatus,
                            'boostFacebook' => $boostFacebook,
                            'montantBoostFacebook' => $montantBoostFacebook,
                            'source' => ($datas->get('source') === 'web') ? 'web' : 'mobile',
                        ])
                    ;
                    $this->em->persist($myTransaction);
        
                    $this->em->flush();
        
                    $resultat = $traitementsDS->startPaiementFedaPay($transaction, $methodePaiementEntity);
                    return new JsonResponse($resultat);
                } catch (\Throwable $th) {
                    $this->sendMail->sendReport("uUid : ".$user->getUid()." WhatsApp : ".$user->getTel(), $th);

                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Erreur!',
                        'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                    ]);
                }
            } elseif ($methodePaiementEntity->getAggregator() == "KPay") {
                $envPaiementApi = $traitementsDS->getEnvPaiementApiKPayDisponible();
                if(!$envPaiementApi) {
                    $this->sendMail->sendReport("uUid : ".$uid, "Aucun Webhook Disponible pour KPay");

                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Erreur!',
                        'message' => "Erreur de paiement. Veuillez contacter les administrateurs SVP.",
                    ]);
                }

                try {
                    $resultat = $traitementsDS->startPaiementKPay(
                        $envPaiementApi, 
                        $methodePaiementEntity, 
                        $montantTotal,
                        $tel,
                        $user->getPseudo(),
                        $user->getMail(),
                        "re_boost_affaire",
                        [
                            'userId' => $user->getId(),
                            'userUid' => $user->getUid(),
                            'formulBoostId' => $formulBoost->getId(),
                            'promotionId' => $promotion->getId(),
                            'inProgrammeRecompense' => $inProgrammeRecompense,
                            'rewardBudget' => $rewardBudget,
                            'publishOnDressurStatus' => $publishOnDressurStatus,
                            'boostFacebook' => $boostFacebook,
                            'montantBoostFacebook' => $montantBoostFacebook,
                            'source' => ($datas->get('source') === 'web') ? 'web' : 'mobile',
                        ],
                        $user,
                        $request->getSchemeAndHttpHost()
                    );
                    return new JsonResponse($resultat);
                } catch (\Throwable $th) {
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
                        $montantTotal,
                        $tel,
                        $user->getPseudo(),
                        $user->getMail(),
                        "re_boost_affaire",
                        [
                            'userId' => $user->getId(),
                            'userUid' => $user->getUid(),
                            'formulBoostId' => $formulBoost->getId(),
                            'promotionId' => $promotion->getId(),
                            'inProgrammeRecompense' => $inProgrammeRecompense,
                            'rewardBudget' => $rewardBudget,
                            'publishOnDressurStatus' => $publishOnDressurStatus,
                            'boostFacebook' => $boostFacebook,
                            'montantBoostFacebook' => $montantBoostFacebook,
                            'source' => ($datas->get('source') === 'web') ? 'web' : 'mobile',
                        ],
                        $user,
                        $request->getSchemeAndHttpHost()
                    );
                    return new JsonResponse($resultat);
                } catch (\Throwable $th) {
                    $this->sendMail->sendReport("uUid : ".$user->getUid()." WhatsApp : ".$user->getTel(), $th);
                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Erreur!',
                        'message' => "Nous avons rencontré une erreur. Vous serez contacté par un administrateur.",
                    ]);
                }
            }
        }

        return new JsonResponse([
            'error' => true,
            'titre' => 'Oups!',
            'message' => "Cette promotion est déjà été démarrée.",
        ]);
    }

    #[Route('/listPromotion/{uid}/{langUserPhone}', name: 'listPromotion', methods: ['POST', "GET"])]
    public function listPromotion(User $user, TraitementsDS $traitementsDS): Response
    {
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

    #[Route('/setMultiplePromotionsToWatch/{uid}', name: 'setMultiplePromotionsToWatch', methods: ['POST'])]
    public function setMultiplePromotionsToWatch($uid, UserRepository $userRepository, PromotionRepository $promotionRepository, Request $request): Response
    {
        $user = $userRepository->findOneBy(['uid' => $uid]);
        if (!$user) {
            return new JsonResponse(['error' => true], 400);
        }

        $data = json_decode($request->getContent(), true);
        $ids = is_array($data) ? ($data['ids'] ?? []) : [];

        foreach ($ids as $id) {
            $promotion = $promotionRepository->find((int) $id);
            if ($promotion) {
                $promotion->setToWatch($user, "vue");
            }
        }
        $this->em->flush();

        return new Response("OK");
    }

    #[Route('/getPromotionsDressurStatus', name: 'getPromotionsDressurStatus', methods: ['GET', 'POST'])]
    public function getPromotionsDressurStatus(PromotionRepository $promotionRepository): Response
    {
        $promotions = $promotionRepository->findBy(
            ['publishOnDressurStatus' => true, 'status' => 3],
            ['id' => 'DESC']
        );

        $data = array_map(function (Promotion $p) {
            $user = $p->getUser();
            return [
                'id'          => $p->getId(),
                'image'       => $p->getImage(),
                'description' => $p->getDescription(),
                'pseudo'      => $user ? $user->getPseudo() : '',
                'whatsapp'    => ($p->getWhatsappContact() ?? ($user ? $user->getTel() : '')),
                'type'        => $p->getTypePromotionAffaire(),
                'dateExp'     => $p->getDateExp()?->format('d/m/Y'),
            ];
        }, $promotions);

        return new JsonResponse([
            'error'      => false,
            'promotions' => $data,
        ]);
    }

    private function requestBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        if (!is_string($value)) {
            return false;
        }

        return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on'], true);
    }

    private function isCustomRewardBudget($datas): bool
    {
        foreach ([
            'rewardBudgetType',
            'rewardBudgetMode',
            'isCustomRewardBudget',
            'rewardBudgetCustom',
        ] as $key) {
            if (!$datas->has($key)) {
                continue;
            }

            $value = $datas->get($key);
            if (is_bool($value)) {
                return $value;
            }

            $normalized = strtolower(trim((string) $value));
            if (in_array($normalized, ['custom', 'customized', 'personnalise', 'personnalisé', 'manual', 'manuel', '1', 'true', 'yes'], true)) {
                return true;
            }
            if (in_array($normalized, ['predefined', 'preset', 'fixed', 'predetermine', 'prédéfini', '0', 'false', 'no'], true)) {
                return false;
            }
        }

        return false;
    }
}
