<?php

namespace App\Controller\API;

use App\Entity\User;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use App\Utilities\ZefameApi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;



#[Route('/api', name: 'api_')]
class ZefameController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1);
    }
    
    #[Route('/zefame', name: 'zefame', methods: ['POST', "GET"])]
    public function zefame(ZefameApi $zefameApi): Response
    {
        // $zefameApi->balance();
        // $zefameApi->services();
        // $zefameApi->status("3099025");
        // $zefameApi->multiStatus(["3099025", "3098451"]);

        dd($zefameApi->multiStatus(["3099025", "3098451"]));
        // return new JsonResponse();
    }
}
