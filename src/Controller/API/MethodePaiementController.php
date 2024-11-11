<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/api', name: 'api_')]
class MethodePaiementController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1);
    }
    
    #[Route('/listeMethodePaiement', name: 'listeMethodePaiement', methods: ['POST', 'GET'])]
    public function listeMethodePaiement(TraitementsDS $traitementsDS): Response
    {
        return new JsonResponse([
            'error' => false,
            'listeMethodePaiement' => $traitementsDS->listeMethodePaiement(),
        ]);
    }
}
