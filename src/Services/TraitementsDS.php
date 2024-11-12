<?php

namespace App\Services;

use DateTime;
use App\Entity\DeletedDS;
use App\Services\CookieDS;
use App\Services\SessionDS;
use App\Utilities\ZefameApi;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use App\Repository\BoostRepository;
use App\Repository\DSBonusRepository;
use App\Repository\MessageRepository;
use App\Controller\API\BoostController;
use App\Repository\DeletedDSRepository;
use App\Repository\PromotionRepository;
use App\Repository\VerifMailRepository;
use App\Repository\MotRefuserRepository;
use App\Repository\PreferenceRepository;
use App\Repository\SuggestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\API\ContactController;
use App\Repository\PromoReseauRepository;
use App\Repository\SignalementRepository;
use App\Repository\TransactionRepository;
use App\Repository\CampagneMailRepository;
use App\Repository\FormuleBoostRepository;
use App\Repository\EnvMailSenderRepository;
use App\Repository\EnvPaiementApiRepository;
use App\Repository\DSBonusHistoriqueRepository;
use App\Repository\FormuleDressurBotRepository;
use App\Controller\API\UserPreferenceController;
use App\Entity\FormulePromoReseau;
use App\Entity\MethodePaiement;
use App\Repository\FormulePromoAffaireRepository;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\MethodePaiementRepository;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TraitementsDS extends AbstractController
{
    private $em;
    private $env;
    private $verificationsDS;
    private $dSBonusHistoriqueRepository;
    private $boostRepository;
    private $wPBonusRepository;
    private $userRepository;
    private $sessionDS;
    private $preferenceRepository;
    private $transactionRepository;
    private $verifMailRepository;
    private $signalementRepository;
    private $promotionRepository;
    private $formulePromoReseauRepository;
    private $formuleBoostRepository;
    private $formuleDressurBotRepository;
    private $cookieDS;
    private $campagneMailRepository;
    private $promoReseauRepository;
    private $suggestionRepository;
    private $messageRepository;
    private $zefameApi;
    private $envPaiementApiRepository;
    private $envMailSenderRepository;
    private $motRefusers;
    private $formulePromoAffaireRepository;
    private $methodePaiementRepository;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, VerificationsDS $verificationsDS, DSBonusHistoriqueRepository $dSBonusHistoriqueRepository, BoostController $boostController, UserPreferenceController $userPreferenceController, ContactController $contactController, BoostRepository $boostRepository, DSBonusRepository $wPBonusRepository, UserRepository $userRepository, SessionDS $sessionDS, DeletedDSRepository $deletedDSRepository, PreferenceRepository $preferenceRepository, TransactionRepository $transactionRepository, VerifMailRepository $verifMailRepository, SignalementRepository $signalementRepository, PromotionRepository $promotionRepository, FormulePromoReseauRepository $formulePromoReseauRepository, FormuleBoostRepository $formuleBoostRepository, FormuleDressurBotRepository $formuleDressurBotRepository, CookieDS $cookieDS, CampagneMailRepository $campagneMailRepository, PromoReseauRepository $promoReseauRepository, SuggestionRepository $suggestionRepository, MessageRepository $messageRepository, ZefameApi $zefameApi, EnvPaiementApiRepository $envPaiementApiRepository, EnvMailSenderRepository $envMailSenderRepository, MotRefuserRepository $motRefuserRepository, FormulePromoAffaireRepository $formulePromoAffaireRepository, MethodePaiementRepository $methodePaiementRepository)
    {
        $this->methodePaiementRepository = $methodePaiementRepository;
        $this->formulePromoAffaireRepository = $formulePromoAffaireRepository;
        $this->motRefusers = $motRefuserRepository->findAll();
        $this->zefameApi = $zefameApi;
        $this->messageRepository = $messageRepository;
        $this->suggestionRepository = $suggestionRepository;
        $this->promoReseauRepository = $promoReseauRepository;
        $this->campagneMailRepository = $campagneMailRepository;
        $this->em = $em;
        $this->env = $env->find(1);
        $this->cookieDS = $cookieDS;
        $this->verificationsDS = $verificationsDS;
        $this->dSBonusHistoriqueRepository = $dSBonusHistoriqueRepository;
        $this->boostRepository = $boostRepository;
        $this->wPBonusRepository = $wPBonusRepository;
        $this->userRepository = $userRepository;
        $this->sessionDS = $sessionDS;
        $this->preferenceRepository = $preferenceRepository;
        $this->transactionRepository = $transactionRepository;
        $this->verifMailRepository = $verifMailRepository;
        $this->signalementRepository = $signalementRepository;
        $this->promotionRepository = $promotionRepository;
        $this->formulePromoReseauRepository = $formulePromoReseauRepository;
        $this->formuleBoostRepository = $formuleBoostRepository;
        $this->formuleDressurBotRepository = $formuleDressurBotRepository;
        $this->envPaiementApiRepository = $envPaiementApiRepository;
        $this->envMailSenderRepository = $envMailSenderRepository;
    }    

    function removeMaxSection($string) {
        // Expression régulière pour capturer " | Max XYZ |"
        $pattern = '/\s*\|\s*Max\s+\d+[KM]?\s*\|?|\s*MAX\s+\d+[KM]?\s*\♻️?/i';
        // Remplacer cette section par une chaîne vide
        $result = preg_replace($pattern, '', $string);
        $result = str_replace(" | Max 1K |", "", $result);
        $result = str_replace(" |", "", $result);
        $result = ucwords($result);
        return $result;
    }

    function getUserByUidInCookies() {
        if($this->cookieDS->get("uid")){
            $uid = $this->cookieDS->get("uid");
            $user = $this->userRepository->findOneBy(['uid' => $uid]);
            // $user = $this->infosUser($user);
            if($user){
                return $user;
            }
        }
        return false;
    }

    public function formatNumber($nbrVue) {
        $suffix = '';        
        if ($nbrVue >= 1000 && $nbrVue < 1000000) {
          $nbrVue = number_format($nbrVue / 1000, 1);
          $suffix = 'K';
        } else if ($nbrVue >= 1000000 && $nbrVue < 1000000000) {
          $nbrVue = number_format($nbrVue / 1000000, 1);
          $suffix = 'M';
        } else if ($nbrVue >= 1000000000) {
          $nbrVue = number_format($nbrVue / 1000000000, 1);
          $suffix = 'Md';
        }        
        return (string)$nbrVue.$suffix;
    }

    public function separateurMillier($nombre) {       
        return number_format($nombre, 0, ',', ' ');;
    }

    function makePseudoWithEmailAdress($email) {
        // Extract the part before the @
        $parts = explode('@', $email);
        $username = $parts[0];

        $slugger = new AsciiSlugger();
        $username = strtolower((string)$slugger->slug($username));

        foreach ($this->motRefusers as $mot) {
            $username = str_replace($mot->getMot(), '', $username);
        }

        if (strlen($username) == 0) {
            $username = "ds-id-";
        }
    
        if (strlen($username) < 5) {
            $username = str_pad($username, 5, strval(rand(0, 9)));
        }
    
        if (strlen($username) > 13) {
            $username = substr($username, 0, 13);
        }
    
        return $username;
    }
    
    public function userBoosts($boosts) {
        $userBoosts = [];
        $prix_boost = "";
        $statut = "";

        foreach ($boosts as $boost) {
            if($this->sessionDS->get("langUserPhone") != "fr") {
                $statut = "In progress";
            } else {
                $statut = "En cours";
            }
            $statusNumber = 1;

            if((new DateTime()) < $boost->getDateDebut()){
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "scheduled";
                } else {
                    $statut = "Programmé";
                }
                $statusNumber = 2;
            }            

            if((new DateTime()) > $boost->getDateExp()){
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Completed";
                } else {
                    $statut = "Terminé";
                }
                $statusNumber = 3;
            }

            if($boost->getMode() == "Gratuit") {
                $prix_boost = $boost->getFormuleBoost()->getPrix(). " Bonus";
                $modeNumber = 1;
            } else {
                $prix_boost = $boost->getFormuleBoost()->getPrix(). " FCFA";
                $modeNumber = 2;
            }

            if($this->sessionDS->get("langUserPhone") != "fr") {
                if($boost->getMode() == "Gratuit") {
                    $boostMode = "Free";
                } else {
                    $boostMode = "Paid";
                }
                $unBoost = [
                    'id' => (string)$boost->getId(),
                    'nomFormule' => $boost->getFormuleBoost()->getTitre(),
                    'dateDebutFormule' => "from ".($boost->getDateDebut())->format('d-m-Y à H:i')." to ".($boost->getDateExp())->format('d-m-Y à H:i'),
                    'prixFormule' => (string)$prix_boost,
                    'statutFormule' => $statut,
                    'modeBoostFormule' => $boostMode,
                    'statusNumber' => $statusNumber,
                    'modeNumber' => $modeNumber,
                ];
            } else {
                $unBoost = [
                    'id' => (string)$boost->getId(),
                    'nomFormule' => $boost->getFormuleBoost()->getTitre(),
                    'dateDebutFormule' => "du ".($boost->getDateDebut())->format('d-m-Y à H:i')." au ".($boost->getDateExp())->format('d-m-Y à H:i'),
                    'prixFormule' => (string)$prix_boost,
                    'statutFormule' => $statut,
                    'modeBoostFormule' => $boost->getMode(),
                    'statusNumber' => $statusNumber,
                    'modeNumber' => $modeNumber,
                ];
            }
            array_push($userBoosts, $unBoost);
        }
        $userBoosts = array_reverse($userBoosts);
        return $userBoosts;
    }

    public function vuesImpressionsCumulerUserPromos($promos){
        $countVues = 0;
        $countImpressions = 0;
        foreach ($promos as $promo) {
            $countVues += $promo->getNombreDeVue();
            $countImpressions += $promo->getNombreImpression();
        }
        return [
            "countVues" => $countVues,
            "countImpressions" => $countImpressions,
        ];
    }

    public function userPromos($promos){
        $userPromos = [];

        foreach ($promos as $promo) {
            $statut = "";
            $peutPayer = false;

            if($promo->getDateExp() and ((new DateTime()) > $promo->getDateExp())) {
                $promo->setStatus(4);
            }

            if ($promo->getStatus() == 0) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Rejected";
                } else {
                    $statut = "Rejeter";
                }
            } else if($promo->getStatus() == 1) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Waiting for validation";
                } else {
                    $statut = "En Attente de validation";
                }
            } else if($promo->getStatus() == 2) {
                $peutPayer = true;
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Accept and pending payment";
                } else {
                    $statut = "Accepter et en attente de paiement";
                }
            } else if($promo->getStatus() == 3) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Accept and in progress";
                } else {
                    $statut = "Accepter et en cours";
                }
            } else if($promo->getStatus() == 4) {
                $peutPayer = true;
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Completed";
                } else {
                    $statut = "Terminé";
                }
            }

            $descp_promo = $promo->getDescription();
            if($promo->getTypePromotionAffaire() == "offre_emploi") {
                $descp_promo = $promo->getAnnotherInfo()["description_poste"];
            }
            if($promo->getTypePromotionAffaire() == "dmd_emploi") {
                $descp_promo = $promo->getAnnotherInfo()["description_profil_demandeur"];
            }

            $unePromo = [
                "id" => (string)$promo->getId(),
                "username" => $promo->getUser(),
                "peutPayer" => $peutPayer,
                "image" => $promo->getImage(),
                "nombreDeVues" => (string)$this->formatNumber($promo->getNombreDeVue()),
                "nombreImpression" => (string)$this->formatNumber($promo->getNombreImpression()),
                "description" => $descp_promo,
                "statusNumber" => $promo->getStatus(),
                "status" => $statut,
                "dateDebut" => $promo->getDateDebut() ? ($promo->getDateDebut())->format('d-m-Y à H:i') : "",
                "dateExp" => $promo->getDateExp() ? ($promo->getDateExp())->format('d-m-Y à H:i') : "",
                "formulePromotion" => $promo->getFormulePromoAffaire() ? $promo->getFormulePromoAffaire()->getTitre() : "",
                "motif" => $promo->getMotif() ? $promo->getMotif() : "",
                "typePromotionAffaire" => $promo->getTypePromotionAffaire(),
                "annotherInfo" => $promo->getAnnotherInfo(),
            ];
            array_push($userPromos, $unePromo);
        }

        $this->em->flush();
        $userPromos = array_reverse($userPromos);
        return $userPromos;
    }

    public function listeMethodePaiements() {
        $listeMethodePaiement = [];
        foreach ($this->methodePaiementRepository->findAll() as $methode) {
            if(!$this->methodePaiementRepository->findOneBy(['autreMethodeUn' => $methode])) {
                if($methode->isActivated()){
                    array_push($listeMethodePaiement, [
                        "id" => $methode->getId(),
                        "value" => $methode->getId(),
                        "titre" => $methode->getPays()." - ".$methode->getTitre(),
                        "code" => $methode->getCode(),
                        "pays" => $methode->getPays(),
                        "aggregator" => $methode->getAggregator(),
                    ]);
                } else if($methode->getAutreMethodeUn()) {
                    if($methode->getAutreMethodeUn()->isActivated()) {
                        array_push($listeMethodePaiement, [
                            "id" => $methode->getAutreMethodeUn()->getId(),
                            "value" => $methode->getAutreMethodeUn()->getId(),
                            "titre" => $methode->getAutreMethodeUn()->getPays()." - ".$methode->getAutreMethodeUn()->getTitre(),
                            "code" => $methode->getAutreMethodeUn()->getCode(),
                            "pays" => $methode->getAutreMethodeUn()->getPays(),
                            "aggregator" => $methode->getAutreMethodeUn()->getAggregator(),
                        ]);
                    }
                }
            }
        }
        return $listeMethodePaiement;
    }

    public function listeFormulBoost() {
        $listeFormulBoost = [];
        foreach ($this->formuleBoostRepository->findAll() as $boost) {
            array_push($listeFormulBoost, [
                "id" => $boost->getId(),
                "value" => $boost->getId(),
                "label" => $boost->getTitre(),
                "prix" => intval($boost->getPrix()),
                "jours" => $boost->getNbrJour(),
            ]);
        }
        return $listeFormulBoost;
    }

    public function listeFormulePromoAffaire() {
        $listeFormulBoost = [];
        foreach ($this->formulePromoAffaireRepository->findAll() as $boost) {
            array_push($listeFormulBoost, [
                "id" => $boost->getId(),
                "value" => $boost->getId(),
                "label" => $boost->getTitre(),
                "prix" => intval($boost->getPrix()),
                "jours" => $boost->getNbrJour(),
            ]);
        }
        return $listeFormulBoost;
    }

    public function listeFormuleDressurBot() {
        $listeFormuleDressurBot = [];
        array_push($listeFormuleDressurBot, [
            "id" => "",
            "label" => "Cliquez pour choisir...",
        ]);
        foreach ($this->formuleDressurBotRepository->findAll() as $boost) {
            $label = $boost->getTitre()." : ";
            $label .= $boost->getPrix()." FCFA pour ".$boost->getNbrJour()." Jours ";
            if($boost->getSignature() == "oui") {
                $label .= "+ Signature";
            }            
            array_push($listeFormuleDressurBot, [
                "id" => $boost->getId(),
                "label" => $label,
            ]);
        }
        return $listeFormuleDressurBot;
    }

    public function userPromoReseaus($promos){
        $this->checkAndUpdateStatusZefame();
        $userPromoReseaus = [];

        foreach ($promos as $promo) {
            $statut = "";

            if ($promo->getStatus() == 0) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Refunded";
                } else {
                    $statut = "Remboursée";
                }
            } else if($promo->getStatus() == 1) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "On hold";
                } else {
                    $statut = "En attente";
                }
            } else if($promo->getStatus() == 2) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "In progress";
                } else {
                    $statut = "En cours";
                }
            } else if($promo->getStatus() == 3) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Completed";
                } else {
                    $statut = "Terminer";
                }
            }

            $titre = $promo->getFormulePromoReseau()->getTitre();
            if($promo->getFormulePromoReseau()->getParent()) {
                $titre = $promo->getFormulePromoReseau()->getParent()->getTitre()." : ".$promo->getFormulePromoReseau()->getTitre();
            }

            $unePromo = [
                "id" => (string)$promo->getId(),
                "titre" => $titre,
                "qteDemander" => $this->separateurMillier($promo->getQteDemander()),
                "prixFixer" => $this->separateurMillier($promo->getPrixFixer())." FCFA",
                "url" => $promo->getUrl(),
                "reference" => $promo->getIdZefame(),
                "statusNumber" => $promo->getStatus(),
                "status" => $statut,
                "compteurDebut" => $this->separateurMillier($promo->getCompteurDebut()),
                "compteurRestant" => $this->separateurMillier($promo->getCompteurRestant()),
                "createdAt" => $promo->getCreatedAt() ? ($promo->getCreatedAt())->format('d-m-Y à H:i') : "",
                "updatedAt" => $promo->getUpdatedAt() ? ($promo->getUpdatedAt())->format('d-m-Y à H:i') : "",
            ];
            array_push($userPromoReseaus, $unePromo);
        }

        $this->em->flush();
        $userPromoReseaus = array_reverse($userPromoReseaus);
        return $userPromoReseaus;
    }

    public function userCampagneMail($campagneMails) {
        $userCampagneMail = [];

        foreach ($campagneMails as $campagneMail) {
            $statut = "";
            $peutPayer = false;

            if($campagneMail->getStatus() == 3 && $campagneMail->getTraitement() == true && count($campagneMail->getFileAttenteCampagneMails()) == 0){
                $campagneMail->setStatus(4);
            }

            if ($campagneMail->getStatus() == 0) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Rejected";
                } else {
                    $statut = "Rejeter";
                }
            } else if($campagneMail->getStatus() == 1) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Waiting for validation";
                } else {
                    $statut = "En Attente de validation";
                }
            } else if($campagneMail->getStatus() == 2) {
                $peutPayer = true;
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Accept and pending payment";
                } else {
                    $statut = "Accepter et en attente de paiement";
                }
            } else if($campagneMail->getStatus() == 3) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Accept and in progress";
                } else {
                    $statut = "Accepter et en cours";
                }
            } else if($campagneMail->getStatus() == 4) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Completed";
                } else {
                    $statut = "Terminé";
                }
            }

            $unecampagneMail = [
                "id" => (string)$campagneMail->getId(),
                "idFormuleCampagneMail" => (string)$campagneMail->getFormuleCampagneMail()->getId(),
                "prixFormuleCampagneMail" => (string)$campagneMail->getFormuleCampagneMail()->getPrix(),
                "titre" => $campagneMail->getTitre(),
                "sujet" => $campagneMail->getSujet(),
                "replyto" => $campagneMail->getReplyto(),
                "sendto" => $campagneMail->getSendto(),
                "contentmail" => $campagneMail->getContentmail(),
                "statusNumber" => $campagneMail->getStatus(),
                "status" => $statut,
                "peutPayer" => $peutPayer,
                "createdAt" => $campagneMail->getCreatedAt() ? ($campagneMail->getCreatedAt())->format('d-m-Y à H:i') : "",
                "motif" => $campagneMail->getMotif() ? $campagneMail->getMotif() : "",
            ];
            array_push($userCampagneMail, $unecampagneMail);
        }
        $userCampagneMail = array_reverse($userCampagneMail);
        $this->em->flush();
        return $userCampagneMail;
    }

    public function listeFormulePromoReseau() {
        $listeFormulePromoReseau = [];
        foreach ($this->formulePromoReseauRepository->findBy(['parent' => NULL, 'available' => true]) as $formule) {
            $lesFormulesFils = [];
            foreach ($this->formulePromoReseauRepository->findBy(['parent' => $formule, 'available' => true]) as $formuleFils) {
                $prix_service_fcfa = $formuleFils->getPrix() * 1.2 * 1.6 * 700;
                $prix_service_fcfa = round($prix_service_fcfa) + 1;
                if($this->sessionDS->get("langUserPhone") == 'fr') {
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
                "iconFlutterName" => $formule->getTitre(),
                "lesFormulesFils" => $lesFormulesFils,
            ]);
        }
        return $listeFormulePromoReseau;
    }

    public function getAffaires($limit){
        $top_trois_affaires = [];
        $promos = $this->promotionRepository->findBy(
            [
                "isFakeVue" => false,
                "status" => [3, 4],
            ], ["nombreDeVue" => "DESC"], $limit
        );
        foreach ($promos as $promo) {
            $promo->setToWatch(null, "web");
            $descp_promo = $promo->getDescription();
            if($promo->getTypePromotionAffaire() == "offre_emploi") {
                $descp_promo = $promo->getAnnotherInfo()["description_poste"];
            }
            if($promo->getTypePromotionAffaire() == "dmd_emploi") {
                $descp_promo = $promo->getAnnotherInfo()["description_profil_demandeur"];
            }
            $unePromo = [
                "uidUser" => $promo->getUser()->getUid(),
                "id" => $promo->getId(),
                "image" => $promo->getImage(),
                "description" => $descp_promo,
                "whatsappNumber" => $promo->getUser()->getTel(),
                "pseudoAnnonceur" => $promo->getUser()->getPseudo(),
                "nombreDeVues" => (string)$this->formatNumber($promo->getNombreDeVue()),
                "nombreImpression" => (string)$this->formatNumber($promo->getNombreImpression()),
                "typePromotionAffaire" => $promo->getTypePromotionAffaire(),
                "annotherInfo" => $promo->getAnnotherInfo(),
            ];
            array_push($top_trois_affaires, $unePromo);            
        }
        $this->em->flush();
        shuffle($top_trois_affaires);
        return $top_trois_affaires;
    }

    public function getTopAffaires($limite){
        $top_trois_affaires = [];
        $promos = $this->promotionRepository->findBy([ "isFakeVue" => false ], ["nombreDeVue" => "DESC"], $limite);

        foreach ($promos as $promo) {
            $descp_promo = $promo->getDescription();
            if($promo->getTypePromotionAffaire() == "offre_emploi") {
                $descp_promo = $promo->getAnnotherInfo()["description_poste"];
            }
            if($promo->getTypePromotionAffaire() == "dmd_emploi") {
                $descp_promo = $promo->getAnnotherInfo()["description_profil_demandeur"];
            }
            $unePromo = [
                "uidUser" => $promo->getUser()->getUid(),
                "id" => $promo->getId(),
                "image" => $promo->getImage(),
                "description" => $descp_promo,
                "whatsappNumber" => $promo->getUser()->getTel(),
                "pseudoAnnonceur" => $promo->getUser()->getPseudo(),
                "nombreDeVues" => (string)$this->formatNumber($promo->getNombreDeVue()),
                "nombreImpression" => (string)$this->formatNumber($promo->getNombreImpression()),
                "typePromotionAffaire" => $promo->getTypePromotionAffaire(),
                "annotherInfo" => $promo->getAnnotherInfo(),
            ];
            array_push($top_trois_affaires, $unePromo);            
        }
        // Mélanger l'ordre des éléments de manière aléatoire
        shuffle($top_trois_affaires);
        return $top_trois_affaires;
    }

    public function listePubliciteAffichageAuxUsers($user){
        $listePubliciteAffichageAuxUsers = [];
        $promos = $this->promotionRepository->findBy([
            "status" => 3,
            "limited" => true,
        ]);
        foreach ($promos as $promo) {
            if ($user->getId() == 3 || $user->getId() == 2) {
                if((new DateTime()) >= ($promo->getDateDebut()) and (new DateTime()) <= ($promo->getDateExp())) {
                    if($promo->getIsFakeVue() == true) {
                        $promo->setToWatch($user, "fakeVue");
                    } else {
                        $promo->setToWatch($user, "all");
                    }

                    $descp_promo = $promo->getDescription();
                    if($promo->getTypePromotionAffaire() == "offre_emploi") {
                        $descp_promo = $promo->getAnnotherInfo()["description_poste"];
                    }
                    if($promo->getTypePromotionAffaire() == "dmd_emploi") {
                        $descp_promo = $promo->getAnnotherInfo()["description_profil_demandeur"];
                    }

                    $unePromo = [
                        "uidUser" => $promo->getUser()->getUid(),
                        "id" => $promo->getId(),
                        "image" => $promo->getImage(),
                        "description" => $descp_promo,
                        "whatsappNumber" => $promo->getUser()->getTel(),
                        "pseudoAnnonceur" => $promo->getUser()->getPseudo(),
                        "nombreDeVues" => (string)$this->formatNumber($promo->getNombreDeVue()),
                        "nombreImpression" => (string)$this->formatNumber($promo->getNombreImpression()),
                        "typePromotionAffaire" => $promo->getTypePromotionAffaire(),
                        "annotherInfo" => $promo->getAnnotherInfo(),
                    ];
                    array_push($listePubliciteAffichageAuxUsers, $unePromo);
                } else {
                    $promo->setStatus(4);
                }
            } else {
                if(in_array($user->getPays(), $promo->getUser()->getPreference()->getPaysChoisies())) {
                    if((new DateTime()) >= ($promo->getDateDebut()) and (new DateTime()) <= ($promo->getDateExp())) {
                        if($promo->getIsFakeVue() == true) {
                            $promo->setToWatch($user, "fakeVue");
                        } else {
                            $promo->setToWatch($user, "all");
                        }

                        $descp_promo = $promo->getDescription();
                        if($promo->getTypePromotionAffaire() == "offre_emploi") {
                            $descp_promo = $promo->getAnnotherInfo()["description_poste"];
                        }
                        if($promo->getTypePromotionAffaire() == "dmd_emploi") {
                            $descp_promo = $promo->getAnnotherInfo()["description_profil_demandeur"];
                        }

                        $unePromo = [
                            "uidUser" => $promo->getUser()->getUid(),
                            "id" => $promo->getId(),
                            "image" => $promo->getImage(),
                            "description" => $descp_promo,
                            "whatsappNumber" => $promo->getUser()->getTel(),
                            "pseudoAnnonceur" => $promo->getUser()->getPseudo(),
                            "nombreDeVues" => (string)$this->formatNumber($promo->getNombreDeVue()),
                            "nombreImpression" => (string)$this->formatNumber($promo->getNombreImpression()),
                            "typePromotionAffaire" => $promo->getTypePromotionAffaire(),
                            "annotherInfo" => $promo->getAnnotherInfo(),
                        ];
                        array_push($listePubliciteAffichageAuxUsers, $unePromo);
                    } else {
                        $promo->setStatus(4);
                    }
                }
            }
        }

        foreach ($this->promotionRepository->findBy(["limited" => false]) as $promoVIP) {
            $descp_promo = $promo->getDescription();
            if($promo->getTypePromotionAffaire() == "offre_emploi") {
                $descp_promo = $promo->getAnnotherInfo()["description_poste"];
            }
            if($promo->getTypePromotionAffaire() == "dmd_emploi") {
                $descp_promo = $promo->getAnnotherInfo()["description_profil_demandeur"];
            }

            array_push($listePubliciteAffichageAuxUsers, [
                "uidUser" => $promo->getUser()->getUid(),
                "id" => $promoVIP->getId(),
                "image" => $promoVIP->getImage(),
                "description" => $descp_promo,
                "whatsappNumber" => $promoVIP->getUser()->getTel(),
                "pseudoAnnonceur" => $promoVIP->getUser()->getPseudo(),
                "nombreDeVues" => (string)$this->formatNumber($promo->getNombreDeVue()),
                "nombreImpression" => (string)$this->formatNumber($promo->getNombreImpression()),
                "typePromotionAffaire" => $promo->getTypePromotionAffaire(),
                "annotherInfo" => $promo->getAnnotherInfo(),
            ]);
        }
        $this->em->flush();
        shuffle($listePubliciteAffichageAuxUsers);
        return $listePubliciteAffichageAuxUsers;
    }

    public function userContacts($user){
        $userContacts = [];
        foreach ($user->getContact()->getAllIdOfMyContacts() as $key => $idContact){
            $userContact = $this->userRepository->find($idContact);
            if($userContact) {
                $unContact = [
                    "id" => (string)$userContact->getUid(),
                    "pseudo" => $userContact->getPseudo(),
                    "mail" => $userContact->getMail(),
                    "pays" => (string)$userContact->getPays(),
                    "tel" => $userContact->getTel(),
                    "nom" => (string)$userContact,
                    "apropos" => $userContact->getApropos() ? $userContact->getApropos() : "",
                    "tiktok" => $userContact->getTiktok() ? $userContact->getTiktok() : "",
                    "instagram" => $userContact->getInstagram() ? $userContact->getInstagram() : "",
                    "facebook" => $userContact->getFacebook() ? $userContact->getFacebook() : "",
                    "youtube" => $userContact->getYoutube() ? $userContact->getYoutube() : "",
                ];
                array_push($userContacts, $unContact);
            }
        }
        return $userContacts;
    }

    public function adminListeContacts($users){
        $userContacts = [];
        foreach ($users as $unUser){
            $unContact = [
                "id" => (string)$unUser->getUid(),
                "pseudo" => $unUser->getPseudo(),
                "mail" => $unUser->getMail(),
                "pays" => (string)$unUser->getPays(),
                "tel" => $unUser->getTel(),
                "nom" => (string)$unUser,
                "apropos" => $unUser->getApropos() ? $unUser->getApropos() : "",
                "tiktok" => $unUser->getTiktok() ? $unUser->getTiktok() : "",
                "instagram" => $unUser->getInstagram() ? $unUser->getInstagram() : "",
                "facebook" => $unUser->getFacebook() ? $unUser->getFacebook() : "",
                "youtube" => $unUser->getYoutube() ? $unUser->getYoutube() : "",
            ];
            array_push($userContacts, $unContact);
        }
        return $userContacts;
    }

    public function generateUserContactAdd($contacts){
        $contactAdds = [];
        foreach ($contacts as $contact) {
            $user = $this->userRepository->find($contact["id"]);
            array_push($contactAdds, [
                'idContact' => $contact["idContact"],
                'nomAdd' => $contact,
                'telAdd' => $user->getTel(),
            ]);
        }
        return $contactAdds;
    }

    public function getAddDisponible($user){
        $contacts = [];
        if($user->getId() == 3 || $user->getId() == 2) {
            $boosts = $this->boostRepository->findAll();
            foreach ($boosts as $boost){
                $userBoost = $boost->getUser();

                if($userBoost->getLastLoginTo() != Null) {
                    $intervale = ($userBoost->getLastLoginTo())->diff(new DateTime());
                    $hoursDifference = $intervale->h;
                    if ($intervale->d > 0) {
                        $hoursDifference += $intervale->d * 24;
                    }

                    // si le user sai connecter il y a plus de 48H, le boost n'est pas proposer
                    if($hoursDifference <= 48) {
                        if((new DateTime()) >= ($boost->getDateDebut()) and (new DateTime()) <= ($boost->getDateExp())) {
                            array_push($contacts, [
                                'id' => $userBoost->getId(),
                                'uid' => $userBoost->getUid(),
                                'pseudo' => $userBoost->getPseudo(),
                                'pays' => (string)$userBoost->getPays(),
                                'nom' => (string)$userBoost,
                                'tel' => $userBoost->getTel(),
                            ]);
                        }
                    }
                }
            }
        } else {
            foreach ($user->getPreference()->getPaysChoisies() as $codePays){
                $boosts = $this->boostRepository->getBoostAndUser($codePays);
                foreach ($boosts as $boost){
                    $userBoost = $boost["boost"]->getUser();
                    if($userBoost->getLastLoginTo() != Null) {
                        $intervale = ($userBoost->getLastLoginTo())->diff(new DateTime());
                        $hoursDifference = $intervale->h;
                        if ($intervale->d > 0) {
                            $hoursDifference += $intervale->d * 24;
                        }

                        // si le user sai connecter il y a plus de 48H, le boost n'est pas proposer
                        if($hoursDifference <= 48) {
                            if($userBoost->getId() != $user->getId()){
                                $contactPossibiliteUn = in_array($userBoost->getId(), $user->getContact()->getWhoIAdd());
                                $contactPossibiliteDeux = in_array($user->getId(), $userBoost->getContact()->getWhoIAdd());
                                if( !$contactPossibiliteUn and !$contactPossibiliteDeux ){
                                    if((new DateTime()) >= ($boost["boost"]->getDateDebut()) and (new DateTime()) <= ($boost["boost"]->getDateExp())){
                                        if(in_array($user->getPays(), $userBoost->getPreference()->getPaysChoisies())){
                                            array_push($contacts, [
                                                'id' => $userBoost->getId(),
                                                'uid' => $userBoost->getUid(),
                                                'pseudo' => $userBoost->getPseudo(),
                                                'pays' => (string)$userBoost->getPays(),
                                                'nom' => (string)$userBoost,
                                                'tel' => $userBoost->getTel(),
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $contacts;
    }

    public function generateUserContactAddAdmin($contacts){
        $contactAdds = [];
        foreach ($contacts as $contact) {
            array_push($contactAdds, [
                'idContact' => $contact->getId(),
                'nomAdd' => (string)$contact,
                'telAdd' => $contact->getTel(),
            ]);
        }
        return $contactAdds;
    }

    public function resetPassword(int $length = 8): ?string
    {
        // allowed characters
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ";
        // make sure we have enough length
        while (strlen($chars) < $length) {
            $chars .= $chars;
        }
        return "DS".substr(str_shuffle($chars), 0, $length);
    }

    public function bonusTab($lesDSBonus){
        $bonusTab = [];
        foreach ($lesDSBonus as $DSBonus) {
            $bonus = [
                "titre" => $DSBonus->getTitre(),
                "montant" => (string)$DSBonus->getMontant()." Points",
                "date" => ($DSBonus->getCreatedAt())->format('d-m-Y à H:i'),
            ];
            array_push($bonusTab, $bonus);
        }
        return $bonusTab;
    }

    public function infosUser($user){
        $count = $this->vuesImpressionsCumulerUserPromos($user->getPromotions());
        $totalImpressions = $count['countImpressions'];
        $totalVues = $count['countVues'];
        $totalImpressionsText = $this->formatNumber($count['countImpressions']);
        $totalVuesText = $this->formatNumber($count['countVues']);
        $lesPublicitesArray = $this->listePubliciteAffichageAuxUsers($user);
        $lesPublicites = json_encode($lesPublicitesArray);
        if(strlen(str_replace(" ", "", $user->getTiktok())) == 0 ) { $user->setTiktok(null); }
        if(strlen(str_replace(" ", "", $user->getInstagram())) == 0 ) { $user->setInstagram(null); }
        if(strlen(str_replace(" ", "", $user->getFacebook())) == 0 ) { $user->setFacebook(null); }
        if(strlen(str_replace(" ", "", $user->getYoutube())) == 0 ) { $user->setYoutube(null); }
        if(strlen(str_replace(" ", "", $user->getApropos())) == 0 ) { $user->setApropos(null); }
        return [
            "boostEnCours" => $this->verificationsDS->siBoostEnCours($this->boostRepository->findBy(['user' => $user])),
            "totalImpressions" => $totalImpressions,
            "totalVues" => $totalVues,
            "totalImpressionsText" => $totalImpressionsText,
            "totalVuesText" => $totalVuesText,
            "myDressurVersion" => $this->env->getVersionApp(),
            "mailIsMaxxFire" => ($user->getMail() == "equipe.test.dressur.ds@gmail.com") ? true : false,
            "id" => $user->getId(),
            "uid" => $user->getUid(),
            "name_complete" => $user->__toString(),
            "avatar" => $user->getAvatar(),
            "banniere" => $user->getBanniere(),
            "pseudo" => $user->getPseudo(),
            "nom" => $user->getNom(),
            "mail" => $user->getMail(),
            "pays" => $user->getPays(),
            "tel" => $user->getTel(),
            "apropos" => $user->getApropos(),
            "tiktok" => $user->getTiktok(),
            "instagram" => $user->getInstagram(),
            "facebook" => $user->getFacebook(),
            "youtube" => $user->getYoutube(),
            "createdAt" => $user->getCreatedAt(),
            "mailIsVerified" => $user->getMailIsVerified(),
            "telIsVerified" => $user->getTelIsVerified(),
            "soldeBonus" => $user->getSoldeBonus(),
            "codeBonus" => $user->getCodeBonus(),
            "siParrain" => $user->getParrain()? true : false,
            "nombreContactDispo" => count($this->getAddDisponible($user)),
            "lesPublicites" => $lesPublicites,
            "havePublicites" => (count($lesPublicitesArray) >= 1) ? true : false,
            "nombreFilleuls" => count($user->getFilleuls()),
            "admin" => $user->getAdmin() ? true : false,
            "permissionAdd" => ($this->verificationsDS->permissionAdd($user))["permissionAdd"],
            "messageErreurPermissionAdd" => ($this->verificationsDS->permissionAdd($user))["messageErreurPermissionAdd"],
            "commissionBonus" => $this->env->getCommissionBonus(),
            'preferencePays' => $user->getPreference()->getPaysChoisies(),
            'preferenceCentreInteretLoisir' => $user->getPreference()->getCentreInteretLoisirChoisies(),
        ];
    }

    public function getCountryWithMethodePaiement($valueMethodePaiement) {
        if($valueMethodePaiement == "mtn" || $valueMethodePaiement == "moov" || $valueMethodePaiement == "sbin") { $country = "bj"; }
        else if($valueMethodePaiement == "mtn_ci" || $valueMethodePaiement == "orange_ci" || $valueMethodePaiement == "moov_ci") { $country = "ci"; }
        else if($valueMethodePaiement == "orange_sn" || $valueMethodePaiement == "free_sn") { $country = "sn"; }
        else if($valueMethodePaiement == "moov_tg" || $valueMethodePaiement == "togocel") { $country = "tg"; }
        else if($valueMethodePaiement == "airtel_ne") { $country = "ne"; }
        else if($valueMethodePaiement == "orange_ml") { $country = "ml"; }
        else if($valueMethodePaiement == "mtn_open_gn") { $country = "gn"; }
        else if($valueMethodePaiement == "moov_bf" || $valueMethodePaiement == "orange_bf") { $country = "bf"; }
        return $country;
    }

    public function startPaiement($transaction, $mode) {
        $paiementDirect = ["mtn","moov","mtn_ci","moov_tg","mtn_open","airtel_ne","free_sn","togocel","mtn_ecw"];
        $paiementDirect = ["moov","mtn_ci","moov_tg","mtn_open","airtel_ne","free_sn","togocel","mtn_ecw"];
        if(in_array($mode, $paiementDirect)) {
            $token = $transaction->generateToken()->token;
            $transaction->sendNowWithToken($mode, $token);
            return [
                "error" => false,
                "direct" => true,
                "url" => "none",
            ];
        } else {
            $token = $transaction->generateToken()->url;
            return [
                "error" => false,
                "direct" => false,
                "url" => $token,
            ];
        }
    }

    public function getSoldeZefame() {
        return $this->zefameApi->balance()->balance;
    }

    public function majServicesZefame() {
        $newFormuleText = "New : ";
        if(count($this->formulePromoReseauRepository->findAll()) == 0) {
            // les FormulePromoReseau
            $formulePromoReseau = (new FormulePromoReseau())->setTitre("TikTok")->setAvailable(true);
            $this->em->persist($formulePromoReseau);

            $formulePromoReseau = (new FormulePromoReseau())->setTitre("Instagram")->setAvailable(true);
            $this->em->persist($formulePromoReseau);

            $formulePromoReseau = (new FormulePromoReseau())->setTitre("Twitter")->setAvailable(true);
            $this->em->persist($formulePromoReseau);

            $formulePromoReseau = (new FormulePromoReseau())->setTitre("Youtube")->setAvailable(true);
            $this->em->persist($formulePromoReseau);

            $formulePromoReseau = (new FormulePromoReseau())->setTitre("Facebook")->setAvailable(true);
            $this->em->persist($formulePromoReseau);

            $formulePromoReseau = (new FormulePromoReseau())->setTitre("Telegram")->setAvailable(true);
            $this->em->persist($formulePromoReseau);

            $formulePromoReseau = (new FormulePromoReseau())->setTitre("Twitch")->setAvailable(true);
            $this->em->persist($formulePromoReseau);

            $formulePromoReseau = (new FormulePromoReseau())->setTitre("Spotify")->setAvailable(true);
            $this->em->persist($formulePromoReseau);

            $formulePromoReseau = (new FormulePromoReseau())->setTitre("Discord")->setAvailable(true);
            $this->em->persist($formulePromoReseau);

            $this->em->flush();
        }

        $lesFormulesParent = $this->formulePromoReseauRepository->findBy(['parent' => null]);
        foreach ($this->formulePromoReseauRepository->findAll() as $uneFormuleReseau) {
            $uneFormuleReseau->setAvailable(false);
            if($uneFormuleReseau->getParent() == null) { 
                $uneFormuleReseau->setAvailable(true)
                    ->setPrix(null)
                    ->setQte(null)
                    ->setQteMin(null)
                    ->setQteMax(null)
                ;
            }
        }

        foreach ($this->zefameApi->services() as $unservice) {
            $uneFR = $this->formulePromoReseauRepository->findOneBy(['idZefame' => $unservice->service]);
            if($uneFR) {
                $uneFR->setAvailable(true)
                    ->setQte(1000)
                    ->setQteMax($unservice->max)
                    ->setPrixZefame($unservice->rate)
                ;
                if($unservice->min > $uneFR->getQteMin()) { $uneFR->setQteMin($unservice->min); }
                if($unservice->rate > $uneFR->getPrix()) { $uneFR->setPrix($unservice->rate); }
            } else {
                $newFormuleText .= $unservice->name." | <br>";
                
                $newFormulePromoReseau = new FormulePromoReseau();
                $newFormulePromoReseau->setQte(1000)->setAvailable(false)
                    ->setIdZefame($unservice->service)
                    ->setTitre($unservice->name)
                    ->setPrix($unservice->rate)
                    ->setPrixZefame($unservice->rate)
                    ->setQteMin($unservice->min)
                    ->setQteMax($unservice->max)
                ;
                foreach ($lesFormulesParent as $unParent) {
                    $nomTeste = str_replace(strtolower($unParent->getTitre())." ", '', strtolower($unservice->name));
                    if(strlen($nomTeste) < strlen($unservice->name)) {
                        $newFormulePromoReseau->setParent($unParent)
                            ->setTitre($this->removeMaxSection($nomTeste))
                        ;
                        break;
                    }
                }
                $nomTeste = str_replace("followers", '', strtolower($unservice->name));
                $nomTeste = str_replace("subscribe", '', strtolower($nomTeste));
                if(strlen($nomTeste) < strlen($unservice->name)) {
                    $newFormulePromoReseau->setQteMin(500);
                }
                $this->em->persist($newFormulePromoReseau);
                // dump($newFormulePromoReseau);
                // dump($unservice);
            }
        }

        if($newFormuleText != "New : ") {
            $this->addFlash('danger', $newFormuleText);
        }
        // dd("END");
        $this->em->flush();
    }

    public function checkAndUpdateStatusZefame() {
        $promoReseauStatut2 = $this->promoReseauRepository->findBy(['status' => 2]);
        foreach ($promoReseauStatut2 as $unePromoReseau) {
            $resultZefame = $this->zefameApi->status($unePromoReseau->getIdZefame());
            if(!isset($resultZefame->error)){
                if($resultZefame->status == "In progress"){
                    $unePromoReseau->setPrixZefame($resultZefame->charge)
                        ->setCompteurDebut($resultZefame->start_count)
                        ->setCompteurRestant($resultZefame->remains)
                        ->setUpdatedAt(new DateTime())
                    ;
                }
                if($resultZefame->status == "Completed"){
                    $unePromoReseau->setStatus(3)
                        ->setCompteurRestant(0)
                        ->setUpdatedAt(new DateTime())
                    ;
                }
                if($resultZefame->status == "Canceled"){
                    $unePromoReseau->setStatus(0)
                        ->setUpdatedAt(new DateTime())
                    ;
                }
                // dump($unePromoReseau);
                // dd($resultZefame);
            }
        }
        $this->em->flush();
    }

    public function getEnvPaiementApiDisponible() {
        $envPaiementApis = $this->envPaiementApiRepository->findBy(['activated' => true]);
        foreach ($envPaiementApis as $envPaiementApi) {
            if($envPaiementApi->getCountTransactionApproved() < 10) {
                return $envPaiementApi;
            }
        }
        return false;
    }

    public function execPurge($user){
        foreach ($this->userRepository->findBy(['parrain' => $user]) as $element) {
            $element->setParrain($this->userRepository->find(3));
            $this->em->flush();
        }

        foreach ($this->campagneMailRepository->findBy(['user' => $user]) as $element) { $this->em->remove($element); }
                
        foreach ($this->dSBonusHistoriqueRepository->findBy(['user' => $user]) as $element) { $this->em->remove($element); }
        
        foreach ($this->promoReseauRepository->findBy(['user' => $user]) as $element) { $this->em->remove($element); }
        
        foreach ($this->promotionRepository->findBy(['user' => $user]) as $element) { $this->em->remove($element); }
        
        foreach ($this->suggestionRepository->findBy(['user' => $user]) as $element) { $this->em->remove($element); }
        
        foreach ($this->transactionRepository->findBy(['user' => $user]) as $element) { $this->em->remove($element); }
        
        foreach ($this->verifMailRepository->findBy(['user' => $user]) as $element) { $this->em->remove($element); }
        
        foreach ($this->boostRepository->findBy(['user' => $user]) as $element) { $this->em->remove($element); }
        
        foreach ($this->signalementRepository->findBy(['signaler' => $user]) as $element) { $this->em->remove($element); }
        
        foreach ($this->signalementRepository->findBy(['signalant' => $user]) as $element) { $this->em->remove($element); }
        
        foreach ($this->messageRepository->findBy(['emetteur' => $user]) as $element) { $this->em->remove($element); }
        
        foreach ($this->messageRepository->findBy(['recepteur' => $user]) as $element) { $this->em->remove($element); }

        $deletedDS = new DeletedDS();
        $deletedDS->setMail($user->getMail())
            ->setTel($user->getTel())
            ->setMotif("GET OUT BY ADMIN")
        ;
        $this->em->persist($deletedDS);

        $this->em->remove($user);

        $this->em->flush();
    }
}