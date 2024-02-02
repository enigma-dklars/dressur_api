<?php

namespace App\Services;

use DateTime;
use App\Repository\UserRepository;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use libphonenumber\PhoneNumberFormat;
use App\Repository\MotRefuserRepository;
use libphonenumber\NumberParseException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class VerificationsDS extends AbstractController
{
    private $motRefusers;
    private $userRepository;
    private $sessionDS;

    public function __construct(MotRefuserRepository $motRefuserRepository, UserRepository $userRepository, SessionDS $sessionDS)
    {
        $this->motRefusers = $motRefuserRepository->findAll();
        $this->userRepository = $userRepository;
        $this->sessionDS = $sessionDS;
    }

    public function remove_emoji($text){
        $string = str_replace("?", "{%}", $text);
        $string = mb_convert_encoding($string, "ISO-8859-1", "UTF-8");
        $string = mb_convert_encoding($string, "UTF-8", "ISO-8859-1");
        $string = str_replace(array("?", "? ", " ?"), array(""), $string);
        $string = str_replace("{%}", "?", $string);
        return trim($string);
    }

    public function siBoostEnCours($boosts){
        foreach ($boosts as $boost) {
            if((new DateTime()) < $boost->getDateExp()){
                return true;
            }
        }
        return false;
    }

    public function messageErreurPermissionAdd($nombreContactAdd, $nombreFilleuls, $max){
        $max = $max + 1;
        if($this->sessionDS->get("langUserPhone") != "fr") {
            return "You have already added $nombreContactAdd contacts DS. You must have at least $max referrals to be able to continue adding new contacts. Currently, you have $nombreFilleuls godson(s).\n\nWhatsPerson is a community and we cannot let you benefit from its advantages without contributing to its evolution.\n\nTo contribute to its evolution, you must bring others people to use the application. Each user/contact you register came through a user.\n\nAs you will not be able to reach the requested number of referrals, do a Paid Contact Boost for users to add you. THANKS.";
        }
        return "Vous avez déjà ajouté $nombreContactAdd contacts DS. Il vous faut avoir au minimum $max filleuls pour pouvoir continuer à ajouter les nouveaux contacts. Actuellement, vous avez $nombreFilleuls filleul(s).\n\nWhatsPerson est une communauté et nous ne pouvons pas vous laisser profité de ses avantages sans contribuer à son évolution.\n\nPour contribuer à son évolution, vous devez amener d'autres personnes à utiliser l'application. Chaque utilisateur/contact que vous enregistrez sont venus par le biais d'un utilisateur.\n\nDans la mesure ou vous ne serez pas capable d'atteindre le nombre de filleuls demandé, faite un Boost Contact Payant pour que les utilisateurs vous ajoute. MERCI.";
    }

    public function permissionAdd($user)
    {
        $nombreFilleuls = count($user->getFilleuls());
        $nombreContactAdd = count($user->getContact()->getWhoIAdd());
        if($nombreFilleuls >= 0 and $nombreFilleuls <= 9){
            if($nombreContactAdd < 100){
                return [
                    "permissionAdd" => true,
                    "messageErreurPermissionAdd" => "WhatsPerson",
                ];
            } else {
                return [
                    "permissionAdd" => false,
                    "messageErreurPermissionAdd" => $this->messageErreurPermissionAdd($nombreContactAdd, $nombreFilleuls, 9),
                ];
            }
        }

        if($nombreFilleuls >= 10 and $nombreFilleuls <= 19){
            if($nombreContactAdd < 200){
                return [
                    "permissionAdd" => true,
                    "messageErreurPermissionAdd" => "WhatsPerson",
                ];
            } else {
                return [
                    "permissionAdd" => false,
                    "messageErreurPermissionAdd" => $this->messageErreurPermissionAdd($nombreContactAdd, $nombreFilleuls, 19),
                ];
            }
        }

        if($nombreFilleuls >= 20 and $nombreFilleuls <= 29){
            if($nombreContactAdd < 300){
                return [
                    "permissionAdd" => true,
                    "messageErreurPermissionAdd" => "WhatsPerson",
                ];
            } else {
                return [
                    "permissionAdd" => false,
                    "messageErreurPermissionAdd" => $this->messageErreurPermissionAdd($nombreContactAdd, $nombreFilleuls, 29),
                ];
            }
        }

        if($nombreFilleuls >= 30 and $nombreFilleuls <= 39){
            if($nombreContactAdd < 400){
                return [
                    "permissionAdd" => true,
                    "messageErreurPermissionAdd" => "WhatsPerson",
                ];
            } else {
                return [
                    "permissionAdd" => false,
                    "messageErreurPermissionAdd" => $this->messageErreurPermissionAdd($nombreContactAdd, $nombreFilleuls, 39),
                ];
            }
        }

        if($nombreFilleuls >= 40 and $nombreFilleuls <= 49){
            if($nombreContactAdd < 500){
                return [
                    "permissionAdd" => true,
                    "messageErreurPermissionAdd" => "WhatsPerson",
                ];
            } else {
                return [
                    "permissionAdd" => false,
                    "messageErreurPermissionAdd" => $this->messageErreurPermissionAdd($nombreContactAdd, $nombreFilleuls, 49),
                ];
            }
        }

        if($nombreFilleuls >= 50 and $nombreFilleuls <= 59){
            if($nombreContactAdd < 600){
                return [
                    "permissionAdd" => true,
                    "messageErreurPermissionAdd" => "WhatsPerson",
                ];
            } else {
                return [
                    "permissionAdd" => false,
                    "messageErreurPermissionAdd" => $this->messageErreurPermissionAdd($nombreContactAdd, $nombreFilleuls, 59),
                ];
            }
        }

        if($nombreFilleuls >= 60 and $nombreFilleuls <= 69){
            if($nombreContactAdd < 700){
                return [
                    "permissionAdd" => true,
                    "messageErreurPermissionAdd" => "WhatsPerson",
                ];
            } else {
                return [
                    "permissionAdd" => false,
                    "messageErreurPermissionAdd" => $this->messageErreurPermissionAdd($nombreContactAdd, $nombreFilleuls, 69),
                ];
            }
        }

        if($nombreFilleuls >= 70 and $nombreFilleuls <= 79){
            if($nombreContactAdd < 800){
                return [
                    "permissionAdd" => true,
                    "messageErreurPermissionAdd" => "WhatsPerson",
                ];
            } else {
                return [
                    "permissionAdd" => false,
                    "messageErreurPermissionAdd" => $this->messageErreurPermissionAdd($nombreContactAdd, $nombreFilleuls, 79),
                ];
            }
        }

        if($nombreFilleuls >= 80 and $nombreFilleuls <= 89){
            if($nombreContactAdd < 900){
                return [
                    "permissionAdd" => true,
                    "messageErreurPermissionAdd" => "WhatsPerson",
                ];
            } else {
                return [
                    "permissionAdd" => false,
                    "messageErreurPermissionAdd" => $this->messageErreurPermissionAdd($nombreContactAdd, $nombreFilleuls, 89),
                ];
            }
        }

        if($nombreFilleuls >= 90 and $nombreFilleuls <= 99){
            if($nombreContactAdd < 1000){
                return [
                    "permissionAdd" => true,
                    "messageErreurPermissionAdd" => "WhatsPerson",
                ];
            } else {
                return [
                    "permissionAdd" => false,
                    "messageErreurPermissionAdd" => $this->messageErreurPermissionAdd($nombreContactAdd, $nombreFilleuls, 99),
                ];
            }
        }

        if($nombreFilleuls >= 100 and $nombreFilleuls <= 109){
            return [
                "permissionAdd" => true,
                "messageErreurPermissionAdd" => "WhatsPerson",
            ];
        }

    }

    public function verifMail($mail){        
        if(filter_var($mail, FILTER_VALIDATE_EMAIL)){
            return true;
        }
        return false;
    }

    public function verifFormatNumTel($tel){
        $phoneNumberUtil = PhoneNumberUtil::getInstance();
        try {
            $parsedNumber = $phoneNumberUtil->parse($tel, 'ZZ');
        } catch (NumberParseException $e) {
            return [
                'error' => true, 
                'message' => $e->getMessage()
            ];
        }
        return [
            'error' => false,
            'region_code' => $phoneNumberUtil->getRegionCodeForNumber($parsedNumber),
            'country_code' => $parsedNumber->getCountryCode(),
            'nationalNumber' => $parsedNumber->getNationalNumber(),
            'e164' => $phoneNumberUtil->format($parsedNumber, PhoneNumberFormat::E164),
            'number_type' => $phoneNumberUtil->getNumberType($parsedNumber),
            'is_valid' => $phoneNumberUtil->isValidNumber($parsedNumber),
            'message' => '',
        ];
    }

    public function verifPseudo($pseudo){
        $slugger = new AsciiSlugger();
        $pseudo = strtolower((string)$slugger->slug($pseudo));
        $pseudoSansChiffre = $pseudo;

        for ($i=0; $i <= 9; $i++) { 
            $pseudoSansChiffre = str_replace($i, '', $pseudoSansChiffre);
        }

        if(strlen($pseudoSansChiffre) <= 1){
            if($this->sessionDS->get("langUserPhone") != "fr") {
                return [
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Your Nickname contains forbidden words...',
                ];
            }
            return [
                'error' => true,
                'titre' => 'Refus!',
                'message' => "Votre Pseudo doit contenir au minimum deux lettres.",
            ];
        }

        if(strlen($pseudo) < 3) {
            if($this->sessionDS->get("langUserPhone") != "fr") {
                return [
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Nickname too short! 3 characters minimum...',
                ];
            }
            return [
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Pseudo trop court! 3 caractères minimum...',
            ];
        }

        if(strlen($pseudo) > 10){
            if($this->sessionDS->get("langUserPhone") != "fr") {
                return [
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Nickname too long! 10 characters maximum...',
                ];
            }
            return [
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Pseudo trop long! 10 caractères maximum...',
            ];
        }

        foreach ($this->motRefusers as $mot) {
            $pseudoTester = str_replace($mot->getMot(), '', $pseudo);
            if(strlen($pseudoTester) < strlen($pseudo)) {
                if($this->sessionDS->get("langUserPhone") != "fr") {
                    return [
                        'error' => true,
                        'titre' => 'Attention!',
                        'message' => 'Your Nickname contains forbidden words...',
                    ];
                }
                return [
                    'error' => true,
                    'titre' => 'Refus!',
                    'message' => 'Votre Pseudo contient des mots proscrits...',
                ];
            }
        }

        // si tous est bon
        return [
            'error' => false,
            'pseudo' => $pseudo,
        ];
    }

    public function verifUSer($uid) {
        $user = $this->userRepository->findOneBy(['uid' => $uid]);
        if(!$user) {
            if($this->sessionDS->get("langUserPhone") != "fr") {
                return [
                    'error' => true,
                    'deleted' => true,
                    'blocked' => false,
                    'titre' => 'Mistake!',
                    'message' => 'This account no longer exists.',
                ];
            }
            return [
                'error' => true,
                'deleted' => true,
                'blocked' => false,
                'titre' => 'Erreur!',
                'message' => "Ce compte n'existe plus.",
            ];
        }
        if($user->getBlocked() == true) {
            if($this->sessionDS->get("langUserPhone") != "fr") {
                return [
                    'error' => true,
                    'deleted' => false,
                    'blocked' => true,
                    'titre' => 'Mistake!',
                    'message' => 'This account has been blocked after several reports.\nIf this is an error, please contact WhatsPerson Support by WhatsApp.',
                ];
            }
            return [
                'error' => true,
                'deleted' => false,
                'blocked' => true,
                'titre' => 'Erreur!',
                'message' => "Ce compte à été bloquer après plusieurs signalements.\nS'il s'agit d'une erreur, veuillez contactez l'Assistance par WhatsApp.",
            ];
        }

        // si tous est bon
        return [
            'error' => false,
            'user' => $user,
        ];
    }
}
