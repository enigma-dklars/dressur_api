<?php

namespace App\Controller\API;

use DateTime;
use FedaPay\FedaPay;
use FedaPay\Webhook;
use App\Entity\Boost;
use App\Entity\Promotion;
use FedaPay\Transaction;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use App\Repository\BoostRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use App\Repository\FormuleBoostRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Transaction as EntityTransaction;
use App\Entity\User;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\PromotionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

#[Route('/api', name: 'api_')]

class PromotionReseauController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1);
    }


    #[Route('/listeFormulePromoReseau', name: 'listeFormulePromoReseau', methods: ['POST', 'GET'])]
    public function listeFormulePromoReseau(Request $request, SessionDS $sessionDS, FormulePromoReseauRepository $formulePromoReseauRepository): Response
    {
        $datas = $request->request;
        
        $langUserPhone = $datas->get('langUserPhone');
        $sessionDS->set("langUserPhone", $langUserPhone);

        $listeFormulePromoReseau = [];
        foreach ($formulePromoReseauRepository->findBy(['parent' => NULL, 'available' => true]) as $formule) {
            $lesFormulesFils = [];
            foreach ($formulePromoReseauRepository->findBy(['parent' => $formule, 'available' => true]) as $formuleFils) {
                array_push($lesFormulesFils, [
                    "value" => $formuleFils->getId(),
                    "label" => $formuleFils->getTitre(),
                    "id" => $formuleFils->getId(),
                    "titre" => $formuleFils->getTitre(),
                    "prix" => $formuleFils->getPrix(),
                    "qte" => $formuleFils->getQte(),
                    "qteMin" => $formuleFils->getQteMin(),
                    "qteMax" => $formuleFils->getQteMax(),
                    "description" => $langUserPhone == 'fr' ? $formuleFils->getDescription() : $formuleFils->getDescriptionEn(),
                ]);
            }

            array_push($listeFormulePromoReseau, [
                "id" => $formule->getId(),
                "titre" => $formule->getTitre(),
                "iconFlutterName" => $formule->getIconFlutterName(),
                "lesFormulesFils" => $lesFormulesFils,
            ]);
        }
        return new JsonResponse([
            'error' => false,
            'listeFormulePromoReseau' => $listeFormulePromoReseau,
        ]);
    }
}
