<?php

namespace App\Controller\Crud;

use App\Entity\MailProspect;
use App\Repository\MailProspectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;

#[Route('/crud/communication-mail')]
class CommunicationMailController extends AbstractController
{
    private $theme;
    private $cookieDS;
    private $traitementsDS;

    public function __construct(CookieDS $cookieDS, TraitementsDS $traitementsDS)
    {
        $this->cookieDS = $cookieDS;
        $this->traitementsDS = $traitementsDS;
        if ($this->cookieDS->check("theme")) {
            if ($this->cookieDS->get("theme") == "dark-theme") {
                $this->theme = "dark-theme";
            } else {
                $this->theme = "light-theme";
            }
        } else {
            $this->theme = "light-theme";
        }
    }

    #[Route('/prospect', name: 'app_communication_mail_prospect_index', methods: ['GET'])]
    public function index(MailProspectRepository $mailProspectRepository): Response
    {
        return $this->render('communication_mail/prospect_index.html.twig', [
            'theme' => $this->theme,
            'user'  => $this->traitementsDS->getUserByUidInCookies(),
            'prospects' => $mailProspectRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/prospect/new', name: 'app_communication_mail_prospect_new', methods: ['GET', 'POST'])]
    public function newProspect(Request $request, EntityManagerInterface $entityManager, MailProspectRepository $mailProspectRepository): Response
    {
        $errors   = [];
        $imported = 0;
        $doublons = 0;

        if ($request->isMethod('POST')) {
            $rawEmails = $request->request->get('emails', '');

            // Sépare par virgule, point-virgule, espace, tabulation, nouvelle ligne
            $parts = preg_split('/[\s,;]+/', $rawEmails);

            foreach ($parts as $part) {
                $email = trim(strtolower($part));

                if ($email === '') {
                    continue;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = $email;
                    continue;
                }

                // Vérifie doublon en base
                $existing = $mailProspectRepository->findOneBy(['email' => $email]);
                if ($existing !== null) {
                    $doublons++;
                    continue;
                }

                $prospect = (new MailProspect())->setEmail($email);
                $entityManager->persist($prospect);
                $imported++;
            }

            $entityManager->flush();

            if ($imported > 0) {
                $this->addFlash('success', $imported . ' adresse(s) enregistrée(s) avec succès.');
            }
            if ($doublons > 0) {
                $this->addFlash('warning', $doublons . ' adresse(s) déjà présente(s) ignorée(s).');
            }
            if (!empty($errors)) {
                $this->addFlash('danger', 'Adresses invalides ignorées : ' . implode(', ', $errors));
            }

            return $this->redirectToRoute('app_communication_mail_prospect_index');
        }

        return $this->render('communication_mail/prospect_new.html.twig', [
            'theme' => $this->theme,
            'user'  => $this->traitementsDS->getUserByUidInCookies(),
        ]);
    }

    #[Route('/prospect/{id}/delete', name: 'app_communication_mail_prospect_delete', methods: ['POST'])]
    public function delete(Request $request, MailProspect $prospect, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $prospect->getId(), $request->request->get('_token'))) {
            $entityManager->remove($prospect);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_communication_mail_prospect_index', [], Response::HTTP_SEE_OTHER);
    }
}
