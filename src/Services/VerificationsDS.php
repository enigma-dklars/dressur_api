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
    private $motRefuserRepository;
    private ?array $motRefusers = null;
    private $userRepository;
    private $sessionDS;

    public function __construct(MotRefuserRepository $motRefuserRepository, UserRepository $userRepository, SessionDS $sessionDS)
    {
        $this->motRefuserRepository = $motRefuserRepository;
        $this->userRepository = $userRepository;
        $this->sessionDS = $sessionDS;
    }

    private function getMotRefusers(): array
    {
        if ($this->motRefusers === null) {
            $this->motRefusers = $this->motRefuserRepository->findAll();
        }
        return $this->motRefusers;
    }

    public function remove_emoji($text){
        $string = (string) $text;
        $string = preg_replace('/[\x{1F600}-\x{1F64F}]/u', '', $string);
        $string = preg_replace('/[\x{1F300}-\x{1F5FF}]/u', '', $string);
        $string = preg_replace('/[\x{1F680}-\x{1F6FF}]/u', '', $string);
        $string = preg_replace('/[\x{1F700}-\x{1F77F}]/u', '', $string);
        $string = preg_replace('/[\x{1F780}-\x{1F7FF}]/u', '', $string);
        $string = preg_replace('/[\x{1F800}-\x{1F8FF}]/u', '', $string);
        $string = preg_replace('/[\x{1F900}-\x{1F9FF}]/u', '', $string);
        $string = preg_replace('/[\x{1FA00}-\x{1FA6F}]/u', '', $string);
        $string = preg_replace('/[\x{1FA70}-\x{1FAFF}]/u', '', $string);
        $string = preg_replace('/[\x{2600}-\x{26FF}]/u', '', $string);
        $string = preg_replace('/[\x{2700}-\x{27BF}]/u', '', $string);
        $string = preg_replace('/[\x{FE00}-\x{FE0F}]/u', '', $string);
        $string = preg_replace('/[\x{1F1E0}-\x{1F1FF}]/u', '', $string);
        $string = preg_replace('/[\x{200D}]/u', '', $string);
        $string = preg_replace('/[\x{20E3}]/u', '', $string);
        return trim($string);
    }

    public function siBoostEnCours($boosts){
        $now = new DateTime();
        foreach ($boosts as $boost) {
            $typeBoost = $boost->getTypeBoost();
            $dateExp   = $boost->getDateExp();
            if ($typeBoost === 'quota') {
                if ($dateExp === null) { return true; } // quota non épuisé
            } else {
                if ($dateExp !== null && $now < $dateExp) { return true; }
            }
        }
        return false;
    }

    public function permissionAdd($user)
    {
        $boosts = $user->getBoosts();
        if ($this->siBoostEnCours($boosts)) {
            return [
                "permissionAdd" => true,
                "messageErreurPermissionAdd" => "Dressur",
            ];
        }
        return [
            "permissionAdd" => false,
            "messageErreurPermissionAdd" => "Vous devez avoir un boost contact en cours pour ajouter des contacts.",
        ];
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

    public function verifPseudo($pseudo) {
        $slugger = new AsciiSlugger();
        $pseudo = strtolower((string)$slugger->slug($pseudo));
        $pseudoSansChiffre = $pseudo;

        for ($i=0; $i <= 9; $i++) {
            $pseudoSansChiffre = str_replace($i, '', $pseudoSansChiffre);
        }

        if(strlen($pseudo) < 3) {
            return [
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Pseudo trop court! 3 caractères minimum...',
            ];
        }

        if(strlen($pseudo) > 20) {
            return [
                'error' => true,
                'titre' => 'Attention!',
                'message' => 'Pseudo trop long! 20 caractères maximum...',
            ];
        }

        foreach ($this->getMotRefusers() as $mot) {
            $pseudoTester = str_replace($mot->getMot(), '', $pseudo);
            if(strlen($pseudoTester) < strlen($pseudo)) {
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
            return [
                'error' => true,
                'deleted' => true,
                'blocked' => false,
                'titre' => 'Erreur!',
                'message' => "Ce compte n'existe plus.",
            ];
        }
        if($user->getBlocked() == true) {
            return [
                'error' => true,
                'deleted' => false,
                'blocked' => true,
                'titre' => 'Erreur!',
                'message' => "Ce compte à été bloquer.\nS'il s'agit d'une erreur, veuillez contactez l'Assistance Dressur par WhatsApp.",
            ];
        }

        // si tous est bon
        return [
            'error' => false,
            'user' => $user,
        ];
    }

    public function isValidSocialUrl($url, $socialNetwork) {
        // Vérifie si l'URL est valide
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }
        
        // Définit les domaines pour chaque réseau social
        $socialDomains = [
            'youtube' => ['youtube.com', 'youtu.be'],
            'tiktok' => ['tiktok.com'],
            'instagram' => ['instagram.com'],
            'facebook' => ['facebook.com'],
        ];
        
        // Vérifie si l'URL contient le domaine du réseau social
        foreach ($socialDomains[$socialNetwork] as $domain) {
            if (strpos($url, $domain) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
