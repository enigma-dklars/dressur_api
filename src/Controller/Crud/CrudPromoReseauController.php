<?php

namespace App\Controller\Crud;

use App\Entity\PromoReseau;
use App\Form\PromoReseauType;
use App\Repository\PromoReseauRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Utilities\ZefameApi;
use DateTime;

#[Route('/crud/promo/reseau')]
class CrudPromoReseauController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_promo_reseau_index', methods: ['GET'])]
    public function index(PromoReseauRepository $promoReseauRepository, TraitementsDS $traitementsDS, Request $request): Response
    {
        $traitementsDS->checkAndUpdateStatusZefame();
        $sourceFilter = $request->query->get('source', '');

        if ($sourceFilter === 'none') {
            $promo_reseaus = $promoReseauRepository->findBy(['source' => null], ['id' => 'DESC']);
        } elseif (in_array($sourceFilter, ['web', 'mobile'])) {
            $promo_reseaus = $promoReseauRepository->findBy(['source' => $sourceFilter], ['id' => 'DESC']);
        } else {
            $promo_reseaus = $promoReseauRepository->findBy([], ['id' => 'DESC']);
        }

        return $this->render('crud_promo_reseau/index.html.twig', [
            'theme' => $this->theme,
            'soldeZefame' => $traitementsDS->getSoldeZefame(),
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseaus' => $promo_reseaus,
            'sourceFilter' => $sourceFilter,
            'sourceCounts' => $promoReseauRepository->getSourceCounts(),
        ]);
    }

    #[Route('/promo_reseau_en_attente', name: 'app_crud_promo_reseau_promo_reseau_en_attente', methods: ['GET'])]
    public function promo_reseau_en_attente(PromoReseauRepository $promoReseauRepository, TraitementsDS $traitementsDS): Response
    {
        $traitementsDS->checkAndUpdateStatusZefame();
        return $this->render('crud_promo_reseau/index.html.twig', [
            'theme' => $this->theme,
            'soldeZefame' => $traitementsDS->getSoldeZefame(),
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseaus' => $promoReseauRepository->findBy(['status' => 1], ['id' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_crud_promo_reseau_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $promoReseau = new PromoReseau();
        $form = $this->createForm(PromoReseauType::class, $promoReseau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($promoReseau);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_promo_reseau_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_promo_reseau/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseau' => $promoReseau,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_promo_reseau_show', methods: ['GET'])]
    public function show(PromoReseau $promoReseau): Response
    {
        return $this->render('crud_promo_reseau/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseau' => $promoReseau,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_promo_reseau_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, PromoReseau $promoReseau, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PromoReseauType::class, $promoReseau);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if($promoReseau->getStatus() == 3) {
                $promoReseau->setCompteurRestant(0);
            }
            if($promoReseau->getIdZefame() != "*****") {
                $promoReseau->setStatus(2);
            }
            $promoReseau->setUpdatedAt(new DateTime());
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_promo_reseau_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_promo_reseau/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'promo_reseau' => $promoReseau,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/demarrage_direct_zefame', name: 'app_crud_promo_reseau_demarrage_direct_zefame', methods: ['GET', 'POST'])]
    public function demarrage_direct_zefame(Request $request, PromoReseau $promoReseau, EntityManagerInterface $em, ZefameApi $zefame): Response
    {
        $formule = $promoReseau->getFormulePromoReseau();
        $formuleLower = mb_strtolower($formule, 'UTF-8'); // Utilise mb_strtolower pour bien gérer les accents et UTF-8
        if (strpos($formuleLower, 'commentaires') === false && strpos($formuleLower, 'customisés') === false && !empty($promoReseau->getFormulePromoReseau()->getIdZefame())) {
            $idServiveZefame = $promoReseau->getFormulePromoReseau()->getIdZefame();
            $linkPromo = $promoReseau->getUrl();
            $qte = $promoReseau->getQteDemander();
            $resultZefame = $zefame->order([
                'service' => $idServiveZefame, 
                'link' => $linkPromo, 
                'quantity' => $qte, 
                'runs' => 2, 
                'interval' => 5
            ]);

            if(isset($resultZefame->order)){
                $promoReseau->setIdZefame($resultZefame->order)
                    ->setStatus(2)
                ;
                $em->flush();
            } else if(isset($resultZefame->error)){
                $this->addFlash(
                    'danger',
                    $resultZefame->error
                );
            } else {
                $this->addFlash(
                    'danger',
                    "Résultat inatendu..."
                );
                $this->addFlash(
                    'danger',
                    (string)$resultZefame
                );
            }
        } else {
            $this->addFlash(
                'danger',
                "Impossible de demarrer directement..."
            );
        }

        return $this->redirectToRoute('app_crud_promo_reseau_promo_reseau_en_attente', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_crud_promo_reseau_delete', methods: ['POST'])]
    public function delete(Request $request, PromoReseau $promoReseau, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$promoReseau->getId(), $request->request->get('_token'))) {
            $entityManager->remove($promoReseau);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_promo_reseau_index', [], Response::HTTP_SEE_OTHER);
    }
}
