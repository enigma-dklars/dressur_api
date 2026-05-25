<?php

namespace App\Controller\API;

use App\Repository\StoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class StoryController extends AbstractController
{
    #[Route('/getActiveStories', name: 'getActiveStories', methods: ['POST', 'GET'])]
    public function getActiveStories(StoryRepository $storyRepository): Response
    {
        try {
            $stories = $storyRepository->findActiveStories();
            shuffle($stories);

            $data = [];
            foreach ($stories as $story) {
                $images = array_values(array_filter($story->getImages() ?? []));
                $videos = array_values(array_filter($story->getVideos() ?? []));

                if (empty($images) && empty($videos)) {
                    continue;
                }

                $data[] = [
                    'id'          => $story->getId(),
                    'user'        => $story->getUser()?->getPseudo() ?? 'Story',
                    'description' => $story->getDescription() ?? '',
                    'url'         => $story->getUrl() ?? '',
                    'images'      => $images,
                    'videos'      => $videos,
                    'createdAt'   => $story->getCreatedAt()?->format('Y-m-d H:i:s') ?? '',
                    'expiredAt'   => $story->getExpiredAt()?->format('Y-m-d H:i:s') ?? null,
                ];
            }

            return new JsonResponse([
                'error'   => false,
                'stories' => $data,
            ]);
        } catch (\Throwable $th) {
            return new JsonResponse([
                'error'   => true,
                'message' => $th->getMessage(),
            ], 500);
        }
    }
}
