<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Services\VerificationsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Utilities\SendMail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]

class ContactController extends AbstractController
{
    private $em;
    private $userRepository;
    private $cookieDS;
    private $sendMail;

    public function __construct(EntityManagerInterface $em, UserRepository $userRepository, CookieDS $cookieDS, SendMail $sendMail)
    {
        $this->em = $em;
        $this->userRepository = $userRepository;
        $this->cookieDS = $cookieDS;
        $this->sendMail = $sendMail;
    }   
    
    #[Route('/listContactDS/{uid}/{langUserPhone}', name: 'listContactDS', methods: ['POST', "GET"])]
    public function listContactDS(User $user, TraitementsDS $traitementsDS): Response
    {
        return new JsonResponse($traitementsDS->userContacts($user));
    }

    #[Route('/allUserAddDressur', name: 'addUserContact')]
    public function addUserContact(UserRepository $userRepository, VerificationsDS $verificationsDS): Response
    {
        $dressurId = 2;
        $batchSize = 50;
        $iteration = 0;
        $now       = new \DateTime();

        try {
            $dressur = $userRepository->find($dressurId);

            // Pre-charger tous les boosts en une seule requête (remplace N lazy-loads)
            $allBoosts = $this->em->createQuery(
                'SELECT IDENTITY(b.user) as userId, b.mode, b.typeBoost, b.dateExp
                 FROM App\Entity\Boost b'
            )->getArrayResult();

            $boostsByUser = [];
            foreach ($allBoosts as $b) {
                $boostsByUser[$b['userId']][] = $b;
            }
            unset($allBoosts);

            // Pre-charger tous les contacts en une seule requête (remplace N lazy-loads)
            $allContacts = $this->em->createQuery(
                'SELECT IDENTITY(c.user) as userId, c.whoIAdd, c.whoAddMe
                 FROM App\Entity\Contact c'
            )->getArrayResult();

            $contactByUser = [];
            foreach ($allContacts as $c) {
                $contactByUser[$c['userId']] = $c;
            }
            unset($allContacts);

            $query = $this->em->createQuery('SELECT u FROM App\Entity\User u WHERE u.id != :dressurId')
                ->setParameter('dressurId', $dressurId);
            $iterableResult = $query->toIterable();

            foreach ($iterableResult as $user) {
                $userId = $user->getId();

                if ($this->isEligible($boostsByUser[$userId] ?? [], $contactByUser[$userId] ?? null, $now)) {
                    $user->getContact()->setNewIAdd($dressur);
                    $dressur->getContact()->setNewAddMe($user);
                }

                $iteration++;
                if ($iteration % $batchSize === 0) {
                    $this->em->flush();
                    $this->em->clear();
                    $dressur = $userRepository->find($dressurId);
                }
            }

            $this->em->flush();

            return new JsonResponse(['error' => false]);

        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error addUserContact : ContactController', $th . '<br><br><br>');
            throw $th;
        }
    }

    /**
     * Calcule l'éligibilité depuis des données pré-chargées (arrays).
     * Reproduit exactement la logique de VerificationsDS::permissionAdd() +
     * VerificationsDS::siBoostEnCours() sans déclencher aucun lazy-load Doctrine.
     */
    private function isEligible(array $boosts, ?array $contact, \DateTime $now): bool
    {
        // Boost actif → ajout illimité
        foreach ($boosts as $boost) {
            if ($boost['typeBoost'] === 'quota') {
                if ($boost['dateExp'] === null) {
                    return true;
                }
            } else {
                if ($boost['dateExp'] instanceof \DateTimeInterface && $now < $boost['dateExp']) {
                    return true;
                }
            }
        }

        // Calcul du quota restant
        $nbBoostPayant  = 0;
        $nbBoostGratuit = 0;
        foreach ($boosts as $boost) {
            if ($boost['mode'] === 'Payant') {
                $nbBoostPayant++;
            } else {
                $nbBoostGratuit++;
            }
        }

        $whoAddMe  = $contact ? count($contact['whoAddMe']) : 0;
        $whoIAdd   = $contact ? count($contact['whoIAdd'])  : 0;
        $maxAjouts = ($nbBoostPayant * 25) + ($nbBoostGratuit * 3) + $whoAddMe;

        return ($maxAjouts - $whoIAdd) > 0;
    }
}
