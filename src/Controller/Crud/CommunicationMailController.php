<?php

namespace App\Controller\Crud;

use App\Entity\MailProspect;
use App\Entity\FileAttenteProspectMail;
use App\Repository\MailProspectRepository;
use App\Repository\FileAttenteProspectMailRepository;
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

    // ─── Portail ────────────────────────────────────────────────────────────────

    #[Route('/', name: 'app_communication_mail_portal', methods: ['GET'])]
    public function portal(
        FileAttenteProspectMailRepository $fileAttenteRepo,
        MailProspectRepository $prospectRepo
    ): Response {
        return $this->render('communication_mail/portal.html.twig', [
            'theme'        => $this->theme,
            'user'         => $this->traitementsDS->getUserByUidInCookies(),
            'nb_attente'   => count($fileAttenteRepo->findBy(['statut' => 'en_attente'])),
            'nb_envoye'    => count($fileAttenteRepo->findBy(['statut' => 'envoye'])),
            'nb_prospects' => count($prospectRepo->findAll()),
        ]);
    }

    // ─── Campagne : Attirer de nouveaux utilisateurs ─────────────────────────

    #[Route('/campagne/prospect', name: 'app_communication_mail_campagne_prospect', methods: ['GET', 'POST'])]
    public function campagneProspect(
        Request $request,
        EntityManagerInterface $entityManager,
        MailProspectRepository $mailProspectRepository
    ): Response {
        $replyto     = 'dressur.ds@gmail.com';
        $titre       = 'Boostez votre activité avec Dressur';
        $sujet       = 'Rejoignez Dressur – La plateforme qui vous connecte à de nouveaux clients';
        $contentmail = self::buildProspectMailContent();

        if ($request->isMethod('POST')) {
            $rawEmails = $request->request->get('emails', '');
            $parts     = preg_split('/[\s,;]+/', $rawEmails);

            $seen      = [];
            $imported  = 0;
            $doublons  = 0;
            $invalides = [];

            foreach ($parts as $part) {
                $email = trim(strtolower($part));

                if ($email === '') {
                    continue;
                }

                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $invalides[] = $email;
                    continue;
                }

                // Dédoublonnage dans la saisie courante
                if (in_array($email, $seen)) {
                    $doublons++;
                    continue;
                }
                $seen[] = $email;

                // Sauvegarde dans la base d'adresses (si nouvelle)
                $existingProspect = $mailProspectRepository->findOneBy(['email' => $email]);
                if ($existingProspect === null) {
                    $prospect = (new MailProspect())->setEmail($email);
                    $entityManager->persist($prospect);
                } else {
                    $doublons++;
                }

                // Ajout en file d'attente (toujours)
                $fileAttente = (new FileAttenteProspectMail())
                    ->setSendto($email)
                    ->setTitre($titre)
                    ->setSujet($sujet)
                    ->setReplyto($replyto)
                    ->setContentmail($contentmail)
                ;
                $entityManager->persist($fileAttente);
                $imported++;
            }

            $entityManager->flush();

            if ($imported > 0) {
                $this->addFlash('success', $imported . ' mail(s) ajouté(s) à la file d\'attente.');
            }
            if ($doublons > 0) {
                $this->addFlash('warning', $doublons . ' adresse(s) en doublon ignorée(s) de la base de prospects.');
            }
            if (!empty($invalides)) {
                $this->addFlash('danger', 'Adresses invalides ignorées : ' . implode(', ', $invalides));
            }

            return $this->redirectToRoute('app_communication_mail_file_attente');
        }

        return $this->render('communication_mail/campagne_prospect.html.twig', [
            'theme'       => $this->theme,
            'user'        => $this->traitementsDS->getUserByUidInCookies(),
            'titre'       => $titre,
            'sujet'       => $sujet,
            'replyto'     => $replyto,
            'contentmail' => $contentmail,
        ]);
    }

    // ─── Liste des adresses en base (MailProspect) ───────────────────────────

    #[Route('/prospects', name: 'app_communication_mail_prospects', methods: ['GET'])]
    public function prospects(MailProspectRepository $mailProspectRepository): Response
    {
        return $this->render('communication_mail/prospect_list.html.twig', [
            'theme'     => $this->theme,
            'user'      => $this->traitementsDS->getUserByUidInCookies(),
            'prospects' => $mailProspectRepository->findBy([], ['id' => 'DESC']),
        ]);
    }

    // ─── Suppression d'une adresse prospect ──────────────────────────────────

    #[Route('/prospects/{id}/delete', name: 'app_communication_mail_prospect_delete', methods: ['POST'])]
    public function deleteProspect(
        Request $request,
        MailProspect $prospect,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $prospect->getId(), $request->request->get('_token'))) {
            $entityManager->remove($prospect);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_communication_mail_prospects', [], Response::HTTP_SEE_OTHER);
    }

    // ─── File d'attente ──────────────────────────────────────────────────────

    #[Route('/file-attente', name: 'app_communication_mail_file_attente', methods: ['GET'])]
    public function fileAttente(FileAttenteProspectMailRepository $fileAttenteRepo): Response
    {
        return $this->render('communication_mail/file_attente.html.twig', [
            'theme'   => $this->theme,
            'user'    => $this->traitementsDS->getUserByUidInCookies(),
            'entries' => $fileAttenteRepo->findBy([], ['id' => 'DESC']),
        ]);
    }

    // ─── Suppression d'une entrée file d'attente ─────────────────────────────

    #[Route('/file-attente/{id}/delete', name: 'app_communication_mail_file_attente_delete', methods: ['POST'])]
    public function deleteFileAttente(
        Request $request,
        FileAttenteProspectMail $entry,
        EntityManagerInterface $entityManager
    ): Response {
        if ($this->isCsrfTokenValid('delete' . $entry->getId(), $request->request->get('_token'))) {
            $entityManager->remove($entry);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_communication_mail_file_attente', [], Response::HTTP_SEE_OTHER);
    }

    // ─── Contenu HTML du mail prospect ───────────────────────────────────────

    private static function buildProspectMailContent(): string
    {
        return <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#212529;">

  <div style="background:#0d6efd;padding:32px 24px;border-radius:8px 8px 0 0;text-align:center;">
    <h1 style="color:white;margin:0;font-size:26px;">Boostez votre activité avec Dressur</h1>
    <p style="color:#d0e8ff;margin:8px 0 0;">La plateforme qui vous connecte à de nouveaux clients</p>
  </div>

  <div style="background:#ffffff;padding:32px 24px;border:1px solid #dee2e6;border-top:none;">

    <p>Bonjour,</p>

    <p>Nous avons le plaisir de vous inviter à découvrir <strong>Dressur</strong>, la plateforme en ligne qui valorise votre activité et vous met en relation directe avec des clients à la recherche de vos services.</p>

    <p>Avec Dressur, c'est simple et efficace :</p>

    <ul style="padding-left:20px;line-height:2;">
      <li>✅ <strong>Créez votre profil professionnel</strong> en quelques minutes</li>
      <li>✅ <strong>Publiez des offres et des promotions</strong> visibles par tous</li>
      <li>✅ <strong>Soyez contacté directement</strong> par des clients intéressés</li>
      <li>✅ <strong>Gérez votre réputation</strong> et développez votre réseau</li>
    </ul>

    <p style="margin-top:24px;">Rejoignez des milliers de professionnels déjà présents sur Dressur — accessible sur le <strong>web</strong> et sur <strong>mobile</strong> (Play Store) !</p>

    <div style="text-align:center;margin:32px 0;">
      <a href="https://dressur.site"
         style="display:inline-block;padding:14px 28px;background:#0d6efd;color:white;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;margin:0 8px 12px;">
        🌐 Accéder à Dressur
      </a>
      <a href="https://play.google.com/store/apps/details?id=site.dressur.dressurapp"
         style="display:inline-block;padding:14px 28px;background:#198754;color:white;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;margin:0 8px 12px;">
        📱 Télécharger sur Play Store
      </a>
    </div>

    <p style="color:#6c757d;font-size:13px;border-top:1px solid #dee2e6;padding-top:16px;margin-top:16px;">
      Cet email vous a été envoyé par l'équipe Dressur.<br>
      <a href="https://dressur.site" style="color:#0d6efd;">dressur.site</a>
    </p>

  </div>

</div>
HTML;
    }
}
