<?php

namespace App\Controller\Crud;

use App\Entity\Notification;
use App\Entity\UserRestriction;
use App\Repository\UserRepository;
use App\Repository\UserRestrictionRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Services\UserRestrictionService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/user')]
class CrudUserRestrictionController extends AbstractController
{
    public function __construct(
        private CookieDS $cookieDS,
        private TraitementsDS $traitementsDS,
        private UserRestrictionService $restrictionService
    ) {
    }

    #[Route('/restrictions', name: 'app_crud_user_restrictions', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        UserRepository $userRepository,
        UserRestrictionRepository $restrictionRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $errors = [];
        $users = $userRepository->findBy([], ['pseudo' => 'ASC']);
        $restrictionTypes = [
            UserRestriction::TYPE_BLOCK_FREE_BOOST => 'Bloquer les Boost Contacts gratuits',
            UserRestriction::TYPE_MINIMUM_TRANSACTION => 'Imposer un montant minimum par transaction',
        ];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('user_restriction_save', $request->request->get('_token'))) {
                $errors[] = 'Token CSRF invalide. Modification annulée.';
            }

            $userId = filter_var($request->request->get('user_id'), FILTER_VALIDATE_INT);
            $type = (string) $request->request->get('type', '');
            $reason = trim((string) $request->request->get('reason', ''));
            $amountValue = trim((string) $request->request->get('minimum_transaction_amount', ''));
            $expiresValue = trim((string) $request->request->get('expires_at', ''));
            $targetUser = $userId ? $userRepository->find($userId) : null;

            if (!$targetUser) {
                $errors[] = 'Utilisateur invalide.';
            }
            if (!isset($restrictionTypes[$type])) {
                $errors[] = 'Type de restriction invalide.';
            }
            if ($reason === '') {
                $errors[] = 'Le motif est obligatoire.';
            }

            $amount = null;
            if ($type === UserRestriction::TYPE_MINIMUM_TRANSACTION) {
                $amount = filter_var($amountValue, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($amount === false) {
                    $errors[] = 'Le montant minimum doit être un nombre entier supérieur à 0 FCFA.';
                }
            }

            $expiresAt = null;
            if ($expiresValue !== '') {
                $expiresAt = DateTime::createFromFormat('!Y-m-d', $expiresValue);
                $dateErrors = DateTime::getLastErrors();
                if ($expiresAt === false || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
                    $errors[] = 'La date de fin est invalide.';
                    $expiresAt = null;
                } else {
                    $expiresAt->setTime(23, 59, 59);
                    if ($expiresAt < new DateTime()) {
                        $errors[] = 'La date de fin doit être aujourd’hui ou une date future.';
                        $expiresAt = null;
                    }
                }
            }

            if (!$errors) {
                $restriction = $restrictionRepository->findOneForUserAndType($targetUser, $type);
                if (!$restriction) {
                    $restriction = (new UserRestriction())
                        ->setUser($targetUser)
                        ->setType($type);
                    $entityManager->persist($restriction);
                }

                $restriction
                    ->setMinimumTransactionAmount($type === UserRestriction::TYPE_MINIMUM_TRANSACTION ? $amount : null)
                    ->setReason($reason)
                    ->setExpiresAt($expiresAt)
                    ->setActive(true)
                    ->setUpdatedAt(new DateTime());
                $this->restrictionService->captureIdentity($restriction, $targetUser);

                $notification = (new Notification())
                    ->setUser($targetUser)
                    ->setText($this->buildRestrictionNotification($restriction))
                    ->setCreatedAt(new DateTime());
                $entityManager->persist($notification);
                $entityManager->flush();
                $this->addFlash('success', 'Restriction enregistrée pour ' . ($targetUser->getPseudo() ?: 'cet utilisateur') . '.');

                return $this->redirectToRoute('app_crud_user_restrictions', [], Response::HTTP_SEE_OTHER);
            }
        }

        return $this->render('crud_user/restrictions.html.twig', [
            'theme' => $this->theme(),
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'users' => $users,
            'restrictions' => $restrictionRepository->findBy([], ['active' => 'DESC', 'updatedAt' => 'DESC']),
            'restrictionTypes' => $restrictionTypes,
            'errors' => $errors,
            'formData' => $request->isMethod('POST') ? $request->request->all() : [],
        ]);
    }

    #[Route('/restrictions/{id}/toggle', name: 'app_crud_user_restriction_toggle', methods: ['POST'])]
    public function toggle(
        UserRestriction $restriction,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('user_restriction_toggle' . $restriction->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide. La restriction n’a pas été modifiée.');
            return $this->redirectToRoute('app_crud_user_restrictions');
        }

        $restriction
            ->setActive(!$restriction->isActive())
            ->setUpdatedAt(new DateTime());

        if ($restriction->isCurrentlyActive() && $restriction->getUser() !== null) {
            $notification = (new Notification())
                ->setUser($restriction->getUser())
                ->setText($this->buildRestrictionNotification($restriction))
                ->setCreatedAt(new DateTime());
            $entityManager->persist($notification);
        }

        $entityManager->flush();

        $this->addFlash(
            $restriction->isActive() ? 'success' : 'warning',
            $restriction->isActive() ? 'Restriction réactivée.' : 'Restriction désactivée.'
        );

        return $this->redirectToRoute('app_crud_user_restrictions', [], Response::HTTP_SEE_OTHER);
    }

    private function buildRestrictionNotification(UserRestriction $restriction): string
    {
        $until = $restriction->getExpiresAt()
            ? ' jusqu’au ' . $restriction->getExpiresAt()->format('d/m/Y')
            : ' jusqu’à la levée de la restriction';

        $description = $restriction->getType() === UserRestriction::TYPE_BLOCK_FREE_BOOST
            ? 'Vous ne pouvez plus effectuer de Boost Contact gratuit'
            : 'Un montant minimum de ' . number_format((int) $restriction->getMinimumTransactionAmount(), 0, ',', ' ') . ' FCFA est applicable à vos transactions payantes';

        return "Compte restreint.\nRestriction appliquée : " . $description . $until . ".\nMotif : " . $restriction->getReason();
    }

    private function theme(): string
    {
        return $this->cookieDS->check('theme') && $this->cookieDS->get('theme') === 'dark-theme'
            ? 'dark-theme'
            : 'light-theme';
    }
}
