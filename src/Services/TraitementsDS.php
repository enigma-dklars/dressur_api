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
use App\Entity\Notification;
use App\Entity\Transaction;
use App\Repository\FormulePromoAffaireRepository;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\HistoriqueProgrammeRecompenseRepository;
use App\Repository\MethodePaiementRepository;
use App\Utilities\SendMail;
use Psr\Log\LoggerInterface;
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
    private $sendMail;
    private $logger;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, VerificationsDS $verificationsDS, BoostRepository $boostRepository, UserRepository $userRepository, SessionDS $sessionDS, DeletedDSRepository $deletedDSRepository, PreferenceRepository $preferenceRepository, TransactionRepository $transactionRepository, VerifMailRepository $verifMailRepository, SignalementRepository $signalementRepository, PromotionRepository $promotionRepository, FormulePromoReseauRepository $formulePromoReseauRepository, FormuleBoostRepository $formuleBoostRepository, FormuleDressurBotRepository $formuleDressurBotRepository, CookieDS $cookieDS, PromoReseauRepository $promoReseauRepository, SuggestionRepository $suggestionRepository, MessageRepository $messageRepository, ZefameApi $zefameApi, EnvPaiementApiRepository $envPaiementApiRepository, EnvMailSenderRepository $envMailSenderRepository, MotRefuserRepository $motRefuserRepository, FormulePromoAffaireRepository $formulePromoAffaireRepository, MethodePaiementRepository $methodePaiementRepository, HistoriqueProgrammeRecompenseRepository $historiqueProgrammeRecompenseRepository, SendMail $sendMail, LoggerInterface $logger)
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
        $this->sendMail = $sendMail;
        $this->logger = $logger;
    }

    public function migrateUidIfNeeded(\App\Entity\User $user): void
    {
        $uid = $user->getUid();
        $isUuidV4 = (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uid
        );
        if (!$isUuidV4) {
            $newUid = \App\Utilities\UuidGenerator::v4();
            $user->setUid($newUid);
            $this->em->flush();
        }
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
            $isFr = true;
            $typeBoost = $boost->getTypeBoost();
            $nbContactsObtenus = $boost->getNbContactsObtenus();
            $nbContactsMax = $boost->getFormuleBoost()->getNbContactsMax();
            $dateDebut = $boost->getDateDebut();
            $dateExp = $boost->getDateExp();
            // --- Statut ---
            $statusNumber = 1;
            $statut = "En cours";
            if ((new DateTime()) < $dateDebut) {
                $statut = "Programmé";
                $statusNumber = 2;
            } elseif ($typeBoost === 'quota') {
                if ($dateExp !== null) {
                    $statut = "Terminé";
                    $statusNumber = 3;
                }
            } else {
                if ($dateExp !== null && (new DateTime()) > $dateExp) {
                    $statut = "Terminé";
                    $statusNumber = 3;
                }
            }
            // --- Mode ---
            $prix_boost = $boost->getFormuleBoost()->getPrix() . " FCFA";
            $modeNumber = $boost->getMode() == "Gratuit" ? 1 : 2;
            $boostMode = $boost->getMode() == "Gratuit" ? "Gratuit" : "Payant";
            // --- Période / résumé selon le type ---
            if ($typeBoost === 'quota') {
                $dateDebutStr = $dateDebut->format('d-m-Y à H:i');
                $dateFinStr = $dateExp ? $dateExp->format('d-m-Y à H:i') : null;
                $periodeFormule = "depuis le " . $dateDebutStr . ($dateFinStr ? " · terminé le " . $dateFinStr : "");
            } else {
                $dateExpStr = $dateExp ? $dateExp->format('d-m-Y à H:i') : '—';
                $periodeFormule = "du " . $dateDebut->format('d-m-Y à H:i') . " au " . $dateExpStr;
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
            if ($type === 'fakeVue' && $promo->getUser()->getId() !== $user->getId()) {
                continue;
            }
            if ($user->getAdmin() === true) {
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
                $statut = "Rejeter";
            } else if($promo->getStatus() == 1) {
                $statut = "En Attente de validation";
            } else if($promo->getStatus() == 2) {
                $peutPayer = true;
                $statut = "Accepter et en attente de paiement";
            } else if($promo->getStatus() == 3) {
                $statut = "Accepter et en cours";
            } else if($promo->getStatus() == 4) {
                $peutPayer = true;
                $statut = "Terminé";
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
            if($methode->isActivated()){
                array_push($listeMethodePaiement, [
                    "id" => $methode->getId(),
                    "value" => $methode->getId(),
                    "label" => $methode->getPays()." - ".$methode->getTitre()." (".$methode->getAggregator().")",
                    "titre" => $methode->getPays()." - ".$methode->getTitre()." (".$methode->getAggregator().")",
                    "code" => $methode->getCode(),
                    "pays" => $methode->getPays(),
                    "aggregator" => $methode->getAggregator(),
                ]);
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

    public function userPromoReseaus($promos, $user = null){
        $this->checkAndUpdateStatusZefame($user);
        $userPromoReseaus = [];

        foreach ($promos as $promo) {
            $statut = "";

            if ($promo->getStatus() == 0) {
                $statut = "Mauvaise URL";
            } else if($promo->getStatus() == 1) {
                $statut = "En attente";
            } else if($promo->getStatus() == 2) {
                $statut = "En cours";
            } else if($promo->getStatus() == 3) {
                $statut = "Terminer";
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
        $user = $this->getUserByUidInCookies();
        $isVendeur = $user && $user->isVendeur();
        foreach ($this->formulePromoReseauRepository->findBy(['parent' => NULL, 'available' => true]) as $formule) {
            $lesFormulesFils = [];
            foreach ($this->formulePromoReseauRepository->findBy(['parent' => $formule, 'available' => true]) as $formuleFils) {
                $prixBase = ($isVendeur && $formuleFils->getPrixVendeur() !== null)
                    ? $formuleFils->getPrixVendeur()
                    : $formuleFils->getPrix();
                $prix_service_fcfa = $prixBase * 1.2 * 1.7 * 700;
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
        ]);

        $this->applyExpiredPromoStatusUpdates($promos);
        $this->applyUserViewTracking($user, $promos);

        foreach ($promos as $promo) {
            if ($promo->getIsFakeVue() && $promo->getUser()->getId() !== $user->getId()) {
                continue;
            }
            if ($user->getAdmin() === true) {
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

        foreach ($this->promotionRepository->findBy(["limited" => false]) as $promoVIP) {
            if ($promoVIP->getIsFakeVue() && $promoVIP->getUser()->getId() !== $user->getId()) {
                continue;
            }
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
            if ($user->getAdmin() === true) {
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
        if($user->getAdmin() === true) {
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
                                            // Bridage déterministe pour les boosts 'date'
                                            // quota → toujours visible
                                            // date payant → ~50% des combinaisons user/boosté/jour
                                            // date gratuit → ~33% des combinaisons user/boosté/jour
                                            if ($boostObj->getTypeBoost() === 'date') {
                                                $jourDuMois = (int)(new DateTime())->format('j');
                                                $hash = $user->getId() + $userBoost->getId() + $jourDuMois;
                                                if ($boostObj->getMode() === 'Gratuit') {
                                                    if ($hash % 3 !== 0) { continue; }
                                                } else {
                                                    if ($hash % 2 !== 0) { continue; }
                                                }
                                            }
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
            "avatar" => "nothing",
            "banniere" => "nothing",

            "boostEnCours" => $this->verificationsDS->siBoostEnCours($this->boostRepository->findBy(['user' => $user])),
            "myDressurVersion" => $this->env->getVersionApp(),
            "mailIsMaxxFire" => ($user->getMail() == "equipe.test.dressur.ds@gmail.com") ? true : false,
            "id" => $user->getId(),
            "uid" => $user->getUid(),
            "name_complete" => $user->__toString(),
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
            "lang" => $user->getLang() ?? 'fr',
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

    /**
     * Fait l'appel HTTP nous-mêmes (au lieu de passer par le SDK feexpay/feexpay-php)
     * afin de conserver la réponse brute complète de l'API, y compris en cas d'erreur.
     * Le SDK, lui, ne renvoie qu'un sous-ensemble de champs (ex: uniquement ->reference)
     * et jette silencieusement le reste de la réponse, ce qui rend le diagnostic impossible.
     */
    private function feexPayRawPost(string $url, array $post): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS     => http_build_query($post),
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = $curlErrno ? curl_error($ch) : null;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
        }

        return [
            "httpCode"  => $httpCode,
            "curlError" => $curlError,
            "raw"       => $raw,
            "decoded"   => $decoded,
        ];
    }

    public function startPaiementFeexPay($envPaiementApi, $methodePaiementEntity, $amount, $tel, $username, $email, $transaction_for, $another_info, $user, string $baseUrl = '') {
        $callbackUrl      = $baseUrl . '/api/wfd/' . $envPaiementApi->getRouteWebhook();
        $errorCallbackUrl = $baseUrl . '/api/wfd/error/' . $envPaiementApi->getRouteWebhook();
        $shopId           = $envPaiementApi->getEndpointSecret();
        $apiToken         = $envPaiementApi->getApiKey();

        $reference = "";
        $url = "none";
        $customer_id = rand(111111, 999999);
        $typeFeexPay = $methodePaiementEntity->getTypeFeexPay();
        $rawResponse = null;

        if ($typeFeexPay == "paiementLocal") {
            $rawResponse = $this->feexPayRawPost(
                "https://api.feexpay.me/api/transactions/requesttopay/integration",
                [
                    "phoneNumber"   => $tel,
                    "amount"        => $amount,
                    "reseau"        => $methodePaiementEntity->getCode(),
                    "token"         => $apiToken,
                    "shop"          => $shopId,
                    "first_name"    => $username,
                    "email"         => $email,
                    "callback_info" => json_encode($another_info),
                    "reference"     => (string)$customer_id,
                    "otp"           => "",
                ]
            );
            $reference = $rawResponse["decoded"]["reference"] ?? null;
        } elseif ($typeFeexPay == "requestToPayWeb") {
            $rawResponse = $this->feexPayRawPost(
                "https://api.feexpay.me/api/transactions/requesttopay/integration",
                [
                    "phoneNumber"   => $tel,
                    "amount"        => $amount,
                    "reseau"        => $methodePaiementEntity->getCode(),
                    "token"         => $apiToken,
                    "shop"          => $shopId,
                    "first_name"    => $username,
                    "email"         => $email,
                    "callback_info" => json_encode($another_info),
                    "reference"     => (string)$customer_id,
                    "return_url"    => "",
                    "cancel_url"    => "",
                ]
            );
            $reference = $rawResponse["decoded"]["reference"] ?? null;
            $url = $rawResponse["decoded"]["payment_url"] ?? "none";
        } elseif ($typeFeexPay == "paiementCard") {
            $rawResponse = $this->feexPayRawPost(
                "https://api.feexpay.me/api/transactions/card/inittransact/integration",
                [
                    "phone"         => $tel,
                    "amount"        => $amount,
                    "reseau"        => $methodePaiementEntity->getCode(),
                    "token"         => $apiToken,
                    "shop"          => $shopId,
                    "first_name"    => $username,
                    "last_name"     => $username,
                    "email"         => $email,
                    "country"       => "Benin",
                    "address1"      => "Cotonou",
                    "district"      => "Littoral",
                    "currency"      => "XOF",
                    "callback_info" => json_encode($another_info),
                    "reference"     => (string)$customer_id,
                ]
            );
            $url = $rawResponse["decoded"]["url"] ?? "none";
            $reference = $rawResponse["decoded"]["reference"] ?? null;
        } else {
            throw new \RuntimeException("typeFeexPay inconnu : " . $typeFeexPay);
        }

        if (empty($reference)) {
            $debugContext = [
                "typeFeexPay"     => $typeFeexPay,
                "amount"          => $amount,
                "tel"             => $tel,
                "username"        => $username,
                "email"           => $email,
                "transaction_for" => $transaction_for,
                "methodeCode"     => $methodePaiementEntity->getCode(),
                "environment"     => $envPaiementApi->getEnvironment(),
                "routeWebhook"    => $envPaiementApi->getRouteWebhook(),
                "callbackUrl"     => $callbackUrl,
                "httpCode"        => $rawResponse["httpCode"] ?? null,
                "curlError"       => $rawResponse["curlError"] ?? null,
                "rawResponseBody" => $rawResponse["raw"] ?? null,
                "decodedResponse" => $rawResponse["decoded"] ?? null,
            ];
            $debugMessage = "FeexPay n'a retourné aucune référence de transaction.<br><br>"
                . "<pre>" . htmlspecialchars(json_encode($debugContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

            $this->logger->error("FeexPay: aucune reference retournee", $debugContext);

            try {
                $this->sendMail->sendReport("Echec paiement FeexPay - aucune reference", $debugMessage);
            } catch (\Throwable $mailError) {
                $this->logger->error("FeexPay: echec envoi mail de rapport", ["error" => $mailError->getMessage()]);
            }

            throw new \RuntimeException("FeexPay n'a retourné aucune référence de transaction.");
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
            ->setStatus("pending")
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

    private function calcPrixVendeur(float $coutFournisseur): float
    {
        if ($coutFournisseur <= 1)  return max(round($coutFournisseur * 2.7, 3), 0.9);
        if ($coutFournisseur <= 5)  return round($coutFournisseur * 1.8,  3);
        if ($coutFournisseur <= 15) return round($coutFournisseur * 1.35, 3);
        return round($coutFournisseur * 1.17, 3);
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
                    ->setPrixVendeur($this->calcPrixVendeur((float) $unservice->rate))
                ;
            } else {
                $newFormuleText .= "<br> $unservice->service => $unservice->name | ... |";
                
                $newFormulePromoReseau = new FormulePromoReseau();
                $newFormulePromoReseau->setQte(1000)->setAvailable(false)
                    ->setIdZefame((int) $unservice->service)
                    ->setTitre($unservice->name)
                    ->setPrix($this->calcPrixReseau((float) $unservice->rate))
                    ->setPrixZefame((float) $unservice->rate)
                    ->setPrixVendeur($this->calcPrixVendeur((float) $unservice->rate))
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

    public function checkAndUpdateStatusZefame($user = null) {
        $criteria = ['status' => 2];
        if ($user !== null) {
            $criteria['user'] = $user;
        }
        $promoReseauStatut2 = $this->promoReseauRepository->findBy($criteria);
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

    public function getEnvPaiementApiKPayDisponible() {
        $envPaiementApis = $this->envPaiementApiRepository->findBy(['activated' => true, 'aggregator' => "KPay"]);
        foreach ($envPaiementApis as $envPaiementApi) {
            return $envPaiementApi;
        }
        return false;
    }

    /**
     * Appel HTTP dédié à KPay (indépendant du helper feexPayRawPost : gardé séparé
     * volontairement pour ne pas coupler les deux intégrations). KPay attend un corps
     * JSON avec authentification par headers X-API-Key / X-Secret-Key, contrairement à
     * FeexPay qui attend un corps x-www-form-urlencoded.
     */
    private function kpayRawPost(string $url, array $headers, array $jsonBody): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => json_encode($jsonBody),
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $raw = curl_exec($ch);
        $curlErrno = curl_errno($ch);
        $curlError = $curlErrno ? curl_error($ch) : null;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = null;
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
        }

        return [
            "httpCode"  => $httpCode,
            "curlError" => $curlError,
            "raw"       => $raw,
            "decoded"   => $decoded,
        ];
    }

    /**
     * Paiement KPay en mode GATEWAY uniquement (page de paiement hébergée par KPay,
     * le client choisit lui-même son opérateur/numéro). Le webhook POST reste la
     * source de vérité pour la confirmation ; les URLs de retour ne servent qu'à
     * afficher un message immédiat au client dans son navigateur.
     */
    public function startPaiementKPay($envPaiementApi, $methodePaiementEntity, $amount, $tel, $username, $email, $transaction_for, $another_info, $user, string $baseUrl = '') {
        $customer_id = rand(111111, 999999);
        $externalId  = (string)$customer_id;

        $returnUrl = $baseUrl . '/api/wkp-return/' . $envPaiementApi->getRouteWebhook() . '?externalId=' . $externalId;
        $cancelUrl = $baseUrl . '/api/wkp-cancel/' . $envPaiementApi->getRouteWebhook() . '?externalId=' . $externalId;

        $rawResponse = $this->kpayRawPost(
            "https://admin.kpay.site/api/v1/payments/init",
            [
                "X-API-Key: " . $envPaiementApi->getApiKey(),
                "X-Secret-Key: " . $envPaiementApi->getEndpointSecret(),
                "Content-Type: application/json",
            ],
            [
                "amount"      => $amount,
                "externalId"  => $externalId,
                "returnUrl"   => $returnUrl,
                "cancelUrl"   => $cancelUrl,
                "description" => "Dressur : paiement " . $transaction_for,
            ]
        );

        $decoded    = $rawResponse["decoded"];
        $gatewayUrl = $decoded["gatewayUrl"] ?? null;
        $reference  = $decoded["reference"] ?? null;

        if (empty($gatewayUrl) || empty($reference)) {
            $debugContext = [
                "aggregator"      => "KPay",
                "amount"          => $amount,
                "tel"             => $tel,
                "username"        => $username,
                "email"           => $email,
                "transaction_for" => $transaction_for,
                "methodeCode"     => $methodePaiementEntity->getCode(),
                "environment"     => $envPaiementApi->getEnvironment(),
                "routeWebhook"    => $envPaiementApi->getRouteWebhook(),
                "returnUrl"       => $returnUrl,
                "httpCode"        => $rawResponse["httpCode"],
                "curlError"       => $rawResponse["curlError"],
                "rawResponseBody" => $rawResponse["raw"],
                "decodedResponse" => $decoded,
            ];
            $debugMessage = "KPay n'a retourné aucune URL de paiement (gatewayUrl) ou reference.<br><br>"
                . "<pre>" . htmlspecialchars(json_encode($debugContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";

            $this->logger->error("KPay: aucune gatewayUrl/reference retournee", $debugContext);

            try {
                $this->sendMail->sendReport("Echec paiement KPay - aucune gatewayUrl", $debugMessage);
            } catch (\Throwable $mailError) {
                $this->logger->error("KPay: echec envoi mail de rapport", ["error" => $mailError->getMessage()]);
            }

            throw new \RuntimeException("KPay n'a retourné aucune URL de paiement.");
        }

        $myTransaction = new Transaction();
        if ($transaction_for == "dressur_bot_activation") {
            $myTransaction->setUserBot($user);
        } else {
            $myTransaction->setUser($user);
        }
        $myTransaction
            ->setTransactionFor($transaction_for)
            ->setIdTransaction($reference)
            ->setReference($reference)
            ->setAmount($amount)
            ->setStatus("pending")
            ->setCustomerId($customer_id)
            ->setCurrencyId(1)
            ->setAnnotherInfo($another_info)
        ;
        $this->em->persist($myTransaction);
        $this->em->flush();

        return [
            "error"  => false,
            "direct" => false,
            "url"    => $gatewayUrl,
        ];
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

        try {
            $this->em->getConnection()->executeStatement(
                'DELETE FROM dsbonus_historique WHERE user_id = :id',
                ['id' => $user->getId()]
            );
        } catch (\Throwable $e) {
            // Table absente en dev — on ignore et on continue la purge
        }

        $deletedDS = new DeletedDS();
        $deletedDS->setMail($user->getMail())
            ->setTel($user->getTel())
            ->setMotif("GET OUT BY ADMIN")
        ;
        $this->em->persist($deletedDS);

        $this->em->remove($user);

        $this->em->flush();
    }

    function genererMotAleatoire(int $longueur): string
    {
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $mot = '';

        for ($i = 0; $i < $longueur; $i++) {
            $mot .= $caracteres[random_int(0, strlen($caracteres) - 1)];
        }

        return $mot;
    }

    function addNotification(string $text, $user = null) {
        $newNotif = new Notification();
        $newNotif->setText($text)->setCreatedAt(new DateTime());
        if($user) {
            $newNotif->setUser($user);
        }
        $this->em->persist($newNotif);
    }
}
