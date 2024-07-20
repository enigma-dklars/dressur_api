<?php

namespace App\Controller\Crud;

use App\Entity\CampagneMail;
use App\Entity\FileAttenteCampagneMail;
use App\Form\CampagneMailType;
use App\Repository\CampagneMailRepository;
use App\Repository\FileAttenteCampagneMailRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Utilities\SendMail;

#[Route('/crud/campagne/mail')]
class CrudCampagneMailController extends AbstractController
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
    
    #[Route('/', name: 'app_crud_campagne_mail_index', methods: ['GET'])]
    public function index(CampagneMailRepository $campagneMailRepository, FileAttenteCampagneMailRepository $fileAttenteCampagneMailRepository): Response
    {
        return $this->render('crud_campagne_mail/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'campagne_mails' => $campagneMailRepository->findBy([], ['id' => 'DESC']),
            'nbr_file_attente' => count($fileAttenteCampagneMailRepository->findAll()),
        ]);
    }

    #[Route('/pending', name: 'app_crud_campagne_mail_pending', methods: ['GET', 'POST'])]
    public function pending(EntityManagerInterface $entityManager, FileAttenteCampagneMailRepository $fileAttenteCampagneMailRepository, SendMail $sendMail): Response
    {
        set_time_limit(30000);
        ini_set("memory_limit", "-1");

        $cent_mails_pending = $fileAttenteCampagneMailRepository->findBy([], null, 25);
        foreach ($cent_mails_pending as $un_mail) {
            try {
                $sendMail->smtpMail(
                    $un_mail->getSendto(),
                    $un_mail->getSujet(),
                    $un_mail->getContentmail(),
                    $un_mail->getReplyto(),
                    $un_mail->getTitre()
                );
                $entityManager->remove($un_mail);
            } catch (\Throwable $th) {
                $sendMail->sendReport("File Attente Campage Mail", $th);
            }
        }
        $entityManager->flush();
        return $this->redirectToRoute('app_crud_campagne_mail_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/new', name: 'app_crud_campagne_mail_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $campagneMail = new CampagneMail();
        $form = $this->createForm(CampagneMailType::class, $campagneMail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($campagneMail);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_campagne_mail_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_campagne_mail/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'campagne_mail' => $campagneMail,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_campagne_mail_show', methods: ['GET'])]
    public function show(CampagneMail $campagneMail): Response
    {
        return $this->render('crud_campagne_mail/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'campagne_mail' => $campagneMail,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_campagne_mail_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CampagneMail $campagneMail, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CampagneMailType::class, $campagneMail);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_campagne_mail_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_campagne_mail/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'campagne_mail' => $campagneMail,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/start', name: 'app_crud_campagne_mail_start', methods: ['GET', 'POST'])]
    public function start(CampagneMail $campagneMail, EntityManagerInterface $entityManager): Response
    {
        $les_adresses_mails = explode(",", str_replace(" ", "", $campagneMail->getSendto()));
        foreach ($les_adresses_mails as $une_adresse_mail) {
            $html = $this->renderView("emails/camp_mail_1.html.twig",[
                'contentmail' => $campagneMail->getContentmail(),
            ]);

            $newFileAttenteCampagneMail = new FileAttenteCampagneMail();
            $newFileAttenteCampagneMail->setCampagneMail($campagneMail)
                ->setTitre($campagneMail->getTitre())
                ->setSujet($campagneMail->getSujet())
                ->setReplyto($campagneMail->getReplyto())
                ->setSendto($une_adresse_mail)
                ->setContentmail($html)
            ;
            $entityManager->persist($newFileAttenteCampagneMail);
        }
        $campagneMail->setTraitement(true);
        $entityManager->flush();
        return $this->redirectToRoute('app_crud_campagne_mail_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_crud_campagne_mail_delete', methods: ['POST'])]
    public function delete(Request $request, CampagneMail $campagneMail, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$campagneMail->getId(), $request->request->get('_token'))) {
            $entityManager->remove($campagneMail);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_campagne_mail_index', [], Response::HTTP_SEE_OTHER);
    }
}
