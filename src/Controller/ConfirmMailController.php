<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfirmMailController extends AbstractController
{
    #[Route('/confirmer-mail/{uid}/{token}', name: 'app_confirm_mail', methods: ['GET'])]
    public function confirm(
        string $uid,
        string $token,
        UserRepository $userRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $userRepository->findOneBy(['uid' => $uid]);

        if (!$user) {
            return $this->render('confirm_mail/index.html.twig', [
                'status'  => 'error',
                'message' => 'Lien invalide ou expiré.',
            ]);
        }

        if ($user->getMailIsVerified() === true) {
            return $this->render('confirm_mail/index.html.twig', [
                'status'  => 'already',
                'message' => 'Votre adresse mail est déjà confirmée.',
            ]);
        }

        $secret   = $this->getParameter('kernel.secret');
        $expected = substr(hash_hmac('sha256', $uid . ':' . strtolower(trim((string) $user->getMail())), $secret), 0, 40);

        if (!hash_equals($expected, $token)) {
            return $this->render('confirm_mail/index.html.twig', [
                'status'  => 'error',
                'message' => 'Lien invalide ou expiré.',
            ]);
        }

        $user->setMailIsVerified(true);
        $em->flush();

        return $this->render('confirm_mail/index.html.twig', [
            'status'  => 'success',
            'message' => 'Votre adresse mail a bien été confirmée !',
        ]);
    }
}
