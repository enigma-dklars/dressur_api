<?php

namespace App\Controller;

use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

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
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('admin_interface/index.html.twig', [
            'env' => $this->env,
            'users' => $userRepository->findBy(["telIsVerified" => false]),
        ]);
    }

    #[Route('/admin/interface/upadte_env_value', name: 'app_admin_interface_upadte_env_value')]
    public function upadte_env_value(Request $request): Response
    {
        $importantUpdate = ($request->get("importantUpdate") == 1) ? true : false;
        $doBoostPayant = ($request->get("doBoostPayant") == 1) ? true : false;

        $this->env->setCommissionBonus($request->get("commissionBonus"));
        $this->env->setVersionApp($request->get("versionApp"));
        $this->env->setLinkLocalServer($request->get("linkLocalServer"));

        $this->env->setDoBoostPayant($doBoostPayant);
        $this->env->setImportantUpdate($importantUpdate);

        $this->em->flush();

        return new Response("OK");
    }
}
