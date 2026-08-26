<?php

namespace App\Controller\Crud;

use App\Entity\UserRestriction;
use App\Repository\UserRepository;
use App\Repository\UserRestrictionRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
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
        private TraitementsDS $traitementsDS
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
                    ->setActive(true)
                    ->setUpdatedAt(new DateTime());

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
        $entityManager->flush();

        $this->addFlash(
            $restriction->isActive() ? 'success' : 'warning',
            $restriction->isActive() ? 'Restriction réactivée.' : 'Restriction désactivée.'
        );

        return $this->redirectToRoute('app_crud_user_restrictions', [], Response::HTTP_SEE_OTHER);
    }

    private function theme(): string
    {
        return $this->cookieDS->check('theme') && $this->cookieDS->get('theme') === 'dark-theme'
            ? 'dark-theme'
            : 'light-theme';
    }
}
