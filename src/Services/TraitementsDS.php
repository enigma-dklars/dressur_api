<?php

namespace App\Services;

use DateTime;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use App\Repository\BoostRepository;
use App\Repository\DSBonusRepository;
use App\Controller\API\BoostController;
use App\Repository\DeletedDSRepository;
use App\Repository\PromotionRepository;
use App\Repository\VerifMailRepository;
use App\Repository\PreferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\API\ContactController;
use App\Repository\SignalementRepository;
use App\Repository\TransactionRepository;
use App\Repository\DSBonusHistoriqueRepository;
use App\Controller\API\UserPreferenceController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class TraitementsDS extends AbstractController
{
    private $em;
    private $env;
    private $verificationsDS;
    private $wPBonusHistoriqueRepository;
    private $boostRepository;
    private $wPBonusRepository;
    private $userRepository;
    private $sessionDS;
    private $preferenceRepository;
    private $transactionRepository;
    private $verifMailRepository;
    private $signalementRepository;
    private $promotionRepository;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, VerificationsDS $verificationsDS, DSBonusHistoriqueRepository $wPBonusHistoriqueRepository, BoostController $boostController, UserPreferenceController $userPreferenceController, ContactController $contactController,  BoostRepository $boostRepository, DSBonusRepository $wPBonusRepository, UserRepository $userRepository,  SessionDS $sessionDS, DeletedDSRepository $deletedDSRepository, PreferenceRepository $preferenceRepository, TransactionRepository $transactionRepository, VerifMailRepository $verifMailRepository, SignalementRepository $signalementRepository, PromotionRepository $promotionRepository)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->verificationsDS = $verificationsDS; 
        $this->wPBonusHistoriqueRepository = $wPBonusHistoriqueRepository;
        $this->boostRepository = $boostRepository;
        $this->wPBonusRepository = $wPBonusRepository;
        $this->userRepository = $userRepository;
        $this->sessionDS = $sessionDS;
        $this->preferenceRepository = $preferenceRepository;
        $this->transactionRepository = $transactionRepository;
        $this->verifMailRepository = $verifMailRepository;
        $this->signalementRepository = $signalementRepository;
        $this->promotionRepository = $promotionRepository;
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
    
    public function userBoosts($boosts) {
        $userBoosts = [];
        $prix_boost = "";
        $statut = "";
        $dejaUnBoostEncours = 0;

        foreach ($boosts as $boost) {
            if($this->sessionDS->get("langUserPhone") != "fr") {
                $statut = "In progress 🚀";
            } else {
                $statut = "En cours 🚀";
            }

            if((new DateTime()) < $boost->getDateExp()){
                $dejaUnBoostEncours++;
                if ($dejaUnBoostEncours > 1) {
                    if($this->sessionDS->get("langUserPhone") != "fr") {
                        $statut = "scheduled 💤";
                    } else {
                        $statut = "Programmé 💤";
                    }
                }
            }
            

            if((new DateTime()) > $boost->getDateExp()){
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "Completed ✅";
                } else {
                    $statut = "Terminé ✅";
                }
            }

            if($boost->getMode() == "Gratuit") {
                $prix_boost = $boost->getFormuleBoost()->getPrix(). "DS";
            } else {
                $prix_boost = $boost->getFormuleBoost()->getPrix(). "FCFA";
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
                ];
            } else {
                $unBoost = [
                    'id' => (string)$boost->getId(),
                    'nomFormule' => $boost->getFormuleBoost()->getTitre(),
                    'dateDebutFormule' => "du ".($boost->getDateDebut())->format('d-m-Y à H:i')." au ".($boost->getDateExp())->format('d-m-Y à H:i'),
                    'prixFormule' => (string)$prix_boost,
                    'statutFormule' => $statut,
                    'modeBoostFormule' => $boost->getMode(),
                ];
            }
            array_push($userBoosts, $unBoost);
        }
        $userBoosts = array_reverse($userBoosts);
        return $userBoosts;
    }

    public function userPromos($promos){
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
                    $statut = "Accept and already pay";
                } else {
                    $statut = "Accepter et déja payer";
                }
            }

            $unePromo = [
                "id" => (string)$promo->getId(),
                "peutPayer" => $peutPayer,
                "image" => $promo->getImage(),
                "nombreDeVues" => (string)$this->formatNumber($promo->getNombreDeVue()),
                "nombreImpression" => (string)$this->formatNumber($promo->getNombreImpression()),
                "description" => $promo->getDescription(),
                "status" => $statut,
                "dateDebut" => $promo->getDateDebut() ? ($promo->getDateDebut())->format('d-m-Y à H:i') : "",
                "dateExp" => $promo->getDateExp() ? ($promo->getDateExp())->format('d-m-Y à H:i') : "",
                "formulePromotion" => $promo->getFormuleBoost() ? $promo->getFormuleBoost()->getTitre() : "",
            ];
            array_push($userPromos, $unePromo);
        }

        $this->em->flush();
        $userPromos = array_reverse($userPromos);
        return $userPromos;
    }

    public function userCampagneMail($campagneMails) {
        $userCampagneMail = [];

        foreach ($campagneMails as $campagneMail) {
            $statut = "";
            $peutPayer = false;

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
                    $statut = "Paying and processing";
                } else {
                    $statut = "Payer et en cours de traitement";
                }
            } else if($campagneMail->getStatus() == 4) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    $statut = "To end";
                } else {
                    $statut = "Terminer";
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
                "status" => $statut,
                "peutPayer" => $peutPayer,
                "createdAt" => $campagneMail->getCreatedAt() ? ($campagneMail->getCreatedAt())->format('d-m-Y à H:i') : "",
            ];
            array_push($userCampagneMail, $unecampagneMail);
        }
        $userCampagneMail = array_reverse($userCampagneMail);
        return $userCampagneMail;
    }

    public function listePubliciteAffichageAuxUsers($user){
        $listePubliciteAffichageAuxUsers = [];

        $promos = $this->promotionRepository->findBy([
            "status" => 3,
            "limited" => true,
        ]);
        foreach ($promos as $promo) {
            if(in_array($user->getPays(), $promo->getUser()->getPreference()->getPaysChoisies())) {
                if((new DateTime()) >= ($promo->getDateDebut()) and (new DateTime()) <= ($promo->getDateExp())){
                    $promo->setToWatch();
                    $unePromo = [
                        "uidUser" => $promo->getUser()->getUid(),
                        "id" => $promo->getId(),
                        "image" => $promo->getImage(),
                        "description" => $promo->getDescription(),
                        "whatsappNumber" => $promo->getUser()->getTel(),
                        "pseudoAnnonceur" => $promo->getUser()->getPseudo(),
                    ];
                    array_push($listePubliciteAffichageAuxUsers, $unePromo);
                }
            }
        }

        foreach ($this->promotionRepository->findBy(["limited" => false]) as $promoVIP) {
            array_push($listePubliciteAffichageAuxUsers, [
                "id" => $promoVIP->getId(),
                "image" => $promoVIP->getImage(),
                "description" => $promoVIP->getDescription(),
                "whatsappNumber" => $promoVIP->getUser()->getTel(),
                "pseudoAnnonceur" => $promoVIP->getUser()->getPseudo(),
            ]);
        }
        $this->em->flush();
        return $listePubliciteAffichageAuxUsers;
    }

    public function userContacts($user){
        $userContacts = [];
        foreach ($user->getContact()->getAllIdOfMyContacts() as $key => $idContact){
            $contact = $this->userRepository->find($idContact);
            $unContact = [
                "id" => (string)$contact->getUid(),
                "pseudo" => $contact->getPseudo(),
                "nom" => $contact->getNom() ? $contact->getNom() : "",
                "afficheNom" => $contact->getPreference()->getAffNom(),
                "mail" => $contact->getMail(),
                "pays" => (string)$contact->getPays(),
                "tel" => $contact->getTel(),
                "apropos" => $contact->getApropos() ? $contact->getApropos() : "",
                "tiktok" => $contact->getTiktok() ? $contact->getTiktok() : "",
                "instagram" => $contact->getInstagram() ? $contact->getInstagram() : "",
                "facebook" => $contact->getFacebook() ? $contact->getFacebook() : "",
                "youtube" => $contact->getYoutube() ? $contact->getYoutube() : "",
            ];
            array_push($userContacts, $unContact);
        }
        return $userContacts;
    }

    public function generateUserContactAdd($contacts){
        $contactAdds = [];
        foreach ($contacts as $contact) {
            $user = $this->userRepository->find($contact["id"]);
            if($user->getPreference()->getAffNom() == true){
                $nom = $user->getPseudo()." ".$nom = $user->getNom();
            } else {
                $nom = $user->getPseudo();
            }
            array_push($contactAdds, [
                'idContact' => $contact["idContact"],
                'nomAdd' => $nom,
                'telAdd' => $user->getTel(),
            ]);
        }
        return $contactAdds;
    }

    public function getAddDisponible($user){
        $contacts = [];
        foreach ($user->getPreference()->getPaysChoisies() as $codePays){
            $boosts = $this->boostRepository->getBoostAndUser($codePays);
            foreach ($boosts as $boost){
                $userBoost = $boost["boost"]->getUser();
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
                                    'tel' => $userBoost->getTel(),
                                ]);
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
            if($contact->getPreference()->getAffNom() == true){
                $nom = $contact->getPseudo()." ".$nom = $contact->getNom();
            } else {
                $nom = $contact->getPseudo();
            }
            if(strlen($nom) == 0){ $nom = "chere utilisateur";}
            array_push($contactAdds, [
                'idContact' => $contact->getId(),
                'nomAdd' => $nom,
                'telAdd' => $contact->getTel(),
            ]);
        }
        return $contactAdds;
    }

    public function mergePaysDisponibleAndUserPreferencePays($userPreference)
    {
        $pays = [];
        foreach ($this->userRepository->getPaysDistinct() as $paysDisponible) {

            $arrayPaysChoisie = $userPreference->getPaysChoisies();
            $isExiste = in_array($paysDisponible["pays"], $arrayPaysChoisie);
            if($isExiste){
                array_push($pays, [
                    'name' => (string)$paysDisponible["pays"],
                    'isSelected' => true,
                ]);
            } else {
                array_push($pays, [
                    'name' => (string)$paysDisponible["pays"],
                    'isSelected' => false,
                ]);
            }
        }
        return $pays;
    }

    public function preferencePaysText($userPreference)
    {
        $pays = "";
        // $pays = implode(', ', $userPreference->getPaysChoisies());

        foreach ($this->userRepository->getPaysDistinct() as $paysDisponible) {
            
            $arrayPaysChoisie = $userPreference->getPaysChoisies();
            
            $isExiste = in_array($paysDisponible["pays"], $arrayPaysChoisie);
            if($isExiste){
                if($pays == ""){
                    $pays .= $paysDisponible["pays"];
                } else {
                    $pays .= ", ".$paysDisponible["pays"];
                }
            }
        }
        if($pays == ""){
            if($this->sessionDS->get("langUserPhone") != "fr") {
                $pays .= "No Country Selected";
            } else {
                $pays .= "Aucun Pays Choisi";
            }
        }
        return $pays;
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
        $lesPublicitesArray = $this->listePubliciteAffichageAuxUsers($user);
        $lesPublicites = json_encode($lesPublicitesArray);
        return [
            "id" => $user->getId(),
            "uid" => $user->getUid(),
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
            "affUserName" => $user->getPreference()->getAffNom() ? true : false,
            "nombreFilleuls" => count($user->getFilleuls()),
            "admin" => $user->getAdmin() ? true : false,
            "permissionAdd" => ($this->verificationsDS->permissionAdd($user))["permissionAdd"],
            "messageErreurPermissionAdd" => ($this->verificationsDS->permissionAdd($user))["messageErreurPermissionAdd"],
            "commissionBonus" => $this->env->getCommissionBonus(),
            'preferencePaysText' => $this->preferencePaysText($user->getPreference()),
        ];
    }

    public function execPurge($user){
        foreach ($this->userRepository->findBy(['parrain' => $user]) as $element) {
            $element->setParrain($this->userRepository->find(1));
            $this->em->flush();
        }

        foreach ($this->boostRepository->findBy(['user' => $user]) as $element) {
            $this->boostRepository->remove($element, true);
        }

        foreach ($this->transactionRepository->findBy(['user' => $user]) as $element) {
            $this->transactionRepository->remove($element, true);
        }

        foreach ($this->verifMailRepository->findBy(['user' => $user]) as $element) {
            $this->verifMailRepository->remove($element, true);
        }

        foreach ($this->wPBonusRepository->findBy(['user' => $user]) as $element) {
            $this->wPBonusRepository->remove($element, true);
        }

        foreach ($this->wPBonusHistoriqueRepository->findBy(['user' => $user]) as $element) {
            $this->wPBonusHistoriqueRepository->remove($element, true);
        }

        foreach ($this->signalementRepository->findBy(['signaler' => $user]) as $element) {
            $this->signalementRepository->remove($element, true);
        }

        $this->userRepository->remove($user, true);
    }   
}