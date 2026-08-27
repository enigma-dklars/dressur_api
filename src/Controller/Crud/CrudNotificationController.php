<?php

namespace App\Controller\Crud;

use App\Repository\NotificationRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/notification')]
class CrudNotificationController extends AbstractController
{
    private $theme;
    private $cookieDS;
    private $traitementsDS;

    public function __construct(CookieDS $cookieDS, TraitementsDS $traitementsDS)
    {
        $this->cookieDS = $cookieDS;
        $this->traitementsDS = $traitementsDS;
        if ($this->cookieDS->check('theme')) {
            $this->theme = $this->cookieDS->get('theme') === 'dark-theme'
                ? 'dark-theme'
                : 'light-theme';
        } else {
            $this->theme = 'light-theme';
        }
    }

    #[Route('/', name: 'app_crud_notification_index', methods: ['GET'])]
    public function index(NotificationRepository $notificationRepository): Response
    {
        $cutoff = new \DateTimeImmutable('-6 months');
        $deletedCount = $notificationRepository->deleteOlderThan($cutoff);

        if ($deletedCount > 0) {
            $this->addFlash(
                'info',
                sprintf(
                    '%d notification(s) datant de plus de six mois ont été supprimée(s) automatiquement.',
                    $deletedCount
                )
            );
        }

        return $this->render('crud_notification/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'notifications' => $notificationRepository->findBy([], [
                'createdAt' => 'DESC',
                'id' => 'DESC',
            ]),
            'cutoff' => $cutoff,
        ]);
    }
}
