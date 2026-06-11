<?php

namespace App\Controller\Crud;

use App\Entity\MailProspect;
use App\Entity\FileAttenteProspectMail;
use App\Repository\MailProspectRepository;
use App\Repository\FileAttenteProspectMailRepository;
use App\Repository\LogBoiteMailRepository;
use App\Repository\UserRepository;
use App\Repository\BoostRepository;
use App\Repository\PromotionRepository;
use App\Repository\PromoReseauRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Utilities\SendMail;
use Symfony\Component\HttpFoundation\JsonResponse;

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
        MailProspectRepository $prospectRepo,
        LogBoiteMailRepository $logRepo,
        UserRepository $userRepository,
        BoostRepository $boostRepository,
        PromotionRepository $promotionRepository,
        PromoReseauRepository $promoReseauRepository
    ): Response {
        $allTypes     = self::getReactivationTypes();
        $inactifTypes = array_filter($allTypes, fn($t) => $t['group'] === 'inactif');
        $serviceTypes = array_filter($allTypes, fn($t) => $t['group'] === 'service');
        $confirmTypes = array_filter($allTypes, fn($t) => $t['group'] === 'confirm');

        $sqlErrors = [];

        $reactivation = [];
        foreach ($inactifTypes as $key => $cfg) {
            try {
                $nb = $userRepository->countInactiveUsersWithEmail($cfg['minDays'], $cfg['maxDays']);
            } catch (\Throwable $e) {
                $nb = 0;
                $sqlErrors[] = '[' . $key . '] ' . $e->getMessage();
            }
            $reactivation[] = array_merge($cfg, ['key' => $key, 'nb' => $nb]);
        }

        $services = [];
        foreach ($serviceTypes as $key => $cfg) {
            try {
                $nb = $this->countServiceCandidates($cfg, $boostRepository, $promotionRepository, $promoReseauRepository);
            } catch (\Throwable $e) {
                $nb = 0;
                $sqlErrors[] = '[' . $key . '] ' . $e->getMessage();
            }
            $services[] = array_merge($cfg, ['key' => $key, 'nb' => $nb]);
        }

        $confirm = [];
        foreach ($confirmTypes as $key => $cfg) {
            try {
                $nb = $userRepository->countUsersWithUnconfirmedMail();
            } catch (\Throwable $e) {
                $nb = 0;
                $sqlErrors[] = '[' . $key . '] ' . $e->getMessage();
            }
            $confirm[] = array_merge($cfg, ['key' => $key, 'nb' => $nb]);
        }

        if (!empty($sqlErrors)) {
            foreach ($sqlErrors as $err) {
                $this->addFlash('warning', 'Erreur comptage : ' . $err);
            }
        }

        return $this->render('communication_mail/portal.html.twig', [
            'theme'        => $this->theme,
            'user'         => $this->traitementsDS->getUserByUidInCookies(),
            'nb_attente'   => count($fileAttenteRepo->findBy(['statut' => 'en_attente'])),
            'nb_envoye'    => count($fileAttenteRepo->findBy(['statut' => 'envoye'])),
            'nb_prospects' => count($prospectRepo->findAll()),
            'nb_logs'      => count($logRepo->findAll()),
            'reactivation' => $reactivation,
            'services'     => $services,
            'confirm'      => $confirm,
        ]);
    }

    // ─── Réactivation : définitions des types ────────────────────────────────

    private static function getReactivationTypes(): array
    {
        return [
            // ── Inactivité générale ──────────────────────────────────────────
            '30j' => [
                'label'     => 'Inactifs depuis 30 à 60 jours',
                'minDays'   => 30,
                'maxDays'   => 60,
                'emoji'     => '💤',
                'color'     => 'warning',
                'sujet'     => 'Dressur vous attend — des opportunités vous ont manqué !',
                'titre'     => 'Des opportunités vous attendent sur Dressur',
                'desc'      => 'Rappel doux pour les utilisateurs récemment moins actifs.',
                'queryType' => 'inactif',
                'group'     => 'inactif',
            ],
            '60j' => [
                'label'     => 'Inactifs depuis 60 à 90 jours',
                'minDays'   => 60,
                'maxDays'   => 90,
                'emoji'     => '😴',
                'color'     => 'orange',
                'sujet'     => 'Ça fait un moment… Revenez découvrir les nouveautés Dressur !',
                'titre'     => 'Revenez sur Dressur — il y a du nouveau !',
                'desc'      => 'Relance avec mise en avant des nouveautés pour les utilisateurs absents.',
                'queryType' => 'inactif',
                'group'     => 'inactif',
            ],
            '90j' => [
                'label'     => 'Inactifs depuis plus de 90 jours',
                'minDays'   => 90,
                'maxDays'   => null,
                'emoji'     => '🚨',
                'color'     => 'danger',
                'sujet'     => 'Votre compte Dressur vous attend toujours !',
                'titre'     => 'Votre compte Dressur est toujours actif',
                'desc'      => 'Dernier rappel pour les utilisateurs très longtemps inactifs.',
                'queryType' => 'inactif',
                'group'     => 'inactif',
            ],
            // ── Relance par service ──────────────────────────────────────────
            'boost' => [
                'label'      => 'Boost Contact expiré (7 j)',
                'minDays'    => null,
                'maxDays'    => null,
                'maxDaysAgo' => 7,
                'emoji'      => '📢',
                'color'      => 'primary',
                'sujet'      => 'Votre Boost Contact a expiré — relancez votre visibilité !',
                'titre'      => 'Renouvelez votre Boost Contact sur Dressur',
                'desc'       => 'Utilisateurs dont le dernier Boost a expiré, sans Boost actif.',
                'queryType'  => 'service_boost',
                'group'      => 'service',
            ],
            'promo' => [
                'label'      => 'Promotion Affaire terminée (7 j)',
                'minDays'    => null,
                'maxDays'    => null,
                'maxDaysAgo' => 7,
                'emoji'      => '🎯',
                'color'      => 'success',
                'sujet'      => 'Votre Promotion Affaire est terminée — remettez-vous en avant !',
                'titre'      => 'Relancez votre Promotion Affaire sur Dressur',
                'desc'       => 'Utilisateurs dont la dernière Promo est terminée, sans Promo active.',
                'queryType'  => 'service_promo',
                'group'      => 'service',
            ],
            'reseau' => [
                'label'      => 'Promo Réseaux Sociaux terminée (15 j)',
                'minDays'    => null,
                'maxDays'    => null,
                'maxDaysAgo' => 15,
                'emoji'      => '📱',
                'color'      => 'info',
                'sujet'      => 'Boostez à nouveau vos réseaux sociaux avec Dressur !',
                'titre'      => 'Relancez votre Promotion Réseaux Sociaux',
                'desc'       => 'Utilisateurs dont la dernière Promo Réseau est terminée, sans commande active.',
                'queryType'  => 'service_reseau',
                'group'      => 'service',
            ],
            // ── Confirmation d'adresse mail ──────────────────────────────────
            'mail_non_confirme' => [
                'label'     => 'Adresse mail non confirmée',
                'minDays'   => null,
                'maxDays'   => null,
                'emoji'     => '✉️',
                'color'     => 'warning',
                'sujet'     => 'Confirmez votre adresse mail Dressur',
                'titre'     => 'Vérifiez votre adresse mail sur Dressur',
                'desc'      => 'Utilisateurs inscrits n\'ayant jamais confirmé leur adresse mail.',
                'queryType' => 'confirm_mail',
                'group'     => 'confirm',
            ],
        ];
    }

    private const REACTIVATION_COOLDOWN_DAYS = 5;

    // ─── Helper : tous les titres de réactivation (pour la détection des doublons) ─

    private static function getReactivationTitres(): array
    {
        return array_column(self::getReactivationTypes(), 'titre');
    }

    // ─── Helper : sépare les users en deux listes (à envoyer / déjà contactés) ─

    private static function splitByRecentContact(
        array $users,
        array $recentlyContactedEmails
    ): array {
        $excluded = [];
        $toSend   = [];

        foreach ($users as $u) {
            $mail = strtolower(trim((string) ($u['mail'] ?? '')));
            if ($mail === '') {
                continue;
            }
            if (in_array($mail, $recentlyContactedEmails, true)) {
                $excluded[] = $u;
            } else {
                $toSend[] = $u;
            }
        }

        return ['toSend' => $toSend, 'excluded' => $excluded];
    }

    // ─── Helper : récupère les candidats selon le type de campagne ────────────

    private function fetchCandidateUsers(
        array $config,
        UserRepository $userRepository,
        BoostRepository $boostRepository,
        PromotionRepository $promotionRepository,
        PromoReseauRepository $promoReseauRepository
    ): array {
        return match ($config['queryType'] ?? 'inactif') {
            'service_boost'  => $boostRepository->findUsersWithExpiredBoostAndEmail($config['maxDaysAgo'] ?? 90),
            'service_promo'  => $promotionRepository->findUsersWithTerminatedPromoAndEmail($config['maxDaysAgo'] ?? 90),
            'service_reseau' => $promoReseauRepository->findUsersWithTerminatedPromoReseauAndEmail($config['maxDaysAgo'] ?? 90),
            'confirm_mail'   => $userRepository->findUsersWithUnconfirmedMail(),
            default          => $userRepository->findInactiveUsersWithEmail($config['minDays'], $config['maxDays']),
        };
    }

    // ─── Helper : count rapide pour le portail ────────────────────────────────

    private function countServiceCandidates(
        array $config,
        BoostRepository $boostRepository,
        PromotionRepository $promotionRepository,
        PromoReseauRepository $promoReseauRepository
    ): int {
        return match ($config['queryType'] ?? 'inactif') {
            'service_boost'  => $boostRepository->countUsersWithExpiredBoostAndEmail($config['maxDaysAgo'] ?? 90),
            'service_promo'  => $promotionRepository->countUsersWithTerminatedPromoAndEmail($config['maxDaysAgo'] ?? 90),
            'service_reseau' => $promoReseauRepository->countUsersWithTerminatedPromoReseauAndEmail($config['maxDaysAgo'] ?? 90),
            default          => 0,
        };
    }

    // ─── Réactivation : aperçu ───────────────────────────────────────────────

    #[Route('/campagne/reactivation/{type}', name: 'app_communication_mail_campagne_reactivation', methods: ['GET'])]
    public function campagneReactivation(
        string $type,
        UserRepository $userRepository,
        BoostRepository $boostRepository,
        PromotionRepository $promotionRepository,
        PromoReseauRepository $promoReseauRepository,
        FileAttenteProspectMailRepository $fileAttenteRepo
    ): Response {
        $types = self::getReactivationTypes();
        if (!isset($types[$type])) {
            throw $this->createNotFoundException('Type de campagne inconnu.');
        }

        $config = $types[$type];
        $users  = $this->fetchCandidateUsers($config, $userRepository, $boostRepository, $promotionRepository, $promoReseauRepository);

        $allEmails    = array_filter(array_map(fn($u) => strtolower(trim((string)($u['mail'] ?? ''))), $users));
        $recentlySent = $fileAttenteRepo->findRecentlyContactedEmails(
            array_values($allEmails),
            self::REACTIVATION_COOLDOWN_DAYS,
            self::getReactivationTitres()
        );

        ['toSend' => $toSend, 'excluded' => $excluded] = self::splitByRecentContact($users, $recentlySent);

        return $this->render('communication_mail/campagne_reactivation.html.twig', [
            'theme'         => $this->theme,
            'user'          => $this->traitementsDS->getUserByUidInCookies(),
            'type'          => $type,
            'config'        => $config,
            'nb_to_send'    => count($toSend),
            'nb_excluded'   => count($excluded),
            'cooldown_days' => self::REACTIVATION_COOLDOWN_DAYS,
            'contentmail'   => self::buildMailContentForType($config),
            'sujet'         => $config['sujet'],
            'replyto'       => 'dressur.ds@gmail.com',
        ]);
    }

    // ─── Campagne : lancer en 1 clic (inactifs ou services) ──────────────────

    #[Route('/campagne/reactivation/{type}/lancer', name: 'app_communication_mail_campagne_reactivation_lancer', methods: ['POST'])]
    public function lancerReactivation(
        string $type,
        Request $request,
        UserRepository $userRepository,
        BoostRepository $boostRepository,
        PromotionRepository $promotionRepository,
        PromoReseauRepository $promoReseauRepository,
        FileAttenteProspectMailRepository $fileAttenteRepo,
        EntityManagerInterface $entityManager
    ): Response {
        $types = self::getReactivationTypes();
        if (!isset($types[$type])) {
            throw $this->createNotFoundException('Type de campagne inconnu.');
        }

        if (!$this->isCsrfTokenValid('reactivation_' . $type, $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_communication_mail_portal');
        }

        $config  = $types[$type];
        $users   = $this->fetchCandidateUsers($config, $userRepository, $boostRepository, $promotionRepository, $promoReseauRepository);
        $replyto = 'dressur.ds@gmail.com';

        $allEmails    = array_filter(array_map(fn($u) => strtolower(trim((string)($u['mail'] ?? ''))), $users));
        $recentlySent = $fileAttenteRepo->findRecentlyContactedEmails(
            array_values($allEmails),
            self::REACTIVATION_COOLDOWN_DAYS,
            self::getReactivationTitres()
        );

        ['toSend' => $toSend] = self::splitByRecentContact($users, $recentlySent);

        $added = 0;
        foreach ($toSend as $u) {
            $mail = trim((string) ($u['mail'] ?? ''));
            if ($mail === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $pseudo  = trim((string) ($u['pseudo'] ?? ''));
            $content = self::buildMailContentForType($config, $pseudo ?: null);

            $entry = (new FileAttenteProspectMail())
                ->setSendto($mail)
                ->setTitre($config['titre'])
                ->setSujet($config['sujet'])
                ->setReplyto($replyto)
                ->setContentmail($content);

            $entityManager->persist($entry);
            $added++;
        }

        $entityManager->flush();

        $skipped = count($users) - $added;
        $msg = $added . ' mail(s) ajouté(s) à la file d\'attente.';
        if ($skipped > 0) {
            $msg .= ' ' . $skipped . ' ignoré(s) (déjà contacté(s) dans les ' . self::REACTIVATION_COOLDOWN_DAYS . ' derniers jours).';
        }

        $this->addFlash('success', $msg);
        return $this->redirectToRoute('app_communication_mail_file_attente');
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

                if (in_array($email, $seen)) {
                    $doublons++;
                    continue;
                }
                $seen[] = $email;

                $existingProspect = $mailProspectRepository->findOneBy(['email' => $email]);
                if ($existingProspect === null) {
                    $prospect = (new MailProspect())->setEmail($email);
                    $entityManager->persist($prospect);
                } else {
                    $doublons++;
                }

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

    // ─── Traitement d'un lot de 10 mails en file d'attente (Ajax) ───────────

    #[Route('/file-attente/process-batch', name: 'app_communication_mail_file_attente_process_batch', methods: ['POST'])]
    public function processBatch(
        FileAttenteProspectMailRepository $fileAttenteRepo,
        EntityManagerInterface $entityManager,
        SendMail $sendMail
    ): JsonResponse {
        $entries = $fileAttenteRepo->findBy(
            ['statut' => 'en_attente'],
            ['id'     => 'ASC'],
            10
        );

        if (empty($entries)) {
            return $this->json([
                'processed' => 0,
                'results'   => [],
                'remaining' => 0,
            ]);
        }

        $results = [];
        foreach ($entries as $entry) {
            $sent   = $sendMail->smtpMail(
                $entry->getSendto(),
                $entry->getSujet(),
                $entry->getContentmail(),
                $entry->getReplyto(),
                $entry->getTitre(),
                'campagne_prospect'
            );
            $statut = $sent ? 'envoye' : 'erreur';
            $entry->setStatut($statut);
            $results[] = [
                'id'     => $entry->getId(),
                'sendto' => $entry->getSendto(),
                'statut' => $statut,
            ];
        }
        $entityManager->flush();

        $remaining = count($fileAttenteRepo->findBy(['statut' => 'en_attente']));

        return $this->json([
            'processed' => count($results),
            'results'   => $results,
            'remaining' => $remaining,
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

    // ─── Suppression multiple d'entrées file d'attente ───────────────────────

    #[Route('/file-attente/delete-multiple', name: 'app_communication_mail_file_attente_delete_multiple', methods: ['POST'])]
    public function deleteMultipleFileAttente(
        Request $request,
        FileAttenteProspectMailRepository $fileAttenteRepo,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('delete_multiple_file_attente', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_communication_mail_file_attente', [], Response::HTTP_SEE_OTHER);
        }

        $ids = $request->request->all('ids');

        if (empty($ids)) {
            $this->addFlash('warning', 'Aucun élément sélectionné.');
            return $this->redirectToRoute('app_communication_mail_file_attente', [], Response::HTTP_SEE_OTHER);
        }

        $deleted = 0;
        foreach ($ids as $id) {
            $entry = $fileAttenteRepo->find((int) $id);
            if ($entry) {
                $entityManager->remove($entry);
                $deleted++;
            }
        }
        $entityManager->flush();

        $this->addFlash('success', $deleted . ' entrée(s) supprimée(s).');

        return $this->redirectToRoute('app_communication_mail_file_attente', [], Response::HTTP_SEE_OTHER);
    }

    // ─── Historique des envois (LogBoiteMail) ────────────────────────────────

    #[Route('/log-boite-mail', name: 'app_communication_mail_log', methods: ['GET'])]
    public function logBoiteMail(
        Request $request,
        LogBoiteMailRepository $logRepo
    ): Response {
        $filters = [
            'raison'    => $request->query->get('raison', ''),
            'sender'    => $request->query->get('sender', ''),
            'date_from' => $request->query->get('date_from', ''),
            'date_to'   => $request->query->get('date_to', ''),
        ];

        $logs        = $logRepo->findFiltered($filters);
        $statsSender = $logRepo->getStatsBySender();
        $raisons     = $logRepo->getDistinctRaisons();
        $senders     = $logRepo->getDistinctSenders();

        return $this->render('communication_mail/log_boite_mail.html.twig', [
            'theme'        => $this->theme,
            'user'         => $this->traitementsDS->getUserByUidInCookies(),
            'logs'         => $logs,
            'stats_sender' => $statsSender,
            'raisons'      => $raisons,
            'senders'      => $senders,
            'filters'      => $filters,
        ]);
    }

    // ─── Dispatcher : choisit le bon template selon le type ──────────────────

    private static function buildMailContentForType(array $config, ?string $pseudo = null): string
    {
        return match ($config['queryType'] ?? 'inactif') {
            'service_boost'  => self::buildBoostMailContent($pseudo),
            'service_promo'  => self::buildPromoAffaireMailContent($pseudo),
            'service_reseau' => self::buildPromoReseauMailContent($pseudo),
            'confirm_mail'   => self::buildConfirmMailContent($pseudo),
            default          => self::buildReactivationMailContent($config['titre'], $pseudo),
        };
    }

    // ─── Contenu HTML : Confirmation d'adresse mail ──────────────────────────

    private static function buildConfirmMailContent(?string $pseudo = null): string
    {
        $salutation = $pseudo ? 'Bonjour <strong>' . htmlspecialchars($pseudo) . '</strong>,' : 'Bonjour,';

        return <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#212529;">

  <div style="background:#ffc107;padding:32px 24px;border-radius:8px 8px 0 0;text-align:center;">
    <img src="https://dressur.site/images/logo.png" alt="Dressur" style="height:48px;margin-bottom:12px;" onerror="this.style.display='none'">
    <h1 style="color:#212529;margin:0;font-size:24px;">✉️ Confirmez votre adresse mail</h1>
  </div>

  <div style="background:#ffffff;padding:32px 24px;border:1px solid #dee2e6;border-top:none;">

    <p>{$salutation}</p>

    <p>Vous êtes inscrit(e) sur <strong>Dressur</strong> mais votre adresse mail n'a pas encore été confirmée. Sans confirmation, certaines fonctionnalités sont limitées et vous ne pouvez pas recevoir nos notifications importantes.</p>

    <div style="background:#fff8e1;border-left:4px solid #ffc107;padding:16px;border-radius:0 6px 6px 0;margin:20px 0;">
      <strong style="color:#856404;">Pourquoi confirmer votre mail ?</strong>
      <ul style="margin:8px 0 0;padding-left:20px;color:#495057;">
        <li>Sécurisez votre compte contre toute prise en main non autorisée</li>
        <li>Recevez les notifications de vos contacts et commandes</li>
        <li>Accédez à toutes les fonctionnalités de la plateforme</li>
        <li>Récupérez facilement votre mot de passe si nécessaire</li>
      </ul>
    </div>

    <p>La confirmation ne prend que quelques secondes. Connectez-vous à votre compte et suivez les instructions pour valider votre adresse.</p>

    <div style="text-align:center;margin:32px 0;">
      <a href="https://dressur.site/profil"
         style="display:inline-block;padding:14px 36px;background:#ffc107;color:#212529;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;">
        ✉️ Confirmer mon adresse mail
      </a>
    </div>

    <p style="color:#6c757d;font-size:13px;border-top:1px solid #dee2e6;padding-top:16px;margin-top:16px;">
      <a href="https://dressur.site" style="color:#856404;">dressur.site</a> —
      <a href="mailto:dressur.ds@gmail.com" style="color:#856404;">dressur.ds@gmail.com</a>
    </p>

  </div>
</div>
HTML;
    }

    // ─── Contenu HTML : Boost Contact ────────────────────────────────────────

    private static function buildBoostMailContent(?string $pseudo = null): string
    {
        $salutation = $pseudo ? 'Bonjour <strong>' . htmlspecialchars($pseudo) . '</strong>,' : 'Bonjour,';

        return <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#212529;">

  <div style="background:#0d6efd;padding:32px 24px;border-radius:8px 8px 0 0;text-align:center;">
    <img src="https://dressur.site/images/logo.png" alt="Dressur" style="height:48px;margin-bottom:12px;" onerror="this.style.display='none'">
    <h1 style="color:white;margin:0;font-size:24px;">📢 Renouvelez votre Boost Contact</h1>
  </div>

  <div style="background:#ffffff;padding:32px 24px;border:1px solid #dee2e6;border-top:none;">

    <p>{$salutation}</p>

    <p>Votre <strong>Boost Contact</strong> sur Dressur a expiré. Sans Boost actif, votre profil est moins visible auprès de vos futurs clients et partenaires.</p>

    <div style="background:#e7f3ff;border-left:4px solid #0d6efd;padding:16px;border-radius:0 6px 6px 0;margin:20px 0;">
      <strong style="color:#0d6efd;">Qu'est-ce que vous perdez sans Boost ?</strong>
      <ul style="margin:8px 0 0;padding-left:20px;color:#495057;">
        <li>Votre profil n'apparaît plus en priorité dans les recherches</li>
        <li>Moins de demandes de contact directes</li>
        <li>Vos concurrents boostés vous devancent</li>
      </ul>
    </div>

    <p>Réactivez votre Boost dès maintenant et retrouvez votre visibilité sur la plateforme !</p>

    <div style="text-align:center;margin:32px 0;">
      <a href="https://dressur.site/boost"
         style="display:inline-block;padding:14px 36px;background:#0d6efd;color:white;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;">
        📢 Renouveler mon Boost Contact
      </a>
    </div>

    <p style="color:#6c757d;font-size:13px;border-top:1px solid #dee2e6;padding-top:16px;margin-top:16px;">
      <a href="https://dressur.site" style="color:#0d6efd;">dressur.site</a> —
      <a href="mailto:dressur.ds@gmail.com" style="color:#0d6efd;">dressur.ds@gmail.com</a>
    </p>

  </div>
</div>
HTML;
    }

    // ─── Contenu HTML : Promotion Affaire ────────────────────────────────────

    private static function buildPromoAffaireMailContent(?string $pseudo = null): string
    {
        $salutation = $pseudo ? 'Bonjour <strong>' . htmlspecialchars($pseudo) . '</strong>,' : 'Bonjour,';

        return <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#212529;">

  <div style="background:#198754;padding:32px 24px;border-radius:8px 8px 0 0;text-align:center;">
    <img src="https://dressur.site/images/logo.png" alt="Dressur" style="height:48px;margin-bottom:12px;" onerror="this.style.display='none'">
    <h1 style="color:white;margin:0;font-size:24px;">🎯 Relancez votre Promotion Affaire</h1>
  </div>

  <div style="background:#ffffff;padding:32px 24px;border:1px solid #dee2e6;border-top:none;">

    <p>{$salutation}</p>

    <p>Votre <strong>Promotion Affaire</strong> est arrivée à terme. C'est le moment idéal pour en lancer une nouvelle et remettre votre activité en avant auprès de toute la communauté Dressur !</p>

    <table style="width:100%;border-collapse:collapse;margin:16px 0;">
      <tr>
        <td style="padding:12px;background:#f0fdf4;border-radius:6px;width:48%;vertical-align:top;border:1px solid #bbf7d0;">
          <strong style="color:#198754;">📣 Produit ou Service</strong><br>
          <span style="font-size:13px;color:#6c757d;">Mettez en avant vos offres directement auprès des acheteurs.</span>
        </td>
        <td style="width:4%;"></td>
        <td style="padding:12px;background:#f0fdf4;border-radius:6px;width:48%;vertical-align:top;border:1px solid #bbf7d0;">
          <strong style="color:#198754;">💼 Offre ou Demande d'emploi</strong><br>
          <span style="font-size:13px;color:#6c757d;">Recrutez ou trouvez un poste dans votre domaine.</span>
        </td>
      </tr>
    </table>

    <p>Une promotion bien placée peut générer des dizaines de contacts qualifiés. Ne laissez pas vos concurrents occuper cet espace.</p>

    <div style="text-align:center;margin:32px 0;">
      <a href="https://dressur.site/promotion"
         style="display:inline-block;padding:14px 36px;background:#198754;color:white;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;">
        🎯 Lancer une nouvelle Promotion
      </a>
    </div>

    <p style="color:#6c757d;font-size:13px;border-top:1px solid #dee2e6;padding-top:16px;margin-top:16px;">
      <a href="https://dressur.site" style="color:#198754;">dressur.site</a> —
      <a href="mailto:dressur.ds@gmail.com" style="color:#198754;">dressur.ds@gmail.com</a>
    </p>

  </div>
</div>
HTML;
    }

    // ─── Contenu HTML : Promotion Réseaux Sociaux ────────────────────────────

    private static function buildPromoReseauMailContent(?string $pseudo = null): string
    {
        $salutation = $pseudo ? 'Bonjour <strong>' . htmlspecialchars($pseudo) . '</strong>,' : 'Bonjour,';

        return <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#212529;">

  <div style="background:#0dcaf0;padding:32px 24px;border-radius:8px 8px 0 0;text-align:center;">
    <img src="https://dressur.site/images/logo.png" alt="Dressur" style="height:48px;margin-bottom:12px;" onerror="this.style.display='none'">
    <h1 style="color:white;margin:0;font-size:24px;">📱 Boostez à nouveau vos réseaux !</h1>
  </div>

  <div style="background:#ffffff;padding:32px 24px;border:1px solid #dee2e6;border-top:none;">

    <p>{$salutation}</p>

    <p>Votre dernière <strong>Promotion Réseaux Sociaux</strong> est terminée. Votre compteur d'abonnés a progressé — il est temps de continuer sur cette lancée !</p>

    <div style="background:#e0f9ff;border-left:4px solid #0dcaf0;padding:16px;border-radius:0 6px 6px 0;margin:20px 0;">
      <strong style="color:#0dcaf0;">Nos services de promotion réseau</strong>
      <ul style="margin:8px 0 0;padding-left:20px;color:#495057;">
        <li><strong>TikTok</strong> — vues, likes, abonnés</li>
        <li><strong>Instagram</strong> — followers, likes, commentaires</li>
        <li><strong>YouTube</strong> — vues, abonnés, likes</li>
        <li><strong>Facebook, Twitter et plus…</strong></li>
      </ul>
    </div>

    <p>Commandez une nouvelle promotion en quelques clics et boostez votre présence en ligne dès aujourd'hui !</p>

    <div style="text-align:center;margin:32px 0;">
      <a href="https://dressur.site/promo-reseau"
         style="display:inline-block;padding:14px 36px;background:#0dcaf0;color:white;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;">
        📱 Lancer une nouvelle Promo Réseau
      </a>
    </div>

    <p style="color:#6c757d;font-size:13px;border-top:1px solid #dee2e6;padding-top:16px;margin-top:16px;">
      <a href="https://dressur.site" style="color:#0dcaf0;">dressur.site</a> —
      <a href="mailto:dressur.ds@gmail.com" style="color:#0dcaf0;">dressur.ds@gmail.com</a>
    </p>

  </div>
</div>
HTML;
    }

    // ─── Contenu HTML du mail de réactivation ────────────────────────────────

    private static function buildReactivationMailContent(string $titre, ?string $pseudo = null): string
    {
        $salutation = $pseudo ? "Bonjour <strong>" . htmlspecialchars($pseudo) . "</strong>," : "Bonjour,";

        return <<<HTML
<div style="font-family:Arial,sans-serif;max-width:600px;margin:0 auto;color:#212529;">

  <div style="background:#0d6efd;padding:32px 24px;border-radius:8px 8px 0 0;text-align:center;">
    <img src="https://dressur.site/images/logo.png" alt="Dressur" style="height:48px;margin-bottom:12px;" onerror="this.style.display='none'">
    <h1 style="color:white;margin:0;font-size:24px;">{$titre}</h1>
  </div>

  <div style="background:#ffffff;padding:32px 24px;border:1px solid #dee2e6;border-top:none;">

    <p>{$salutation}</p>

    <p>Nous avons remarqué que vous ne vous êtes plus connecté(e) à <strong>Dressur</strong> depuis un moment. Vous nous manquez !</p>

    <p>Depuis votre dernière visite, voici ce que vous avez peut-être manqué :</p>

    <table style="width:100%;border-collapse:collapse;margin:16px 0;">
      <tr>
        <td style="padding:10px;background:#f8f9fa;border-radius:6px;width:48%;vertical-align:top;">
          <strong style="color:#0d6efd;">📢 Boost Contact</strong><br>
          <span style="font-size:13px;color:#6c757d;">Soyez visible auprès de nouveaux clients dans votre zone.</span>
        </td>
        <td style="padding:10px;width:4%;"></td>
        <td style="padding:10px;background:#f8f9fa;border-radius:6px;width:48%;vertical-align:top;">
          <strong style="color:#0d6efd;">🎯 Promotion Affaire</strong><br>
          <span style="font-size:13px;color:#6c757d;">Promouvez vos produits et services à toute la communauté.</span>
        </td>
      </tr>
      <tr><td colspan="3" style="height:8px;"></td></tr>
      <tr>
        <td style="padding:10px;background:#f8f9fa;border-radius:6px;width:48%;vertical-align:top;">
          <strong style="color:#0d6efd;">📱 Réseaux Sociaux</strong><br>
          <span style="font-size:13px;color:#6c757d;">Boostez vos abonnés sur TikTok, Instagram, YouTube et plus.</span>
        </td>
        <td style="padding:10px;width:4%;"></td>
        <td style="padding:10px;background:#f8f9fa;border-radius:6px;width:48%;vertical-align:top;">
          <strong style="color:#0d6efd;">🏆 Programme Récompenses</strong><br>
          <span style="font-size:13px;color:#6c757d;">Vos points vous attendent ! Consultez votre solde de récompenses.</span>
        </td>
      </tr>
    </table>

    <p>Revenez dès maintenant — c'est <strong>100% gratuit</strong> et vos informations sont toujours là !</p>

    <div style="text-align:center;margin:32px 0;">
      <a href="https://dressur.site"
         style="display:inline-block;padding:14px 32px;background:#0d6efd;color:white;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;margin:0 8px 12px;">
        🌐 Revenir sur Dressur
      </a>
      <a href="https://play.google.com/store/apps/details?id=com.dressur.ds"
         style="display:inline-block;padding:14px 32px;background:#198754;color:white;text-decoration:none;border-radius:6px;font-weight:bold;font-size:16px;margin:0 8px 12px;">
        📱 Ouvrir l'application
      </a>
    </div>

    <p style="color:#6c757d;font-size:13px;border-top:1px solid #dee2e6;padding-top:16px;margin-top:16px;">
      Cet email vous a été envoyé par l'équipe Dressur car vous êtes inscrit(e) sur notre plateforme.<br>
      <a href="https://dressur.site" style="color:#0d6efd;">dressur.site</a> — 
      <a href="mailto:dressur.ds@gmail.com" style="color:#0d6efd;">dressur.ds@gmail.com</a>
    </p>

  </div>

</div>
HTML;
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
      <a href="https://play.google.com/store/apps/details?id=com.dressur.ds"
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
