<?php

namespace App\Controller\Crud;

use App\Entity\MsgAi;
use App\Form\MsgAiType;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Repository\MsgAiRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/crud/msg/ai')]
class CrudMsgAiController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_msg_ai_index', methods: ['GET'])]
    public function index(MsgAiRepository $msgAiRepository): Response
    {
        return $this->render('crud_msg_ai/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'msg_ais' => $msgAiRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_crud_msg_ai_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $msgAi = new MsgAi();
        $form = $this->createForm(MsgAiType::class, $msgAi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($msgAi);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_msg_ai_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_msg_ai/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'msg_ai' => $msgAi,
            'form' => $form,
        ]);
    }

    #[Route('/create-for-all', name: 'app_crud_msg_ai_create_for_all', methods: ['GET', 'POST'])]
    public function create_for_all(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
    {
        // Vérifier si la requête est en POST
        if ($request->isMethod('POST')) {

            // Récupération de toutes les variantes envoyées
            // "testers" est le name="testers[]" dans le formulaire
            $messages = $request->request->all('testers');
            $users = $userRepository->findAll();

            foreach ($users as $user) {
                // dump($user->getTel(), $user->__toString(), $message_selectionner);
                $message_selectionner = $messages[rand(0, count($messages) - 1)];
                $oneNewMsgAi = new MsgAi();
                $oneNewMsgAi->setRecepteur($user->getTel())->setMessage($message_selectionner);
                $em->persist($oneNewMsgAi);
            }
            $em->flush();

            return $this->redirectToRoute('app_crud_msg_ai_index', [], Response::HTTP_SEE_OTHER);
        }

        // Si GET → juste afficher le formulaire
        return $this->renderForm('crud_msg_ai/create_for_all.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
        ]);
    }

    #[Route('/{id}', name: 'app_crud_msg_ai_show', methods: ['GET'])]
    public function show(MsgAi $msgAi): Response
    {
        return $this->render('crud_msg_ai/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'msg_ai' => $msgAi,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_msg_ai_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, MsgAi $msgAi, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(MsgAiType::class, $msgAi);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_msg_ai_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_msg_ai/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'msg_ai' => $msgAi,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_msg_ai_delete', methods: ['POST'])]
    public function delete(Request $request, MsgAi $msgAi, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$msgAi->getId(), $request->request->get('_token'))) {
            $entityManager->remove($msgAi);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_msg_ai_index', [], Response::HTTP_SEE_OTHER);
    }
}
