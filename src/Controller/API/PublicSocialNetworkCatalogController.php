<?php

namespace App\Controller\API;

use App\Services\PublicSocialNetworkCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
final class PublicSocialNetworkCatalogController extends AbstractController
{
    #[Route('/social-networks/catalog', name: 'social_network_catalog', methods: ['GET'])]
    public function catalog(PublicSocialNetworkCatalog $catalog): JsonResponse
    {
        return new JsonResponse([
            'error' => false,
            'networks' => $catalog->all(),
        ]);
    }
}