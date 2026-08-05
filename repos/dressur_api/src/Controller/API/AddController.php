<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use App\Repository\BoostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/api', name: 'api_')]
class AddController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1);
    }
    
    #[Route('/getContactActuUser/{uid}', name: 'getContactActuUser', methods: ['POST', "GET"])]
    public function getContactActuUser(User $user, TraitementsDS $traitementsDS): Response
    {
        return new JsonResponse($traitementsDS->getAddDisponible($user));
    }

    #[Route('/addTousUserContact/{uid}/{langUserPhone}', name: 'addTousUserContact', methods: ['POST', "GET"])]
    public function addTousUserContact(Request $request, $uid, UserRepository $userRepository, VerificationsDS $verificationsDS, TraitementsDS $traitementsDS, BoostRepository $boostRepository): Response
    {
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
        $contactsAdd = [];
        if ($user->getTelIsVerified() == true) {
            // Calculé une seule fois : la permission ne change pas pendant la requête
            $permission = $verificationsDS->permissionAdd($user);

            // Pré-chargement du boost actif du user pour éviter un double appel plus bas.
            // Si c'est un boost quota avec un plafond défini, on calcule le nombre d'ajouts
            // encore autorisés afin de stopper la boucle dès que le quota est atteint.
            $userBoostActif = $boostRepository->findBoostActif($user);
            $resteQuota   = null;  // null = pas de bridage quota en cours de boucle
            $quotaAtteint = false; // passe à true si le break quota est déclenché
            if ($userBoostActif && $userBoostActif->getTypeBoost() === 'quota') {
                $nbContactsMax = $userBoostActif->getFormuleBoost()->getNbContactsMax();
                if ($nbContactsMax !== null) {
                    $resteQuota = $nbContactsMax - (int)($userBoostActif->getNbContactsObtenus() ?? 0);
                }
            }

            foreach ($traitementsDS->getAddDisponible($user) as $add) {
                $userAdd = $userRepository->findOneBy(['tel' => $add['tel']]);
                if($userAdd){
                    if($permission["permissionAdd"] == true){
                        // MOD
                        $user->getContact()->setNewIAdd($userAdd);
                        $userAdd->getContact()->setNewAddMe($user);
                        $this->em->flush();
                        // Incrémenter le boost actif de $userAdd s'il en a un (quota ou date)
                        $boostActif = $boostRepository->findBoostActif($userAdd);
                        if ($boostActif) {
                            $boostActif->setNbContactsObtenus((int)($boostActif->getNbContactsObtenus() ?? 0) + 1);
                            if ($boostActif->getTypeBoost() === 'quota') {
                                $nbContactsMax = $boostActif->getFormuleBoost()->getNbContactsMax();
                                if ($nbContactsMax !== null && $boostActif->getNbContactsObtenus() >= $nbContactsMax) {
                                    $boostActif->setDateExp(new \DateTime());
                                }
                            }
                            $this->em->flush();
                        }
                        array_push($contactsAdd, [
                            "pseudo" => $userAdd->getPseudo(),
                            "nom" => (string)$userAdd,
                            "tel" => $userAdd->getTel(),
                        ]);
                        // Bridage quota : on stoppe dès que le plafond est atteint
                        if ($resteQuota !== null) {
                            $resteQuota--;
                            if ($resteQuota <= 0) {
                                $quotaAtteint = true;
                                break;
                            }
                        }
                    } else {
                        return new JsonResponse([
                            'error' => true,
                            "contactsAdd" => $contactsAdd,
                            "permissionAdd" => $permission["permissionAdd"],
                            "messageErreurPermissionAdd" => $permission["messageErreurPermissionAdd"],
                        ]);
                    }
                }
            }

            if(count($contactsAdd) > 0) {
                // Incrémenter le boost actif du user (variable déjà chargée avant la boucle)
                if($userBoostActif) {
                    $userBoostActif->setNbContactsObtenus((int)($userBoostActif->getNbContactsObtenus() ?? 0) + count($contactsAdd));
                    if ($userBoostActif->getTypeBoost() === 'quota') {
                        $nbContactsMax = $userBoostActif->getFormuleBoost()->getNbContactsMax();
                        if ($nbContactsMax !== null && $userBoostActif->getNbContactsObtenus() >= $nbContactsMax) {
                            $userBoostActif->setDateExp(new \DateTime());
                        }
                    }
                    $this->em->flush();
                }
            }

            $traitementsDS->addNotification("Vous avez enregistrer ".count($contactsAdd)." contact(s).", $user);
            $this->em->flush();

            // Quota atteint en cours de boucle : on informe le user des contacts
            // enregistrés ET on lui indique qu'un nouveau boost est nécessaire.
            if ($quotaAtteint) {
                return new JsonResponse([
                    'error'                      => true,
                    'contactsAdd'                => $contactsAdd,
                    'permissionAdd'              => false,
                    'messageErreurPermissionAdd' => 'Vous avez atteint votre quota de contacts. Lancez un nouveau boost contact pour continuer.',
                ]);
            }
        }
        return new JsonResponse([
            'error' => false,
            'contactsAdd' => $contactsAdd,
        ]);
    }
}
