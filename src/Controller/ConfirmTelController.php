<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Services\TraitementsDS;
use App\Services\UserRestrictionService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfirmTelController extends AbstractController
{
    #[Route('/confirmer-tel/{uid}/{token}', name: 'app_confirm_tel', methods: ['GET'])]
    public function confirm(
        string $uid,
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        TraitementsDS $traitementsDS,
        UserRestrictionService $restrictionService
    ): Response {
        $user = $userRepository->findOneBy(['uid' => $uid]);

        if (!$user) {
            return $this->render('confirm_tel/index.html.twig', [
                'status'  => 'error',
                'message' => 'Lien invalide ou expiré.',
            ]);
        }

        if ($user->getTelIsVerified() === true) {
            return $this->render('confirm_tel/index.html.twig', [
                'status'  => 'already',
                'message' => 'Votre numéro de téléphone est déjà confirmé.',
            ]);
        }

        $secret   = $this->getParameter('kernel.secret');
        $tel      = strtolower(trim((string) $user->getTel()));
        $expected = substr(hash_hmac('sha256', $uid . ':' . $tel, $secret), 0, 40);

        if (!hash_equals($expected, $token)) {
            return $this->render('confirm_tel/index.html.twig', [
                'status'  => 'error',
                'message' => 'Lien invalide ou expiré.',
            ]);
        }

        $user->setTelIsVerified(true);
        $restrictionService->restoreForUser($user);
        $traitementsDS->addNotification("Votre numéro WhatsApp a bien été confirmé !", $user);
        $em->flush();

        return $this->render('confirm_tel/index.html.twig', [
            'status'  => 'success',
            'message' => 'Votre numéro WhatsApp a bien été confirmé !',
        ]);
    }
}
