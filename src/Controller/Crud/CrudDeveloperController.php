<?php

namespace App\Controller\Crud;

use App\Entity\DeveloperProfile;
use App\Entity\User;
use App\Repository\DeveloperProfileRepository;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/developpeur')]
class CrudDeveloperController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CookieDS $cookieDS,
        private readonly UserRepository $userRepository,
        private readonly TraitementsDS $traitementsDS,
    ) {
    }

    #[Route('/', name: 'app_crud_developpeur_index', methods: ['GET'])]
    public function index(DeveloperProfileRepository $profileRepository, Request $request): Response
    {
        $this->assertAdmin();
        $status = $request->query->get('status', '');
        $allowedStatuses = ['pending', 'active', 'suspended', 'revoked'];
        $profiles = $status !== '' && in_array($status, $allowedStatuses, true)
            ? $profileRepository->findBy(['status' => $status], ['id' => 'DESC'])
            : $profileRepository->findBy([], ['id' => 'DESC']);

        return $this->render('crud_developpeur/index.html.twig', [
            'theme' => $this->cookieDS->get('theme') === 'dark-theme' ? 'dark-theme' : 'light-theme',
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'profiles' => $profiles,
            'statusFilter' => $status,
        ]);
    }

    #[Route('/{id}/status', name: 'app_crud_developpeur_status', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function changeStatus(DeveloperProfile $profile, Request $request): Response
    {
        $this->assertAdmin();
        if (!$this->isCsrfTokenValid('developer_status_' . $profile->getId(), (string)$request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $status = (string)$request->request->get('status', '');
        if (!in_array($status, ['active', 'suspended', 'revoked'], true)) {
            $this->addFlash('danger', 'Statut développeur invalide.');
            return $this->redirectToRoute('app_crud_developpeur_index');
        }

        $profile->setStatus($status);
        if ($status === 'suspended') {
            $profile->setSuspendedAt(new DateTime())->setRevokedAt(null);
        } elseif ($status === 'revoked') {
            $profile->setRevokedAt(new DateTime())->setSuspendedAt(null);
            foreach ($profile->getApiKeys() as $apiKey) {
                if ($apiKey->getRevokedAt() === null) {
                    $apiKey->setRevokedAt(new DateTime());
                }
            }
        } else {
            $profile->setSuspendedAt(null)->setRevokedAt(null);
        }

        $this->entityManager->flush();
        $this->addFlash('success', 'Le statut développeur a été mis à jour.');

        return $this->redirectToRoute('app_crud_developpeur_index');
    }

    private function assertAdmin(): void
    {
        $uid = $this->cookieDS->get('uid');
        $user = $uid ? $this->userRepository->findOneBy(['uid' => $uid]) : null;
        if (!$user instanceof User || !$user->getAdmin()) {
            throw $this->createAccessDeniedException('Droits administrateur requis.');
        }
    }
}
