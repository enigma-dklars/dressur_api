<?php

namespace App\Controller\Crud;

use App\Entity\User;
use App\Form\User1Type;
use App\Repository\BoostRepository;
use App\Repository\CampagneMailRepository;
use App\Repository\DSBonusHistoriqueRepository;
use App\Repository\MessageRepository;
use App\Repository\PromoReseauRepository;
use App\Repository\PromotionRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuggestionRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Repository\VerifMailRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/crud/user')]
class CrudUserController extends AbstractController
{
    private $theme;
    private $cookieDS;
    private $traitementsDS;

    public function __construct(CookieDS $cookieDS, TraitementsDS $traitementsDS)
    {
        $this->cookieDS = $cookieDS;
        $this->traitementsDS = $traitementsDS;
        if($this->cookieDS->check("theme")) {
            if($this->cookieDS->get("theme") == "dark-theme") {
                $this->theme = "dark-theme";
            } else {
                $this->theme = "light-theme";
            }
        } else {
            $this->theme = "light-theme";
        }
    }

    #[Route('/', name: 'app_crud_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('crud_user/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'users' => $userRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_crud_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(User1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_user/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/purge', name: 'app_crud_user_purge', methods: ['GET', 'POST'])]
    public function purge(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, TraitementsDS $traitementsDS): Response
    {
        // Process the form submission
        if ($request->isMethod('POST')) {
            $input = $request->request->get('identifier');
            $input = str_replace(" ", "", $input);

            $user = $userRepository->findOneBy(['mail' => $input]) ?? $userRepository->findOneBy(['tel' => $input]);
            
            if($user) {                
                $traitementsDS->execPurge($user);
                // Add a flash message to confirm deletion
                $this->addFlash('success', 'User and all related information have been deleted.');
                
                return $this->redirectToRoute('app_crud_user_purge');
            }

            // Add a flash message if user is not found
            $this->addFlash('danger', 'User not found.');
        }

        return $this->render('crud_user/purge_user.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
        ]);
    }

    #[Route('/{id}', name: 'app_crud_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('crud_user/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(User1Type::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_user/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/activerMail', name: 'app_crud_user_activerMail', methods: ['GET', 'POST'])]
    public function activerMail(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setMailIsVerified(true);
        $entityManager->flush();
        return $this->redirectToRoute('app_crud_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/activerTel', name: 'app_crud_user_activerTel', methods: ['GET', 'POST'])]
    public function activerTel(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setTelIsVerified(true);
        $entityManager->flush();
        return $this->redirectToRoute('app_crud_user_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_crud_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
