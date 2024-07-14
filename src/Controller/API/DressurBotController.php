<?php

namespace App\Controller\API;

use App\Entity\User;
use FedaPay\FedaPay;
use FedaPay\Transaction;
use App\Services\SessionDS;
use App\Entity\CampagneMail;
use App\Entity\Promotion;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\BoostRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FormuleBoostRepository;
use App\Repository\PromotionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Transaction as EntityTransaction;
use App\Entity\UserBot;
use App\Repository\CampagneMailRepository;
use App\Repository\FormuleCampagneMailRepository;
use App\Repository\PromoReseauRepository;
use App\Repository\UserBotRepository;
use App\Repository\UserRepository;
use App\Utilities\SendMail;
use DateTime;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]

class DressurBotController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1); 
    }

    #[Route('/dressurUserBot', name: 'dressurUserBot')]
    public function dressurUserBot(Request $request, SendMail $sendMail, UserBotRepository $userBotRepository, VerificationsDS $verificationsDS): Response
    {
        $nom = $request->get("nom");
        $email = $request->get("email");
        $numero = $request->get("numero");
        $adresseMac = $request->get("adresseMac");
        $uuidMachine = $request->get("uuidMachine");
        $diskSerialNumber = $request->get("diskSerialNumber");

        $userBotFind = $userBotRepository->findOneBy([
            'email' => $email,
            'numero' => $numero,
            'adresseMac' => $adresseMac,
            'uuidMachine' => $uuidMachine,
            'diskSerialNumber' => $diskSerialNumber,
        ]);

        $verificationNumTel = $verificationsDS->verifFormatNumTel($numero);
        if($verificationNumTel["error"] == true){
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir un numéro de téléphone valide précédé de son préfix Exp(+229 62005500)."]);
        }
        $numero = $verificationNumTel["e164"];

        if (!$verificationsDS->verifMail($email)) {
            return new JsonResponse(['error' => true,'titre' => 'Attention!','message' => "Veuillez saisir une adresse E-Mail valide.",]); 
        }

        if($userBotFind) {
            if($userBotFind->getExpiratedAt() > new DateTime()) {
                return new JsonResponse([
                    'error' => false,
                    'target' => "configPage",
                ]);
            } else {
                return new JsonResponse([
                    'error' => false,
                    'target' => "paiementPage",
                ]);
            }
        } else {
            $html = $this->renderView('emails/welcomeToDressurBot.html.twig',[
                "nom" => $nom,
                "email" => $email,
                "numero" => $numero,
            ]);
    
            try {
                $newUserBot = new UserBot();
                $newUserBot->setNom($nom)
                    ->setEmail($email)
                    ->setNumero($numero)
                    ->setAdresseMac($adresseMac)
                    ->setUuidMachine($uuidMachine)
                    ->setDiskSerialNumber($diskSerialNumber)
                ;
                $this->em->persist($newUserBot);
                $this->em->flush();

                $sendMail->smtpMail(
                    $email,
                    "BIENVENU SUR DRESSUR BOT",
                    $html,
                    "dressur.ds@gmail.com", 
                    "Dressur Bot No ".time(), 
                );
                
                return new JsonResponse([
                    'error' => false,
                    'target' => "paiementPage",
                    'userInfo' => [
                        'email' => $email,
                        'numero' => $numero,
                        'adresseMac' => $adresseMac,
                        'uuidMachine' => $uuidMachine,
                        'diskSerialNumber' => $diskSerialNumber,
                        'createdAt' => $newUserBot->getCreatedAt(),
                        'expiratedAt' => $newUserBot->getExpiratedAt(),
                    ],
                ]);
            } catch (\Throwable $th) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Mail non envoyer. Veuillez contactez l'assistance de Dressur Bot...",
                ]);
            }
        }
        return new JsonResponse([
            'error' => true,
            'titre' => 'Erreur!',
            'message' => "Erreur de traitement. Veuillez contactez l'assistance de Dressur Bot...",
        ]);
    }
}