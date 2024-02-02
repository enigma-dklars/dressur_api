<?php

namespace App\Controller;

use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminInterfaceController extends AbstractController
{
    private $em;
    private $env;
    private $traitementsDS;

    public function __construct(EntityManagerInterface $em, EnvRepository $env, TraitementsDS $traitementsDS)
    {
        $this->em = $em;
        $this->env = $env->find(1);
        $this->traitementsDS = $traitementsDS;
    } 
    #[Route('/admin/interface', name: 'app_admin_interface')]
    public function index(): Response
    {
        return $this->render('admin_interface/index.html.twig', [
            'env' => $this->env,
        ]);
    }

    #[Route('/admin/interface/upadte_env_value', name: 'app_admin_interface_update_env_value')]
    public function update_env_value(): Response
    {
        return $this->render('admin_interface/index.html.twig', [
            'env' => $this->env,
        ]);
    }
}
