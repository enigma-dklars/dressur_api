<?php

namespace App\Controller\Crud;

use App\Entity\Story;
use App\Repository\StoryRepository;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/crud/story')]
class CrudStoryController extends AbstractController
{
    private string $theme;
    private CookieDS $cookieDS;
    private TraitementsDS $traitementsDS;

    public function __construct(CookieDS $cookieDS, TraitementsDS $traitementsDS)
    {
        $this->cookieDS = $cookieDS;
        $this->traitementsDS = $traitementsDS;
        $this->theme = ($cookieDS->check('theme') && $cookieDS->get('theme') === 'dark-theme')
            ? 'dark-theme'
            : 'light-theme';
    }

    #[Route('/', name: 'app_crud_story_index', methods: ['GET'])]
    public function index(StoryRepository $storyRepository): Response
    {
        return $this->render('crud_story/index.html.twig', [
            'theme'   => $this->theme,
            'user'    => $this->traitementsDS->getUserByUidInCookies(),
            'stories' => $storyRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/delete-images-no-use', name: 'app_crud_story_delete_images_no_use', methods: ['GET'])]
    public function deleteImagesNoUse(StoryRepository $storyRepository): Response
    {
        $storyDirectory = $this->getParameter('story_directory');

        if (!is_dir($storyDirectory)) {
            $this->addFlash('danger', 'Le dossier story n\'existe pas.');
            return $this->redirectToRoute('app_crud_story_index', [], Response::HTTP_SEE_OTHER);
        }

        $usedImages = [];
        foreach ($storyRepository->findAll() as $story) {
            foreach ($story->getImages() as $filename) {
                $usedImages[$filename] = true;
            }
        }

        $deleted = 0;
        foreach (scandir($storyDirectory) as $file) {
            if ($file === '.' || $file === '..') continue;
            if (str_starts_with($file, 'story_') && !isset($usedImages[$file])) {
                unlink($storyDirectory . '/' . $file);
                $deleted++;
            }
        }

        $this->addFlash('success', $deleted > 0
            ? $deleted . ' image(s) orpheline(s) supprimée(s).'
            : 'Aucune image orpheline trouvée.'
        );

        return $this->redirectToRoute('app_crud_story_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/user-search', name: 'app_crud_story_user_search', methods: ['GET'])]
    public function userSearch(Request $request, UserRepository $userRepository): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        $results = [];

        if (strlen($q) >= 2) {
            $qb = $userRepository->createQueryBuilder('u')
                ->where('u.pseudo LIKE :q OR u.nom LIKE :q OR u.mail LIKE :q OR u.tel LIKE :q')
                ->setParameter('q', '%' . $q . '%')
                ->orderBy('u.pseudo', 'ASC')
                ->setMaxResults(20);

            foreach ($qb->getQuery()->getResult() as $user) {
                $results[] = [
                    'id'   => $user->getId(),
                    'text' => $user->getPseudo() . ' — ' . $user->getNom() . ' (' . $user->getMail() . ')',
                ];
            }
        }

        return new JsonResponse(['results' => $results]);
    }

    #[Route('/new', name: 'app_crud_story_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SluggerInterface $slugger, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            $story = new Story();

            $story->setDescription($request->request->get('description'));
            $story->setUrl($request->request->get('url') ?: null);

            $userId = $request->request->get('user_id');
            if ($userId) {
                $user = $userRepository->find((int) $userId);
                $story->setUser($user);
            }

            $expiredAtRaw = $request->request->get('expired_at');
            if ($expiredAtRaw) {
                $story->setExpiredAt(new DateTime($expiredAtRaw));
            }

            $videosRaw = $request->request->get('videos', '');
            $videos = array_values(array_filter(array_map('trim', explode("\n", $videosRaw))));
            $story->setVideos($videos);

            $uploadedFiles = $request->files->get('images', []);
            $storyDirectory = $this->getParameter('story_directory');
            $errors = [];

            foreach ($uploadedFiles as $file) {
                if ($file === null) continue;

                if ($file->getSize() > 3 * 1024 * 1024) {
                    $errors[] = 'Image "' . $file->getClientOriginalName() . '" dépasse 3 Mo.';
                    continue;
                }

                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = 'story_' . $safeFilename . '_' . uniqid() . '.' . $file->guessExtension();

                $file->move($storyDirectory, $newFilename);
                $story->addImage($newFilename);
            }

            if (!empty($errors)) {
                return $this->render('crud_story/new.html.twig', [
                    'theme'  => $this->theme,
                    'user'   => $this->traitementsDS->getUserByUidInCookies(),
                    'errors' => $errors,
                ]);
            }

            $em->persist($story);
            $em->flush();

            $this->addFlash('success', 'Story #' . $story->getId() . ' créée avec succès.');
            return $this->redirectToRoute('app_crud_story_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud_story/new.html.twig', [
            'theme'  => $this->theme,
            'user'   => $this->traitementsDS->getUserByUidInCookies(),
            'errors' => [],
        ]);
    }

    #[Route('/{id}', name: 'app_crud_story_show', methods: ['GET'])]
    public function show(Story $story): Response
    {
        return $this->render('crud_story/show.html.twig', [
            'theme' => $this->theme,
            'user'  => $this->traitementsDS->getUserByUidInCookies(),
            'story' => $story,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_story_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Story $story, EntityManagerInterface $em, SluggerInterface $slugger, UserRepository $userRepository): Response
    {
        if ($request->isMethod('POST')) {
            $story->setDescription($request->request->get('description'));
            $story->setUrl($request->request->get('url') ?: null);

            $userId = $request->request->get('user_id');
            $story->setUser($userId ? $userRepository->find((int) $userId) : null);

            $expiredAtRaw = $request->request->get('expired_at');
            $story->setExpiredAt($expiredAtRaw ? new DateTime($expiredAtRaw) : null);

            $videosRaw = $request->request->get('videos', '');
            $videos = array_values(array_filter(array_map('trim', explode("\n", $videosRaw))));
            $story->setVideos($videos);

            $imagesToDelete = $request->request->all('delete_images') ?? [];
            $storyDirectory = $this->getParameter('story_directory');

            foreach ($imagesToDelete as $filename) {
                $story->removeImage($filename);
                $filePath = $storyDirectory . '/' . $filename;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $uploadedFiles = $request->files->get('images', []);
            $errors = [];

            foreach ($uploadedFiles as $file) {
                if ($file === null) continue;

                if ($file->getSize() > 3 * 1024 * 1024) {
                    $errors[] = 'Image "' . $file->getClientOriginalName() . '" dépasse 3 Mo.';
                    continue;
                }

                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = 'story_' . $safeFilename . '_' . uniqid() . '.' . $file->guessExtension();

                $file->move($storyDirectory, $newFilename);
                $story->addImage($newFilename);
            }

            if (!empty($errors)) {
                return $this->render('crud_story/edit.html.twig', [
                    'theme'  => $this->theme,
                    'user'   => $this->traitementsDS->getUserByUidInCookies(),
                    'story'  => $story,
                    'errors' => $errors,
                ]);
            }

            $em->flush();

            $this->addFlash('success', 'Story #' . $story->getId() . ' modifiée avec succès.');
            return $this->redirectToRoute('app_crud_story_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('crud_story/edit.html.twig', [
            'theme'  => $this->theme,
            'user'   => $this->traitementsDS->getUserByUidInCookies(),
            'story'  => $story,
            'errors' => [],
        ]);
    }

    #[Route('/{id}', name: 'app_crud_story_delete', methods: ['POST'])]
    public function delete(Request $request, Story $story, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $story->getId(), $request->request->get('_token'))) {
            $storyDirectory = $this->getParameter('story_directory');

            foreach ($story->getImages() as $filename) {
                $filePath = $storyDirectory . '/' . $filename;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            $id = $story->getId();
            $em->remove($story);
            $em->flush();

            $this->addFlash('success', 'Story #' . $id . ' supprimée avec succès.');
        } else {
            $this->addFlash('danger', 'Token CSRF invalide. Suppression annulée.');
        }

        return $this->redirectToRoute('app_crud_story_index', [], Response::HTTP_SEE_OTHER);
    }
}
