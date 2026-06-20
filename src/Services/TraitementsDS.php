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
use App\Repository\MessageRepository;
use App\Repository\DeletedDSRepository;
use App\Repository\PromotionRepository;
use App\Repository\VerifMailRepository;
use App\Repository\MotRefuserRepository;
use App\Repository\PreferenceRepository;
use App\Repository\SuggestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\PromoReseauRepository;
use App\Repository\SignalementRepository;
use App\Repository\TransactionRepository;
use App\Repository\FormuleBoostRepository;
use App\Repository\EnvMailSenderRepository;
use App\Repository\EnvPaiementApiRepository;
use App\Repository\FormuleDressurBotRepository;
use App\Entity\FormulePromoReseau;
use App\Entity\Transaction;
use App\Repository\FormulePromoAffaireRepository;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\HistoriqueProgrammeRecompenseRepository;
use App\Repository\MethodePaiementRepository;
use Feexpay\FeexpayPhp\FeexpayClass;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TraitementsDS extends AbstractController
{
    private $em;
    private $env;
    private $verificationsDS;
    private $boostRepository;
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
    private $promoReseauRepository;
    private $suggestionRepository;
    private $messageRepository;
    private $zefameApi;
    private $envPaiementApiRepository;
    private $envMailSenderRepository;
    private $motRefusers;
    private $formulePromoAffaireRepository;
    private $methodePaiementRepository;
    private $historiqueProgrammeRecompenseRepository;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, VerificationsDS $verificationsDS, BoostRepository $boostRepository, UserRepository $userRepository, SessionDS $sessionDS, DeletedDSRepository $deletedDSRepository, PreferenceRepository $preferenceRepository, TransactionRepository $transactionRepository, VerifMailRepository $verifMailRepository, SignalementRepository $signalementRepository, PromotionRepository $promotionRepository, FormulePromoReseauRepository $formulePromoReseauRepository, FormuleBoostRepository $formuleBoostRepository, FormuleDressurBotRepository $formuleDressurBotRepository, CookieDS $cookieDS, PromoReseauRepository $promoReseauRepository, SuggestionRepository $suggestionRepository, MessageRepository $messageRepository, ZefameApi $zefameApi, EnvPaiementApiRepository $envPaiementApiRepository, EnvMailSenderRepository $envMailSenderRepository, MotRefuserRepository $motRefuserRepository, FormulePromoAffaireRepository $formulePromoAffaireRepository, MethodePaiementRepository $methodePaiementRepository, HistoriqueProgrammeRecompenseRepository $historiqueProgrammeRecompenseRepository)
    {
        $this->methodePaiementRepository = $methodePaiementRepository;
        $this->formulePromoAffaireRepository = $formulePromoAffaireRepository;
        $this->motRefusers = $motRefuserRepository->findAll();
        $this->zefameApi = $zefameApi;
        $this->messageRepository = $messageRepository;
        $this->suggestionRepository = $suggestionRepository;
        $this->promoReseauRepository = $promoReseauRepository;
        $this->em = $em;
        $this->env = $env->find(1);
        $this->cookieDS = $cookieDS;
        $this->verificationsDS = $verificationsDS;
        $this->boostRepository = $boostRepository;
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
        $this->historiqueProgrammeRecompenseRepository = $historiqueProgrammeRecompenseRepository;
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
    
    public function userBoosts($boosts) {
        $userBoosts = [];
        foreach ($boosts as $boost) {
            $isFr = $this->sessionDS->get("langUserPhone") == "fr";
            $typeBoost = $boost->getTypeBoost();
            $nbContactsObtenus = $boost->getNbContactsObtenus();
            $nbContactsMax = $boost->getFormuleBoost()->getNbContactsMax();
            $dateDebut = $boost->getDateDebut();
            $dateExp = $boost->getDateExp();
            // --- Statut ---
            $statusNumber = 1;
            $statut = $isFr ? "En cours" : "In progress";
            if ((new DateTime()) < $dateDebut) {
                $statut = $isFr ? "Programmé" : "Scheduled";
                $statusNumber = 2;
            } elseif ($typeBoost === 'quota') {
                if ($dateExp !== null) {
                    $statut = $isFr ? "Terminé" : "Completed";
                    $statusNumber = 3;
                }
            } else {
                if ($dateExp !== null && (new DateTime()) > $dateExp) {
                    $statut = $isFr ? "Terminé" : "Completed";
                    $statusNumber = 3;
                }
            }
            // --- Mode ---
            $prix_boost = $boost->getFormuleBoost()->getPrix() . " FCFA";
            $modeNumber = $boost->getMode() == "Gratuit" ? 1 : 2;
            $boostMode = $boost->getMode() == "Gratuit"
                ? ($isFr ? "Gratuit" : "Free")
                : ($isFr ? "Payant" : "Paid");
            // --- Période / résumé selon le type ---
            if ($typeBoost === 'quota') {
                $dateDebutStr = $dateDebut->format('d-m-Y à H:i');
                $dateFinStr = $dateExp ? $dateExp->format('d-m-Y à H:i') : null;
                $periodeFormule = $isFr
                    ? "depuis le " . $dateDebutStr . ($dateFinStr ? " · terminé le " . $dateFinStr : "")
                    : "since " . $dateDebutStr . ($dateFinStr ? " · ended " . $dateFinStr : "");
            } else {
                $dateExpStr = $dateExp ? $dateExp->format('d-m-Y à H:i') : '—';
                $periodeFormule = $isFr
                    ? "du " . $dateDebut->format('d-m-Y à H:i') . " au " . $dateExpStr
                    : "from " . $dateDebut->format('d-m-Y à H:i') . " to " . $dateExpStr;
            }
            $unBoost = [
                'id'                  => (string)$boost->getId(),
                'typeBoost'           => $typeBoost,
                'nomFormule'          => $boost->getFormuleBoost()->getTitre(),
                'dateDebutFormule'    => $periodeFormule,
                'dateDebut'           => $dateDebut->format('d-m-Y à H:i'),
                'dateExp'             => $dateExp ? $dateExp->format('d-m-Y à H:i') : null,
                'nbContactsObtenus'   => $nbContactsObtenus,
                'nbContactsMax'       => $nbContactsMax,
                'prixFormule'         => (string)$prix_boost,
                'statutFormule'       => $statut,
                'modeBoostFormule'    => $boostMode,
                'statusNumber'        => $statusNumber,
                'modeNumber'          => $modeNumber,
            ];
            array_push($userBoosts, $unBoost);
        }
        $userBoosts = array_reverse($userBoosts);
        return $userBoosts;
    }

    public function finishParticipationProgrammeRecompense($promoAffaire) {
        $lesParticipations = $this->historiqueProgrammeRecompenseRepository->findBy(['promotion' => $promoAffaire]);

        foreach ($lesParticipations as $uneParticipation) {
            if(in_array($uneParticipation->getStatus(), ['terminer', 'en_cours'])) {
                $uneParticipation->setStatus("echouer");
            }
        }
    }

    private function applyExpiredPromoStatusUpdates(iterable $promos): void
    {
        $dirty = false;
        foreach ($promos as $promo) {
            if ($promo->getDateExp() && (new DateTime()) > $promo->getDateExp() && $promo->getStatus() == 3) {
                $promo->setStatus(4)->setInProgrammeRecompense(false)->setPublishOnDressurStatus(false);
                $this->finishParticipationProgrammeRecompense($promo);
                $dirty = true;
            }
        }
        if ($dirty) {
            $this->em->flush();
        }
    }

    private function applyWebViewTracking(iterable $promos): void
    {
        $dirty = false;
        foreach ($promos as $promo) {
            $promo->setToWatch(null, 'web');
            $dirty = true;
        }
        if ($dirty) {
            $this->em->flush();
        }
    }

    private function applyUserViewTracking($user, iterable $promos): void
    {
        $dirty = false;
        foreach ($promos as $promo) {
            $type = $promo->getIsFakeVue() ? 'fakeVue' : 'all';
            if ($user->getId() == 3 || $user->getId() == 2) {
                if ((new DateTime()) >= $promo->getDateDebut() && (new DateTime()) <= $promo->getDateExp()) {
                    $promo->setToWatch($user, $type);
                    $dirty = true;
                }
            } else {
                if (in_array($user->getPays(), $promo->getUser()->getPreference()->getPaysChoisies())) {
                    if ((new DateTime()) >= $promo->getDateDebut() && (new DateTime()) <= $promo->getDateExp()) {
                        $promo->setToWatch($user, $type);
                        $dirty = true;
                    }
                }
            }
        }
        if ($dirty) {
            $this->em->flush();
        }
    }

    public function userPromos($promos){
        $this->applyExpiredPromoStatusUpdates($promos);
        $userPromos = [];

        foreach ($promos as $promo) {
            $statut = "";
            $peutPayer = false;

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
                "inProgrammeRecompense" => $promo->isInProgrammeRecompense() ? 1 : 0,
                "publishOnDressurStatus" => $promo->isPublishOnDressurStatus() ? 1 : 0,
            ];
            array_push($userPromos, $unePromo);
        }

        $userPromos = array_reverse($userPromos);
        return $userPromos;
    }

    public function listeMethodePaiements() {
        $listeMethodePaiement = [];
        foreach ($this->methodePaiementRepository->findBy([], ['pays' => "ASC", 'titre' => "ASC"]) as $methode) {
            if(!$this->methodePaiementRepository->findOneBy(['autreMethodeUn' => $methode])) {
                if($methode->isActivated()){
                    array_push($listeMethodePaiement, [
                        "id" => $methode->getId(),
                        "value" => $methode->getId(),
                        "label" => $methode->getPays()." - ".$methode->getTitre(),
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
                            "label" => $methode->getAutreMethodeUn()->getPays()." - ".$methode->getAutreMethodeUn()->getTitre(),
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

    public function listeFormulBoost(string $typeBoost = 'date') {
        $listeFormulBoost = [];
        foreach ($this->formuleBoostRepository->findAll() as $boost) {
            // Toujours exclure les formules gratuites (prix = 0)
            // et filtrer par le type demandé (défaut 'date' pour rétrocompat)
            if (
                $boost->isActivated() == true
                && intval($boost->getPrix()) > 0
                && $boost->getTypeBoost() === $typeBoost
            ) {
                array_push($listeFormulBoost, [
                    "id"            => $boost->getId(),
                    "value"         => $boost->getId(),
                    "label"         => $boost->getTitre(),
                    "prix"          => intval($boost->getPrix()),
                    "jours"         => $boost->getNbrJour(),
                    "typeBoost"     => $boost->getTypeBoost(),
                    "nbContactsMax" => $boost->getNbContactsMax(),
                ]);
            }
        }
        return $listeFormulBoost;
    }

    public function listeFormulePromoAffaire() {
        $listeFormulBoost = [];
        foreach ($this->formulePromoAffaireRepository->findBy(['activated' => true]) as $boost) {
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
            if($boost->isActivated() == true) {
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

        $userPromoReseaus = array_reverse($userPromoReseaus);
        return $userPromoReseaus;
    }

    public function listeFormulePromoReseau() {
        $listeFormulePromoReseau = [];
        foreach ($this->formulePromoReseauRepository->findBy(['parent' => NULL, 'available' => true]) as $formule) {
            $lesFormulesFils = [];
            foreach ($this->formulePromoReseauRepository->findBy(['parent' => $formule, 'available' => true]) as $formuleFils) {
                $prix_service_fcfa = $formuleFils->getPrix() * 1.2 * 1.7 * 700;
                $prix_service_fcfa = round($prix_service_fcfa) + 1;
                $description_service = "💰 ".$formuleFils->getQte()." ".$formuleFils->getTitre()." pour ".$prix_service_fcfa." FCFA\n\nQuantité Min : ".$formuleFils->getQteMin()." - Max : ".$formuleFils->getQteMax()."\n\n".$formuleFils->getDescription();
                $description_service .= "\n\nAucun remboursement n'est possible, vérifiez donc bien avant d'effectuer votre commande et surtout, ne faites pas d'erreur d'URL.";
                $description_service .= "\n\nVous pourriez être contacté par l'assistance Dressur via WhatsApp pour des informations supplémentaires.";
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
        $this->applyWebViewTracking($promos);
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
                "inProgrammeRecompense" => $promo->isInProgrammeRecompense() ? 1 : 0,
            ];
            array_push($top_trois_affaires, $unePromo);            
        }
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
                "inProgrammeRecompense" => $promo->isInProgrammeRecompense() ? 1 : 0,
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
            "isFakeVue" => false,
        ]);

        $this->applyExpiredPromoStatusUpdates($promos);
        $this->applyUserViewTracking($user, $promos);

        foreach ($promos as $promo) {
            if ($user->getId() == 3 || $user->getId() == 2) {
                if((new DateTime()) >= ($promo->getDateDebut()) and (new DateTime()) <= ($promo->getDateExp())) {
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
                        "inProgrammeRecompense" => $promo->isInProgrammeRecompense() ? 1 : 0,
                    ];
                    array_push($listePubliciteAffichageAuxUsers, $unePromo);
                }
            } else {
                if(in_array($user->getPays(), $promo->getUser()->getPreference()->getPaysChoisies())) {
                    if((new DateTime()) >= ($promo->getDateDebut()) and (new DateTime()) <= ($promo->getDateExp())) {
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
                            "inProgrammeRecompense" => $promo->isInProgrammeRecompense() ? 1 : 0,
                        ];
                        array_push($listePubliciteAffichageAuxUsers, $unePromo);
                    }
                }
            }
        }

        foreach ($this->promotionRepository->findBy(["limited" => false, "isFakeVue" => false]) as $promoVIP) {
            $descp_promo = $promoVIP->getDescription();
            if($promoVIP->getTypePromotionAffaire() == "offre_emploi") {
                $descp_promo = $promoVIP->getAnnotherInfo()["description_poste"];
            }
            if($promoVIP->getTypePromotionAffaire() == "dmd_emploi") {
                $descp_promo = $promoVIP->getAnnotherInfo()["description_profil_demandeur"];
            }

            array_push($listePubliciteAffichageAuxUsers, [
                "uidUser" => $promoVIP->getUser()->getUid(),
                "id" => $promoVIP->getId(),
                "image" => $promoVIP->getImage(),
                "description" => $descp_promo,
                "whatsappNumber" => $promoVIP->getUser()->getTel(),
                "pseudoAnnonceur" => $promoVIP->getUser()->getPseudo(),
                "nombreDeVues" => (string)$this->formatNumber($promoVIP->getNombreDeVue()),
                "nombreImpression" => (string)$this->formatNumber($promoVIP->getNombreImpression()),
                "typePromotionAffaire" => $promoVIP->getTypePromotionAffaire(),
                "annotherInfo" => $promoVIP->getAnnotherInfo(),
                "inProgrammeRecompense" => $promoVIP->isInProgrammeRecompense() ? 1 : 0,
            ]);
        }
        shuffle($listePubliciteAffichageAuxUsers);
        return $listePubliciteAffichageAuxUsers;
    }

    public function listePromotionAffaireInProgrammeRecompense($user){
        $listePubliciteAffichageAuxUsers = [];
        $promos = $this->promotionRepository->findBy([
            "status" => 3,
            "limited" => true,
            "inProgrammeRecompense" => true,
        ], ['id' => 'DESC']);

        $this->applyExpiredPromoStatusUpdates($promos);

        foreach ($promos as $promo) {
            if ($user->getId() == 3 || $user->getId() == 2) {
                if((new DateTime()) >= ($promo->getDateDebut()) and (new DateTime()) <= ($promo->getDateExp())) {

                    $descp_promo = $promo->getDescription();

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
                        "inProgrammeRecompense" => $promo->isInProgrammeRecompense() ? 1 : 0,
                    ];
                    array_push($listePubliciteAffichageAuxUsers, $unePromo);
                }
            } else {
                if(in_array($user->getPays(), $promo->getUser()->getPreference()->getPaysChoisies())) {
                    if((new DateTime()) >= ($promo->getDateDebut()) and (new DateTime()) <= ($promo->getDateExp())) {

                        $descp_promo = $promo->getDescription();

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
                            "inProgrammeRecompense" => $promo->isInProgrammeRecompense() ? 1 : 0,
                        ];

                        /**
                         * ici je recherche dans l'historique, la derniere occurence pour la promotion de l'itération
                         * dont le statut est approuver 
                         * pour l'utilisateur connecter
                         * 
                         * si on a pas de resultat, on affiche la promotion dans la liste des programme de recempense pour l'utilisateur
                         * si on a un resultat, on affiche la promotion dans la liste des promotions du programme a condition que la date actuel
                         * dépasse la date d'expiration de l'historique approuver
                         */
                        $lastAppreouvedForThisPromotion = $this->historiqueProgrammeRecompenseRepository->findOneBy(
                            ['promotion' => $promo, 'status' => 'approuver', 'user' => $user], 
                            ['id' => 'DESC']
                        );
                        if($lastAppreouvedForThisPromotion) {
                            if((new DateTime()) > $lastAppreouvedForThisPromotion->getExpiredAt()) {
                                array_push($listePubliciteAffichageAuxUsers, $unePromo);
                            }
                        } else {
                            array_push($listePubliciteAffichageAuxUsers, $unePromo);
                        }
                    }
                }
            }
        }

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
                    "nom" => $userContact->getNom() ? $userContact->getNom() : "",
                    "apropos" => $userContact->getApropos() ? $userContact->getApropos() : "",
                    "tiktok" => $userContact->getTiktok() ? $userContact->getTiktok() : "",
                    "instagram" => $userContact->getInstagram() ? $userContact->getInstagram() : "",
                    "facebook" => $userContact->getFacebook() ? $userContact->getFacebook() : "",
                    "youtube" => $userContact->getYoutube() ? $userContact->getYoutube() : "",
                ];
                array_push($userContacts, $unContact);
            }
        }
        // return $userContacts;

        // inverser l'ordre du tableau
        return array_reverse($userContacts);
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

    public function getAddProgrammer(){
        $lesBoostContact = $this->boostRepository->findAll();
        $nbrContacts = 0;
        $now = new DateTime();
        foreach ($lesBoostContact as $boost) {
            $dateDebut = $boost->getDateDebut();
            $dateExp   = $boost->getDateExp();
            $typeBoost = $boost->getTypeBoost();
            // Un boost est "programmé" si dateDebut est dans le futur
            // et qu'il n'est pas encore épuisé (pour quota : dateExp null = non épuisé)
            if ($now <= $dateDebut) {
                if ($typeBoost === 'quota' && $dateExp === null) {
                    $nbrContacts++;
                } elseif ($typeBoost !== 'quota' && $dateExp !== null && $now <= $dateExp) {
                    $nbrContacts++;
                }
            }
        }
        return $nbrContacts;
    }

    public function getBoostEnCoursCount(): int
    {
        $now = new DateTime();
        $count = 0;
        foreach ($this->boostRepository->findAll() as $boost) {
            $dateDebut = $boost->getDateDebut();
            $dateExp   = $boost->getDateExp();
            $typeBoost = $boost->getTypeBoost();
            if ($now < $dateDebut) {
                continue; // programmé, pas encore démarré
            }
            if ($typeBoost === 'quota') {
                if ($dateExp === null) { $count++; } // quota non épuisé = en cours
            } else {
                if ($dateExp !== null && $now < $dateExp) { $count++; }
            }
        }
        return $count;
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
                        $isActif = $boost->getTypeBoost() === 'quota'
                            ? ((new DateTime()) >= $boost->getDateDebut() && $boost->getDateExp() === null)
                            : ((new DateTime()) >= $boost->getDateDebut() && $boost->getDateExp() !== null && (new DateTime()) <= $boost->getDateExp());
                        if($isActif) {
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
                                $boostObj = $boost["boost"];
                                $isActif = $boostObj->getTypeBoost() === 'quota'
                                    ? ((new DateTime()) >= $boostObj->getDateDebut() && $boostObj->getDateExp() === null)
                                    : ((new DateTime()) >= $boostObj->getDateDebut() && $boostObj->getDateExp() !== null && (new DateTime()) <= $boostObj->getDateExp());
                                if($isActif){
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

    public function infosUser($user){
        $lesPublicitesArray = $this->listePubliciteAffichageAuxUsers($user);
        $lesPublicites = json_encode($lesPublicitesArray);
        if(strlen(str_replace(" ", "", $user->getTiktok())) == 0 ) { $user->setTiktok(null); }
        if(strlen(str_replace(" ", "", $user->getInstagram())) == 0 ) { $user->setInstagram(null); }
        if(strlen(str_replace(" ", "", $user->getFacebook())) == 0 ) { $user->setFacebook(null); }
        if(strlen(str_replace(" ", "", $user->getYoutube())) == 0 ) { $user->setYoutube(null); }
        if(strlen(str_replace(" ", "", $user->getApropos())) == 0 ) { $user->setApropos(null); }
        return [
            "totalVues" => 0,
            "totalVuesText" => "0",
            "totalImpressions" => 0,
            "totalImpressionsText" => "0",

            "boostEnCours" => $this->verificationsDS->siBoostEnCours($this->boostRepository->findBy(['user' => $user])),
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
            "nombreContactDispo" => count($this->getAddDisponible($user)),
            "nombreContacts" => count($this->userContacts($user)),
            "lesPublicites" => $lesPublicites,
            "havePublicites" => (count($lesPublicitesArray) >= 1) ? true : false,
            "admin" => $user->getAdmin() ? true : false,
            "permissionAdd" => ($this->verificationsDS->permissionAdd($user))["permissionAdd"],
            "messageErreurPermissionAdd" => ($this->verificationsDS->permissionAdd($user))["messageErreurPermissionAdd"],
            'preferencePays' => $user->getPreference()->getPaysChoisies(),
            'addPageActu' => $user->getPreference()->getAddPageActu(),
            'isInscritProgrammeRecompense' => $user->getIsInscritProgrammeRecompense(),
            'soldeProgrammeRecompense' => $user->getSoldeProgrammeRecompense() ?? 0,
        ];
    }

    public function startPaiementFedaPay($transaction, $methodePaiementEntity) {
        if($methodePaiementEntity->isIsdirect()) {
            $token = $transaction->generateToken()->token;
            $transaction->sendNowWithToken($methodePaiementEntity->getCode(), $token);
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

    public function startPaiementFeexPay($envPaiementApi, $methodePaiementEntity, $amount, $tel, $username, $email, $transaction_for, $another_info, $user) {
        $skeleton = new FeexpayClass($envPaiementApi->getEndpointSecret(), $envPaiementApi->getApiKey(), "callback_url", $envPaiementApi->getEnvironment(), "error_callback_url");
        $reference = "";
        $url = "none";
        $customer_id = rand(111111, 999999);

        if($methodePaiementEntity->getTypeFeexPay() == "paiementLocal") {
            $response = $skeleton->paiementLocal(
                $amount,
                $tel,
                $methodePaiementEntity->getCode(),
                $username,
                $email,
                json_encode($another_info),
                $customer_id,
                ""
            );
            $reference = $response;
        }

        if($methodePaiementEntity->getTypeFeexPay() == "requestToPayWeb") {
            $response = $skeleton->requestToPayWeb(
                $amount,
                $tel,
                $methodePaiementEntity->getCode(),
                $username,
                $email,
                json_encode($another_info),
                $customer_id,
                "",
                ""
            );
            $reference = $response["reference"];
            $url = $response["payment_url"];
        }

        if($methodePaiementEntity->getTypeFeexPay() == "paiementCard") {
            $responseCard = $skeleton->paiementCard(
                $amount,
                $tel,
                $methodePaiementEntity->getCode(),
                $username,
                $username,
                $email,
                "Benin", // "country(Benin)", 
                "Cotonou", // "address(Cotonou)", 
                "Littoral", // "district(Littoral)", 
                "XOF", // "currency(XOF, USD, EUR)",
                json_encode($another_info),
                $customer_id,
            );
            $url = $responseCard["url"];
            $reference = $responseCard["reference"];
        }

        $myTransaction  = new Transaction();
        if($transaction_for == "dressur_bot_activation") {
            $myTransaction->setUserBot($user);
        } else {
            $myTransaction->setUser($user);
        }
        $myTransaction
            ->setTransactionFor($transaction_for)
            ->setIdTransaction($reference)
            ->setReference($reference)
            ->setAmount($amount)
            ->setStatus("PENDING")
            ->setCustomerId($customer_id)
            ->setCurrencyId(1)
            ->setAnnotherInfo($another_info)
        ;
        $this->em->persist($myTransaction);
        $this->em->flush();

        return [
            "error" => false,
            "direct" => $url == "none" ? true : false,
            "url" => $url,
        ];
    }

    public function getSoldeZefame() {
        return $this->zefameApi->balance()->balance;
    }

    private function calcPrixReseau(float $coutFournisseur): float
    {
        if ($coutFournisseur <= 1)  return max(round($coutFournisseur * 3, 3), 1.0);
        if ($coutFournisseur <= 5)  return round($coutFournisseur * 2,   3);
        if ($coutFournisseur <= 15) return round($coutFournisseur * 1.5, 3);
        return round($coutFournisseur * 1.3, 3);
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

            $formulePromoReseau = (new FormulePromoReseau())->setTitre("WhatsApp")->setAvailable(true);
            $this->em->persist($formulePromoReseau);

            $this->em->flush();
        }

        $lesFormulesParent = $this->formulePromoReseauRepository->findBy(['parent' => null]);
        $prixChangesText = "Prix Zefame modifiés : ";

        foreach ($this->formulePromoReseauRepository->findAll() as $uneFormuleReseau) {
            if(!empty($uneFormuleReseau->getIdZefame())) {
                $uneFormuleReseau->setAvailable(false);
            }

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
                $ancienPrix = $uneFR->getPrixZefame();
                if($ancienPrix !== null && (float)$ancienPrix != (float)$unservice->rate) {
                    $prixChangesText .= "<br> {$uneFR->getTitre()} : {$ancienPrix}€ → {$unservice->rate}€ (vente : " . $this->calcPrixReseau($unservice->rate) . "€)";
                }
                $uneFR->setAvailable(true)
                    ->setQte(1000)
                    ->setQteMin((int) $unservice->min)
                    ->setQteMax((int) $unservice->max)
                    ->setPrixZefame((float) $unservice->rate)
                    ->setPrix($this->calcPrixReseau((float) $unservice->rate))
                ;
            } else {
                $newFormuleText .= "<br> $unservice->service => $unservice->name | ... |";
                
                $newFormulePromoReseau = new FormulePromoReseau();
                $newFormulePromoReseau->setQte(1000)->setAvailable(false)
                    ->setIdZefame((int) $unservice->service)
                    ->setTitre($unservice->name)
                    ->setPrix($this->calcPrixReseau((float) $unservice->rate))
                    ->setPrixZefame((float) $unservice->rate)
                    ->setQteMin((int) $unservice->min)
                    ->setQteMax((int) $unservice->max)
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
                $this->em->persist($newFormulePromoReseau);
                // dump($newFormulePromoReseau);
                // dump($unservice);
            }
        }

        if($newFormuleText != "New : ") {
            $this->addFlash('danger', $newFormuleText);
        }
        if($prixChangesText != "Prix Zefame modifiés : ") {
            $this->addFlash('warning', $prixChangesText);
        }
        // dd("END");
        $this->em->flush();
    }

    public function checkAndUpdateStatusZefame() {
        $promoReseauStatut2 = $this->promoReseauRepository->findBy(['status' => 2]);
        foreach ($promoReseauStatut2 as $unePromoReseau) {
            if(!empty($unePromoReseau->getIdZefame())) {
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
        }
        $this->em->flush();
    }

    public function getEnvPaiementApiFedaPayDisponible() {
        $envPaiementApis = $this->envPaiementApiRepository->findBy(['activated' => true, 'aggregator' => "FedaPay"]);
        foreach ($envPaiementApis as $envPaiementApi) {
            return $envPaiementApi;
        }
        return false;
    }

    public function getEnvPaiementApiFeexPayDisponible() {
        $envPaiementApis = $this->envPaiementApiRepository->findBy(['activated' => true, 'aggregator' => "FeexPay"]);
        foreach ($envPaiementApis as $envPaiementApi) {
            return $envPaiementApi;
        }
        return false;
    }

    public function execPurge($user){
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

        $this->em->getConnection()->executeStatement(
            'DELETE FROM dsbonus_historique WHERE user_id = :id',
            ['id' => $user->getId()]
        );

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
