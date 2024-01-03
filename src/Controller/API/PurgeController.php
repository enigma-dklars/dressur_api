<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Repository\BoostRepository;
use App\Repository\DeletedDSRepository;
use App\Repository\PreferenceRepository;
use App\Repository\SignalementRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Repository\VerifMailRepository;
use App\Repository\DSBonusHistoriqueRepository;
use App\Repository\DSBonusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]

class PurgeController extends AbstractController
{
    private $em;
    private $boostRepository;
    private $deletedDSRepository;
    private $preferenceRepository;
    private $transactionRepository;
    private $verifMailRepository;
    private $wPBonusRepository;
    private $wPBonusHistoriqueRepository;
    private $signalementRepository;
    private $userRepository;
    
    public function __construct(EntityManagerInterface $em, BoostRepository $boostRepository, DeletedDSRepository $deletedDSRepository, PreferenceRepository $preferenceRepository, TransactionRepository $transactionRepository, VerifMailRepository $verifMailRepository, DSBonusRepository $wPBonusRepository, DSBonusHistoriqueRepository $wPBonusHistoriqueRepository, SignalementRepository $signalementRepository, UserRepository $userRepository)
    {
        $this->em = $em; 
        $this->boostRepository = $boostRepository;
        $this->deletedDSRepository = $deletedDSRepository;
        $this->preferenceRepository = $preferenceRepository;
        $this->transactionRepository = $transactionRepository;
        $this->verifMailRepository = $verifMailRepository;
        $this->wPBonusRepository = $wPBonusRepository;
        $this->wPBonusHistoriqueRepository = $wPBonusHistoriqueRepository;
        $this->signalementRepository = $signalementRepository;
        $this->userRepository = $userRepository;
    }

    #[Route('/purge_ds_by_user_id/{id}', name: 'purge_ds_by_user_id')]
    public function purge_ds_by_user_id(User $user): Response
    {
        set_time_limit(10000);

        $this->execPurge($user);
        
        return new Response("ok");
    }

    #[Route('/purge_ds', name: 'purge_ds')]
    public function purge_ds(UserRepository $userRepository): Response
    {
        set_time_limit(10000);

        $users = $userRepository->findBy(['blocked' => true]);
        foreach ($users as $user) {
            $this->execPurge($user);
        }

        $usersDeleted = $userRepository->findBy(['deleted' => true]);
        foreach ($usersDeleted as $userDeleted) {
            $this->execPurge($userDeleted);
        }

        $usersSuspend = $userRepository->findBy(['suspended' => true]);
        foreach ($usersSuspend as $userSuspend) {
            $this->execPurge($userSuspend);
        }

        $usersnomailnotel = $userRepository->findBy(['telIsVerified' => false, 'mailIsVerified' => false]);
        foreach ($usersnomailnotel as $usernomailnotel) {
            $this->execPurge($usernomailnotel);
        }

        $usersnotelhavemail = $userRepository->findBy(['telIsVerified' => false, 'mailIsVerified' => true]);
        foreach ($usersnotelhavemail as $usernotelhavemail) {
            $this->execPurge($usernotelhavemail);
        }
        
        return new Response("ok");
    }

    #[Route('/del_user_qui_bouge_pas/{id_max}', name: 'del_user_qui_bouge_pas')]
    public function del_user_qui_bouge_pas($id_max, UserRepository $userRepository): Response
    {
        set_time_limit(10000);

        $usersDeleted = $userRepository->findBy(['deleted' => true]);
        foreach ($usersDeleted as $userDeleted) {
            $this->execPurge($userDeleted);
        }

        $usersSuspend = $userRepository->findBy(['suspended' => true]);
        foreach ($usersSuspend as $userSuspend) {
            $this->execPurge($userSuspend);
        }

        $usersnottel = $userRepository->findBy(['telIsVerified' => false]);
        foreach ($usersnottel as $usernottel) {
            if($id_max >= $usernottel->getId()) {
                $this->execPurge($usernottel);
            }
        }

        $usersnotmail = $userRepository->findBy(['mailIsVerified' => false]);
        foreach ($usersnotmail as $usernotmail) {
            if($id_max >= $usernotmail->getId()) {
                $this->execPurge($usernotmail);
            }
        }
        
        return new Response("ok");
    }

    #[Route('/addbonusKdoToUser/{bonus}/{id_user_max}', name: 'addbonusKdoToUser')]
    public function addbonusKdoToUser($bonus, $id_user_max, UserRepository $userRepository): Response
    {
        set_time_limit(10000);

        $usersnottel = $userRepository->findBy([
            'telIsVerified' => true, 
            'mailIsVerified' => true,
            'suspended' => false,
            'deleted' => false,
            'blocked' => false,
        ]);
        foreach ($usersnottel as $usernottel) {
            if($id_user_max >= $usernottel->getId()) {
                // $usernottel->addSoldeBonus($bonus);
                $usernottel->setSoldeBonus($bonus);
            }
        }
        $this->em->flush();
        
        return new Response("ok");
    }

    public function execPurge($user){
        foreach ($this->userRepository->findBy(['parrain' => $user]) as $element) {
            $element->setParrain($this->userRepository->find(1));
            $this->em->flush();
        }

        foreach ($this->boostRepository->findBy(['user' => $user]) as $element) {
            $this->boostRepository->remove($element, true);
        }

        foreach ($this->deletedDSRepository->findBy(['user' => $user]) as $element) {
            $this->deletedDSRepository->remove($element, true);
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
