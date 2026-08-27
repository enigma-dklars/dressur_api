<?php

namespace App\Controller\Crud;

use App\Entity\MailProspect;
use App\Entity\FileAttenteProspectMail;
use App\Entity\FileAttenteWhatsapp;
use App\Repository\MailProspectRepository;
use App\Repository\FileAttenteProspectMailRepository;
use App\Repository\FileAttenteWhatsappRepository;
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
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

#[Route('/crud/communication-mail')]
class CommunicationMailController extends AbstractController
{
    private $theme;
    private $cookieDS;
    private $traitementsDS;
    private $mot;

    public function __construct(CookieDS $cookieDS, TraitementsDS $traitementsDS)
    {
        $this->cookieDS = $cookieDS;
        $this->traitementsDS = $traitementsDS;
        $this->mot = $traitementsDS->genererMotAleatoire(rand(6,10));

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
        FileAttenteWhatsappRepository $whatsappRepo,
        MailProspectRepository $prospectRepo,
        LogBoiteMailRepository $logRepo,
        UserRepository $userRepository,
        BoostRepository $boostRepository,
        PromotionRepository $promotionRepository,
        PromoReseauRepository $promoReseauRepository,
        CacheInterface $cache
    ): Response {
        $allTypes        = self::getReactivationTypes();
        $inactifTypes    = array_filter($allTypes, fn($t) => $t['group'] === 'inactif');
        $serviceTypes    = array_filter($allTypes, fn($t) => $t['group'] === 'service');
        $serviceWaTypes  = array_filter($allTypes, fn($t) => $t['group'] === 'service_wa');
        $confirmTypes    = array_filter($allTypes, fn($t) => $t['group'] === 'confirm');
        $confirmWaTypes  = array_filter($allTypes, fn($t) => $t['group'] === 'confirm_wa');

        // ── Comptages mis en cache 10 minutes ────────────────────────────────────
        $counts = $cache->get('portal_campaign_counts', function (ItemInterface $item) use (
            $userRepository,
            $boostRepository,
            $promotionRepository,
            $promoReseauRepository,
            $inactifTypes,
            $serviceTypes,
            $serviceWaTypes,
            $confirmTypes,
            $confirmWaTypes
        ) {
            $item->expiresAfter(600); // 10 minutes
            $data = [];
            $errors = [];
            foreach ($inactifTypes as $key => $cfg) {
                try {
                    $data['inactif'][$key] = $userRepository->countInactiveUsersWithEmail($cfg['minDays'], $cfg['maxDays']);
                } catch (\Throwable $e) {
                    $data['inactif'][$key] = 0;
                    $errors[] = '[' . $key . '] ' . $e->getMessage();
                }
            }
            foreach ($serviceTypes as $key => $cfg) {
                try {
                    $data['service'][$key] = match ($cfg['queryType'] ?? '') {
                        'service_boost'  => $boostRepository->countUsersWithExpiredBoostAndEmail($cfg['maxDaysAgo'] ?? 90),
                        'service_promo'  => $promotionRepository->countUsersWithTerminatedPromoAndEmail($cfg['maxDaysAgo'] ?? 90),
                        'service_reseau' => $promoReseauRepository->countUsersWithTerminatedPromoReseauAndEmail($cfg['maxDaysAgo'] ?? 90),
                        default          => 0,
                    };
                } catch (\Throwable $e) {
                    $data['service'][$key] = 0;
                    $errors[] = '[' . $key . '] ' . $e->getMessage();
                }
            }
            foreach ($serviceWaTypes as $key => $cfg) {
                try {
                    $data['service_wa'][$key] = match ($cfg['queryType'] ?? '') {
                        'service_boost_wa'  => $boostRepository->countUsersWithExpiredBoostAndTel($cfg['maxDaysAgo'] ?? 90),
                        'service_promo_wa'  => $promotionRepository->countUsersWithTerminatedPromoAndTel($cfg['maxDaysAgo'] ?? 90),
                        'service_reseau_wa' => $promoReseauRepository->countUsersWithTerminatedPromoReseauAndTel($cfg['maxDaysAgo'] ?? 90),
                        default             => 0,
                    };
                } catch (\Throwable $e) {
                    $data['service_wa'][$key] = 0;
                    $errors[] = '[' . $key . '] ' . $e->getMessage();
                }
            }
            foreach ($confirmTypes as $key => $cfg) {
                try {
                    $data['confirm'][$key] = $userRepository->countUsersWithUnconfirmedMail();
                } catch (\Throwable $e) {
                    $data['confirm'][$key] = 0;
                    $errors[] = '[' . $key . '] ' . $e->getMessage();
                }
            }
            foreach ($confirmWaTypes as $key => $cfg) {
                try {
                    $data['confirm_wa'][$key] = $userRepository->countUsersWithUnconfirmedTel();
                } catch (\Throwable $e) {
                    $data['confirm_wa'][$key] = 0;
                    $errors[] = '[' . $key . '] ' . $e->getMessage();
                }
            }
            $data['_errors'] = $errors;
            return $data;
        });
        // ── Reconstitution des tableaux pour Twig à partir du cache ─────────────
        $sqlErrors = $counts['_errors'] ?? [];
        $reactivation = [];
        foreach ($inactifTypes as $key => $cfg) {
            $reactivation[] = array_merge($cfg, ['key' => $key, 'nb' => $counts['inactif'][$key] ?? 0]);
        }
        $services = [];
        foreach ($serviceTypes as $key => $cfg) {
            $services[] = array_merge($cfg, ['key' => $key, 'nb' => $counts['service'][$key] ?? 0]);
        }
        $servicesWa = [];
        foreach ($serviceWaTypes as $key => $cfg) {
            $servicesWa[] = array_merge($cfg, ['key' => $key, 'nb' => $counts['service_wa'][$key] ?? 0]);
        }
        $confirm = [];
        foreach ($confirmTypes as $key => $cfg) {
            $confirm[] = array_merge($cfg, ['key' => $key, 'nb' => $counts['confirm'][$key] ?? 0]);
        }
        $confirmWa = [];
        foreach ($confirmWaTypes as $key => $cfg) {
            $confirmWa[] = array_merge($cfg, ['key' => $key, 'nb' => $counts['confirm_wa'][$key] ?? 0]);
        }
        if (!empty($sqlErrors)) {
            foreach ($sqlErrors as $err) {
                $this->addFlash('warning', 'Erreur comptage : ' . $err);
            }
        }

        return $this->render('communication_mail/portal.html.twig', [
            'theme'               => $this->theme,
            'user'                => $this->traitementsDS->getUserByUidInCookies(),
            'nb_attente'          => $fileAttenteRepo->countByStatut('en_attente'),
            'nb_envoye'           => $fileAttenteRepo->countByStatut('envoye'),
            'nb_prospects'        => $prospectRepo->countAll(),
            'nb_logs'             => $logRepo->countAll(),
            'nb_whatsapp_attente' => $whatsappRepo->countByStatut('en_attente'),
            'reactivation'        => $reactivation,
            'services'            => $services,
            'services_wa'         => $servicesWa,
            'confirm'             => $confirm,
            'confirm_wa'          => $confirmWa,
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
                'channel'   => 'email',
            ],
            // ── Relance WhatsApp par service ─────────────────────────────────
            'boost_wa' => [
                'label'      => 'Boost Contact expiré (7 j) — WhatsApp',
                'minDays'    => null,
                'maxDays'    => null,
                'maxDaysAgo' => 7,
                'emoji'      => '📢',
                'color'      => 'primary',
                'sujet'      => 'Votre Boost Contact a expiré',
                'titre'      => 'Renouvelez votre Boost Contact sur Dressur',
                'desc'       => 'Utilisateurs avec Boost expiré (7 j) et numéro WhatsApp confirmé.',
                'queryType'  => 'service_boost_wa',
                'group'      => 'service_wa',
                'channel'    => 'whatsapp',
            ],
            'promo_wa' => [
                'label'      => 'Promotion Affaire terminée (7 j) — WhatsApp',
                'minDays'    => null,
                'maxDays'    => null,
                'maxDaysAgo' => 7,
                'emoji'      => '🎯',
                'color'      => 'success',
                'sujet'      => 'Votre Promotion Affaire est terminée',
                'titre'      => 'Relancez votre Promotion Affaire sur Dressur',
                'desc'       => 'Utilisateurs avec Promo terminée (7 j) et numéro WhatsApp confirmé.',
                'queryType'  => 'service_promo_wa',
                'group'      => 'service_wa',
                'channel'    => 'whatsapp',
            ],
            'reseau_wa' => [
                'label'      => 'Promo Réseaux Sociaux terminée (7 j) — WhatsApp',
                'minDays'    => null,
                'maxDays'    => null,
                'maxDaysAgo' => 7,
                'emoji'      => '📱',
                'color'      => 'info',
                'sujet'      => 'Votre Promo Réseaux est terminée',
                'titre'      => 'Relancez votre Promotion Réseaux Sociaux sur Dressur',
                'desc'       => 'Utilisateurs avec Promo Réseau terminée (7 j) et numéro WhatsApp confirmé.',
                'queryType'  => 'service_reseau_wa',
                'group'      => 'service_wa',
                'channel'    => 'whatsapp',
            ],
            // ── Confirmation numéro WhatsApp ─────────────────────────────────
            'tel_non_confirme' => [
                'label'     => 'Numéro de téléphone non confirmé',
                'minDays'   => null,
                'maxDays'   => null,
                'emoji'     => '📱',
                'color'     => 'success',
                'sujet'     => 'Confirmez votre numéro WhatsApp',
                'titre'     => 'Vérifiez votre numéro sur Dressur',
                'desc'      => 'Utilisateurs avec un numéro de téléphone non confirmé.',
                'queryType' => 'confirm_tel',
                'group'     => 'confirm_wa',
                'channel'   => 'whatsapp',
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
        array $recentlyContacted,
        string $contactKey = 'mail'
    ): array {
        $excluded = [];
        $toSend   = [];

        foreach ($users as $u) {
            $contact = strtolower(trim((string) ($u[$contactKey] ?? '')));
            if ($contact === '') {
                continue;
            }
            if (in_array($contact, $recentlyContacted, true)) {
                $excluded[] = $u;
            } else {
                $toSend[] = $u;
            }
        }

        return ['toSend' => $toSend, 'excluded' => $excluded];
    }

    // ─── Helper : récupère les candidats selon le type de campagne ────────────

    public function fetchCandidateUsers(
        array $config,
        UserRepository $userRepository,
        BoostRepository $boostRepository,
        PromotionRepository $promotionRepository,
        PromoReseauRepository $promoReseauRepository
    ): array {
        return match ($config['queryType'] ?? 'inactif') {
            'service_boost'      => $boostRepository->findUsersWithExpiredBoostAndEmail($config['maxDaysAgo'] ?? 90),
            'service_promo'      => $promotionRepository->findUsersWithTerminatedPromoAndEmail($config['maxDaysAgo'] ?? 90),
            'service_reseau'     => $promoReseauRepository->findUsersWithTerminatedPromoReseauAndEmail($config['maxDaysAgo'] ?? 90),
            'service_boost_wa'   => $boostRepository->findUsersWithExpiredBoostAndTel($config['maxDaysAgo'] ?? 90),
            'service_promo_wa'   => $promotionRepository->findUsersWithTerminatedPromoAndTel($config['maxDaysAgo'] ?? 90),
            'service_reseau_wa'  => $promoReseauRepository->findUsersWithTerminatedPromoReseauAndTel($config['maxDaysAgo'] ?? 90),
            'confirm_mail'       => $userRepository->findUsersWithUnconfirmedMail(),
            'confirm_tel'        => $userRepository->findUsersWithUnconfirmedTel(),
            default              => $userRepository->findInactiveUsersWithEmail($config['minDays'], $config['maxDays']),
        };
    }

    // ─── Helper : count rapide pour le portail ────────────────────────────────

    public function countServiceCandidates(
        array $config,
        BoostRepository $boostRepository,
        PromotionRepository $promotionRepository,
        PromoReseauRepository $promoReseauRepository
    ): int {
        return match ($config['queryType'] ?? 'inactif') {
            'service_boost'     => $boostRepository->countUsersWithExpiredBoostAndEmail($config['maxDaysAgo'] ?? 90),
            'service_promo'     => $promotionRepository->countUsersWithTerminatedPromoAndEmail($config['maxDaysAgo'] ?? 90),
            'service_reseau'    => $promoReseauRepository->countUsersWithTerminatedPromoReseauAndEmail($config['maxDaysAgo'] ?? 90),
            'service_boost_wa'  => $boostRepository->countUsersWithExpiredBoostAndTel($config['maxDaysAgo'] ?? 90),
            'service_promo_wa'  => $promotionRepository->countUsersWithTerminatedPromoAndTel($config['maxDaysAgo'] ?? 90),
            'service_reseau_wa' => $promoReseauRepository->countUsersWithTerminatedPromoReseauAndTel($config['maxDaysAgo'] ?? 90),
            default             => 0,
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
        FileAttenteProspectMailRepository $fileAttenteRepo,
        FileAttenteWhatsappRepository $whatsappRepo
    ): Response {
        $types = self::getReactivationTypes();
        if (!isset($types[$type])) {
            throw $this->createNotFoundException('Type de campagne inconnu.');
        }

        $config     = $types[$type];
        $isWhatsapp = ($config['channel'] ?? 'email') === 'whatsapp';
        $users      = $this->fetchCandidateUsers($config, $userRepository, $boostRepository, $promotionRepository, $promoReseauRepository);

        $cooldownPreview = self::REACTIVATION_COOLDOWN_DAYS;

        if ($isWhatsapp) {
            $allPhones    = array_filter(array_map(fn($u) => trim((string)($u['tel'] ?? '')), $users));
            $recentlySent = $whatsappRepo->findRecentlyContactedPhones(
                array_values($allPhones),
                $cooldownPreview,
                [$config['titre']]
            );
            ['toSend' => $toSend, 'excluded' => $excluded] = self::splitByRecentContact($users, $recentlySent, 'tel');
        } else {
            $allEmails    = array_filter(array_map(fn($u) => strtolower(trim((string)($u['mail'] ?? ''))), $users));
            $recentlySent = $fileAttenteRepo->findRecentlyContactedEmails(
                array_values($allEmails),
                $cooldownPreview,
                [$config['titre']]
            );
            ['toSend' => $toSend, 'excluded' => $excluded] = self::splitByRecentContact($users, $recentlySent);
        }

        $previewContent = $isWhatsapp
            ? $this->buildWhatsappMessageForType($config, null)
            : self::buildMailContentForType(
                $config,
                null,
                ($config['queryType'] ?? '') === 'confirm_mail'
                    ? 'https://dressur.site/confirmer-mail/[uid-utilisateur]/[token-securise]'
                    : null
            );

        return $this->render('communication_mail/campagne_reactivation.html.twig', [
            'theme'         => $this->theme,
            'user'          => $this->traitementsDS->getUserByUidInCookies(),
            'type'          => $type,
            'config'        => $config,
            'nb_to_send'    => count($toSend),
            'nb_excluded'   => count($excluded),
            'cooldown_days' => $cooldownPreview,
            'contentmail'   => $previewContent,
            'sujet'         => $config['sujet'],
            'replyto'       => 'dressur.ds@gmail.com',
            'is_whatsapp'   => $isWhatsapp,
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
        FileAttenteWhatsappRepository $whatsappFileRepo,
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

        $config     = $types[$type];
        $isWhatsapp = ($config['channel'] ?? 'email') === 'whatsapp';
        $users      = $this->fetchCandidateUsers($config, $userRepository, $boostRepository, $promotionRepository, $promoReseauRepository);

        // ── Canal WhatsApp ────────────────────────────────────────────────────
        if ($isWhatsapp) {
            $cooldown = self::REACTIVATION_COOLDOWN_DAYS;

            $allPhones    = array_filter(array_map(fn($u) => trim((string)($u['tel'] ?? '')), $users));
            $recentlySent = $whatsappFileRepo->findRecentlyContactedPhones(
                array_values($allPhones),
                $cooldown,
                [$config['titre']]
            );
            ['toSend' => $toSend] = self::splitByRecentContact($users, $recentlySent, 'tel');

            $added = 0;
            foreach ($toSend as $u) {
                $tel = trim((string) ($u['tel'] ?? ''));
                if ($tel === '') {
                    continue;
                }

                $pseudo = trim((string) ($u['pseudo'] ?? ''));
                $nom    = trim((string) ($u['nom'] ?? ''));
                $uid    = trim((string) ($u['uid'] ?? ''));

                $confirmUrl = null;
                if (($config['queryType'] ?? '') === 'confirm_tel' && $uid !== '') {
                    $token      = $this->generateConfirmTelToken($uid, $tel);
                    $confirmUrl = 'https://dressur.site/confirmer-tel/' . rawurlencode($uid) . '/' . $token;
                }

                $message = $this->buildWhatsappMessageForType($config, $pseudo ?: null, $confirmUrl, $nom ?: null);

                $entry = (new FileAttenteWhatsapp())
                    ->setSendto($tel)
                    ->setTitre($config['titre'])
                    ->setMessage($message);

                $entityManager->persist($entry);
                $added++;
            }

            $entityManager->flush();

            $skipped = count($users) - $added;
            $msg = $added . ' message(s) WhatsApp ajouté(s) à la file d\'attente.';
            if ($skipped > 0) {
                $msg .= ' ' . $skipped . ' ignoré(s) (déjà contacté(s) dans les ' . $cooldown . ' derniers jours).';
            }

            $this->addFlash('success', $msg);
            return $this->redirectToRoute('app_communication_mail_portal');
        }

        // ── Canal Email (comportement par défaut) ─────────────────────────────
        $replyto = 'dressur.ds@gmail.com';

        $allEmails    = array_filter(array_map(fn($u) => strtolower(trim((string)($u['mail'] ?? ''))), $users));
        $recentlySent = $fileAttenteRepo->findRecentlyContactedEmails(
            array_values($allEmails),
            self::REACTIVATION_COOLDOWN_DAYS,
            [$config['titre']]
        );

        ['toSend' => $toSend] = self::splitByRecentContact($users, $recentlySent);

        $added = 0;
        foreach ($toSend as $u) {
            $mail = trim((string) ($u['mail'] ?? ''));
            if ($mail === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $pseudo  = trim((string) ($u['pseudo'] ?? ''));
            $uid     = trim((string) ($u['uid'] ?? ''));

            $confirmUrl = null;
            if (($config['queryType'] ?? '') === 'confirm_mail' && $uid !== '') {
                $token      = $this->generateConfirmToken($uid, $mail);
                $confirmUrl = 'https://dressur.site/confirmer-mail/' . rawurlencode($uid) . '/' . $token;
            }

            $content = self::buildMailContentForType($config, $pseudo ?: null, $confirmUrl);

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

    // ─── File d'attente WhatsApp ─────────────────────────────────────────────

    #[Route('/file-attente-whatsapp', name: 'app_communication_mail_file_attente_whatsapp', methods: ['GET'])]
    public function fileAttenteWhatsapp(FileAttenteWhatsappRepository $whatsappRepo): Response
    {
        return $this->render('communication_mail/file_attente_whatsapp.html.twig', [
            'theme'   => $this->theme,
            'user'    => $this->traitementsDS->getUserByUidInCookies(),
            'entries' => $whatsappRepo->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/file-attente-whatsapp/json', name: 'app_communication_mail_file_attente_whatsapp_json', methods: ['GET'])]
    public function fileAttenteWhatsappJson(FileAttenteWhatsappRepository $whatsappRepo, UserRepository $userRepo): JsonResponse
    {
        $entries = $whatsappRepo->findBy(['statut' => 'en_attente'], ['id' => 'ASC']);

        $data = array_map(function ($e) use ($userRepo) {
            $user  = $userRepo->findOneBy(['tel' => $e->getSendto()]);
            $wa_id = $user?->getLid();

            return [
                'numero'  => $e->getSendto(),
                'message' => $e->getMessage(),
                'wa_id'   => $wa_id,
            ];
        }, $entries);

        return $this->json($data);
    }

    #[Route('/file-attente-whatsapp/{id}/delete', name: 'app_communication_mail_file_attente_whatsapp_delete', methods: ['POST'])]
    public function deleteFileAttenteWhatsapp(
        Request $request,
        int $id,
        FileAttenteWhatsappRepository $whatsappRepo,
        EntityManagerInterface $entityManager
    ): Response {
        $entry = $whatsappRepo->find($id);
        if ($entry && $this->isCsrfTokenValid('delete_wa' . $id, $request->request->get('_token'))) {
            $entityManager->remove($entry);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_communication_mail_file_attente_whatsapp', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/file-attente-whatsapp/delete-multiple', name: 'app_communication_mail_file_attente_whatsapp_delete_multiple', methods: ['POST'])]
    public function deleteMultipleFileAttenteWhatsapp(
        Request $request,
        FileAttenteWhatsappRepository $whatsappRepo,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('delete_multiple_wa', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_communication_mail_file_attente_whatsapp', [], Response::HTTP_SEE_OTHER);
        }

        $ids = $request->request->all('ids');
        if (empty($ids)) {
            $this->addFlash('warning', 'Aucun élément sélectionné.');
            return $this->redirectToRoute('app_communication_mail_file_attente_whatsapp', [], Response::HTTP_SEE_OTHER);
        }

        $deleted = 0;
        foreach ($ids as $id) {
            $entry = $whatsappRepo->find((int) $id);
            if ($entry) {
                $entityManager->remove($entry);
                $deleted++;
            }
        }
        $entityManager->flush();

        $this->addFlash('success', $deleted . ' entrée(s) supprimée(s).');
        return $this->redirectToRoute('app_communication_mail_file_attente_whatsapp', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/file-attente-whatsapp/delete-all', name: 'app_communication_mail_file_attente_whatsapp_delete_all', methods: ['POST'])]
    public function deleteAllFileAttenteWhatsapp(
        Request $request,
        FileAttenteWhatsappRepository $whatsappRepo,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('delete_all_wa', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_communication_mail_file_attente_whatsapp', [], Response::HTTP_SEE_OTHER);
        }

        $deleted = $entityManager->createQuery(
            'DELETE FROM App\Entity\FileAttenteWhatsapp e'
        )->execute();
        $entityManager->flush();

        $this->addFlash('success', $deleted . ' entrée(s) supprimée(s) — file d\'attente WhatsApp vidée.');
        return $this->redirectToRoute('app_communication_mail_file_attente_whatsapp', [], Response::HTTP_SEE_OTHER);
    }

    // ─── Message personnalisé WhatsApp ───────────────────────────────────────

    private static function getMessagePersonnaliseAudiences(): array
    {
        return [
            'boost_all' => [
                'label' => 'Utilisateurs Boost Contact',
                'group' => 'Historique des services',
                'description' => 'A déjà utilisé Boost Contact au moins une fois.',
                'emoji' => '📢',
                'titre' => 'Message Personnalisé — Boost Contact',
            ],
            'boost_gratuit' => [
                'label' => 'Boost Contact gratuit uniquement',
                'group' => 'Historique des services',
                'description' => 'A utilisé uniquement le mode gratuit.',
                'emoji' => '🎁',
                'titre' => 'Message Personnalisé — Boost Contact Gratuit',
            ],
            'boost_payant' => [
                'label' => 'Boost Contact payant uniquement',
                'group' => 'Historique des services',
                'description' => 'A utilisé uniquement le mode payant.',
                'emoji' => '💳',
                'titre' => 'Message Personnalisé — Boost Contact Payant',
            ],
            'boost_mixte' => [
                'label' => 'Boost Contact payant et gratuit',
                'group' => 'Historique des services',
                'description' => 'A utilisé les modes gratuit et payant.',
                'emoji' => '🔁',
                'titre' => 'Message Personnalisé — Boost Contact Payant et Gratuit',
            ],
            'promo_all' => [
                'label' => 'Utilisateurs Promotion Affaire',
                'group' => 'Historique des services',
                'description' => 'A déjà utilisé Promotion Affaire au moins une fois.',
                'emoji' => '🎯',
                'titre' => 'Message Personnalisé — Promotion Affaire',
            ],
            'reseau_all' => [
                'label' => 'Utilisateurs Promotion Réseaux Sociaux',
                'group' => 'Historique des services',
                'description' => 'A déjà utilisé Promotion Réseaux Sociaux au moins une fois.',
                'emoji' => '📱',
                'titre' => 'Message Personnalisé — Promotion Réseaux Sociaux',
            ],
            'boost_dernier_ancien' => [
                'label' => 'Dernier Boost Contact il y a au moins 7 jours',
                'group' => 'Relancer les anciens utilisateurs',
                'description' => 'Le dernier Boost Contact remonte à au moins une semaine.',
                'emoji' => '⏳',
                'titre' => 'Message Personnalisé — Boost Contact ancien',
            ],
            'boost_dernier_14' => [
                'label' => 'Dernier Boost Contact il y a au moins 14 jours',
                'group' => 'Relancer les anciens utilisateurs',
                'description' => 'Le dernier Boost Contact remonte à au moins deux semaines.',
                'emoji' => '🕒',
                'titre' => 'Message Personnalisé — Boost Contact inactif depuis 14 jours',
            ],
            'boost_dernier_30' => [
                'label' => 'Dernier Boost Contact il y a au moins 30 jours',
                'group' => 'Relancer les anciens utilisateurs',
                'description' => 'Le dernier Boost Contact remonte à au moins un mois.',
                'emoji' => '📅',
                'titre' => 'Message Personnalisé — Boost Contact inactif depuis 30 jours',
            ],
            'boost_sans_promo' => [
                'label' => 'Boost Contact sans Promotion Affaire',
                'group' => 'Faire découvrir un autre service',
                'description' => 'A utilisé Boost Contact mais jamais Promotion Affaire.',
                'emoji' => '↗️',
                'titre' => 'Message Personnalisé — Découverte Promotion Affaire',
            ],
            'promo_sans_boost' => [
                'label' => 'Promotion Affaire sans Boost Contact',
                'group' => 'Faire découvrir un autre service',
                'description' => 'A utilisé Promotion Affaire mais jamais Boost Contact.',
                'emoji' => '↗️',
                'titre' => 'Message Personnalisé — Découverte Boost Contact',
            ],
            'reseau_sans_boost' => [
                'label' => 'Promotion Réseaux Sociaux sans Boost Contact',
                'group' => 'Faire découvrir un autre service',
                'description' => 'A utilisé Promotion Réseaux Sociaux mais jamais Boost Contact.',
                'emoji' => '↗️',
                'titre' => 'Message Personnalisé — Découverte Boost Contact',
            ],
            'promo_terminee' => [
                'label' => 'Promotion Affaire terminée sans campagne active',
                'group' => 'Renouveler une campagne',
                'description' => 'A terminé une promotion et n’en a pas d’active actuellement.',
                'emoji' => '🔄',
                'titre' => 'Message Personnalisé — Renouvellement Promotion Affaire',
            ],
            'promo_refusee' => [
                'label' => 'Promotion Affaire refusée',
                'group' => 'Renouveler une campagne',
                'description' => 'Possède au moins une promotion refusée à corriger ou relancer.',
                'emoji' => '🛠️',
                'titre' => 'Message Personnalisé — Promotion Affaire à corriger',
            ],
            'reseau_terminee' => [
                'label' => 'Promotion Réseaux Sociaux terminée sans campagne active',
                'group' => 'Renouveler une campagne',
                'description' => 'A terminé une promotion réseau et n’en a pas d’active actuellement.',
                'emoji' => '🔄',
                'titre' => 'Message Personnalisé — Renouvellement Promotion Réseaux Sociaux',
            ],
            'sans_service' => [
                'label' => 'Inscrits n’ayant encore utilisé aucun service',
                'group' => 'Première activation',
                'description' => 'Numéro WhatsApp confirmé, mais aucun Boost ou aucune promotion utilisée.',
                'emoji' => '👋',
                'titre' => 'Message Personnalisé — Première activation',
            ],
            'nouveau_sans_service' => [
                'label' => 'Nouveaux inscrits sans service depuis 30 jours',
                'group' => 'Première activation',
                'description' => 'Compte récent, sans aucun service utilisé depuis l’inscription.',
                'emoji' => '🌱',
                'titre' => 'Message Personnalisé — Bienvenue sur Dressur',
            ],
            'inactif_30' => [
                'label' => 'Comptes sans connexion depuis 30 jours',
                'group' => 'Réactivation du compte',
                'description' => 'Dernière connexion datant d’au moins 30 jours.',
                'emoji' => '💬',
                'titre' => 'Message Personnalisé — Retour sur Dressur',
            ],
            'inactif_90' => [
                'label' => 'Comptes sans connexion depuis 90 jours',
                'group' => 'Réactivation du compte',
                'description' => 'Dernière connexion datant d’au moins 90 jours.',
                'emoji' => '📣',
                'titre' => 'Message Personnalisé — Vous nous manquez',
            ],
        ];
    }

    /**
     * Retourne les utilisateurs correspondant à une audience WhatsApp.
     */
    private function getMessagePersonnaliseAudienceUsers(
        string $audienceKey,
        BoostRepository $boostRepository,
        PromotionRepository $promotionRepository,
        PromoReseauRepository $promoReseauRepository,
        UserRepository $userRepository
    ): array {
        return match ($audienceKey) {
            'boost_all'            => $boostRepository->findUsersWhoEverUsedBoostAndTelWithDetails(),
            'boost_gratuit'        => $boostRepository->findUsersWhoEverUsedOnlyBoostModeAndTelWithDetails('Gratuit', 'Payant'),
            'boost_payant'         => $boostRepository->findUsersWhoEverUsedOnlyBoostModeAndTelWithDetails('Payant', 'Gratuit'),
            'boost_mixte'          => $boostRepository->findUsersWhoEverUsedBoostWithBothModesAndTelWithDetails(),
            'boost_dernier_ancien' => $boostRepository->findUsersWhoseLastBoostIsAtLeastDaysAgoWithDetails(7),
            'boost_dernier_14'     => $boostRepository->findUsersWhoseLastBoostIsAtLeastDaysAgoWithDetails(14),
            'boost_dernier_30'     => $boostRepository->findUsersWhoseLastBoostIsAtLeastDaysAgoWithDetails(30),
            'promo_all'            => $promotionRepository->findUsersWhoEverUsedPromoAndTelWithDetails(),
            'reseau_all'           => $promoReseauRepository->findUsersWhoEverUsedPromoReseauAndTelWithDetails(),
            'boost_sans_promo'     => $userRepository->findUsersWithBoostAndWithoutPromotionAndTelWithDetails(),
            'promo_sans_boost'     => $userRepository->findUsersWithPromotionAndWithoutBoostAndTelWithDetails(),
            'reseau_sans_boost'    => $userRepository->findUsersWithPromoReseauAndWithoutBoostAndTelWithDetails(),
            'promo_terminee'      => $promotionRepository->findUsersWithFinishedPromoAndNoActiveAndTelWithDetails(),
            'promo_refusee'       => $promotionRepository->findUsersWithRefusedPromoAndTelWithDetails(),
            'reseau_terminee'     => $promoReseauRepository->findUsersWithFinishedPromoReseauAndNoActiveAndTelWithDetails(),
            'sans_service'       => $userRepository->findUsersWithoutServiceAndTelWithDetails(),
            'nouveau_sans_service' => $userRepository->findNewUsersWithoutServiceAndTelWithDetails(30),
            'inactif_30'         => $userRepository->findInactiveUsersWithTelWithDetails(30),
            'inactif_90'         => $userRepository->findInactiveUsersWithTelWithDetails(90),
            default              => [],
        };
    }

    /**
     * Conserve une seule ligne par utilisateur. Le numéro est un repli pour
     * rester robuste si une ancienne requête ne fournit pas encore user_id.
     */
    private function uniqueMessagePersonnaliseAudienceUsers(array $users): array
    {
        $uniqueUsers = [];

        foreach ($users as $user) {
            $tel = trim((string) ($user['tel'] ?? ''));
            if ($tel === '') {
                continue;
            }

            $userId = trim((string) ($user['user_id'] ?? ''));
            $uniqueKey = $userId !== '' ? 'id:' . $userId : 'tel:' . $tel;
            if (isset($uniqueUsers[$uniqueKey])) {
                continue;
            }

            $uniqueUsers[$uniqueKey] = $user;
        }

        return array_values($uniqueUsers);
    }

    #[Route('/message-personnalise-whatsapp', name: 'app_communication_mail_message_personnalise_whatsapp', methods: ['GET', 'POST'])]
    public function messagePersonnaliseWhatsapp(
        Request $request,
        BoostRepository $boostRepository,
        PromotionRepository $promotionRepository,
        PromoReseauRepository $promoReseauRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $audiences = self::getMessagePersonnaliseAudiences();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('message_personnalise_whatsapp', $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_communication_mail_message_personnalise_whatsapp');
            }

            $audienceKey = $request->request->get('audience', '');
            $messageTemplate = trim($request->request->get('message', ''));

            if (!isset($audiences[$audienceKey])) {
                $this->addFlash('danger', 'Audience invalide.');
                return $this->redirectToRoute('app_communication_mail_message_personnalise_whatsapp');
            }

            if ($messageTemplate === '') {
                $this->addFlash('danger', 'Le message ne peut pas être vide.');
                return $this->redirectToRoute('app_communication_mail_message_personnalise_whatsapp');
            }

            $audienceConfig = $audiences[$audienceKey];
            $titre = $audienceConfig['titre'];

            $users = $this->uniqueMessagePersonnaliseAudienceUsers(
                $this->getMessagePersonnaliseAudienceUsers(
                    $audienceKey,
                    $boostRepository,
                    $promotionRepository,
                    $promoReseauRepository,
                    $userRepository
                )
            );

            $added = 0;
            foreach ($users as $u) {
                $tel = trim((string) ($u['tel'] ?? ''));
                if ($tel === '') {
                    continue;
                }

                $message = str_replace(
                    ['{nom}', '{pseudo}', '{mail}', '{tel}', '{uid}'],
                    [
                        trim((string) ($u['nom']    ?? '')),
                        trim((string) ($u['pseudo'] ?? '')),
                        trim((string) ($u['mail']   ?? '')),
                        $tel,
                        trim((string) ($u['uid']    ?? '')),
                    ],
                    $messageTemplate
                );

                $entry = (new FileAttenteWhatsapp())
                    ->setSendto($tel)
                    ->setTitre($titre)
                    ->setMessage($message);

                $entityManager->persist($entry);
                $added++;
            }

            $entityManager->flush();

            $this->addFlash('success', $added . ' message(s) personnalisé(s) ajouté(s) à la file d\'attente WhatsApp.');
            return $this->redirectToRoute('app_communication_mail_message_personnalise_whatsapp');
        }

        $audienceCounts = [];
        foreach (array_keys($audiences) as $audienceKey) {
            $audienceCounts[$audienceKey] = count(
                $this->uniqueMessagePersonnaliseAudienceUsers(
                    $this->getMessagePersonnaliseAudienceUsers(
                        $audienceKey,
                        $boostRepository,
                        $promotionRepository,
                        $promoReseauRepository,
                        $userRepository
                    )
                )
            );
        }

        $audiencesByGroup = [];
        foreach ($audiences as $key => $audience) {
            $audiencesByGroup[$audience['group']][$key] = $audience;
        }

        return $this->render('communication_mail/message_personnalise_whatsapp.html.twig', [
            'theme'            => $this->theme,
            'user'             => $this->traitementsDS->getUserByUidInCookies(),
            'audiences'        => $audiences,
            'audiencesByGroup' => $audiencesByGroup,
            'audienceCounts'   => $audienceCounts,
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

    #[Route('/file-attente/delete-all', name: 'app_communication_mail_file_attente_delete_all', methods: ['POST'])]
    public function deleteAllFileAttente(
        Request $request,
        FileAttenteProspectMailRepository $fileAttenteRepo,
        EntityManagerInterface $entityManager
    ): Response {
        if (!$this->isCsrfTokenValid('delete_all_file_attente', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_communication_mail_file_attente', [], Response::HTTP_SEE_OTHER);
        }

        $deleted = $entityManager->createQuery(
            'DELETE FROM App\Entity\FileAttenteProspectMail e'
        )->execute();
        $entityManager->flush();

        $this->addFlash('success', $deleted . ' entrée(s) supprimée(s) — file d\'attente mail vidée.');
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

    private static function buildMailContentForType(array $config, ?string $pseudo = null, ?string $confirmUrl = null): string
    {
        return match ($config['queryType'] ?? 'inactif') {
            'service_boost'  => self::buildBoostMailContent($pseudo),
            'service_promo'  => self::buildPromoAffaireMailContent($pseudo),
            'service_reseau' => self::buildPromoReseauMailContent($pseudo),
            'confirm_mail'   => self::buildConfirmMailContent($pseudo, $confirmUrl),
            default          => self::buildReactivationMailContent($config['titre'], $pseudo),
        };
    }

    public function generateConfirmToken(string $uid, string $mail): string
    {
        $secret = $this->getParameter('kernel.secret');
        return substr(hash_hmac('sha256', $uid . ':' . strtolower(trim($mail)), $secret), 0, 40);
    }

    public function generateConfirmTelToken(string $uid, string $tel): string
    {
        $secret = $this->getParameter('kernel.secret');
        return substr(hash_hmac('sha256', $uid . ':' . strtolower(trim($tel)), $secret), 0, 40);
    }

    private function buildWhatsappMessageForType(array $config, ?string $pseudo = null, ?string $confirmUrl = null, ?string $nom = null): string
    {
        return match ($config['queryType'] ?? 'confirm_tel') {
            'service_boost_wa'   => $this->buildWhatsappBoostMessage($pseudo),
            'service_promo_wa'   => $this->buildWhatsappPromoAffaireMessage($pseudo),
            'service_reseau_wa'  => $this->buildWhatsappPromoReseauMessage($pseudo),
            default              => $this->buildWhatsappConfirmMessage($pseudo, $confirmUrl),
        };
    }

    public function buildWhatsappBoostMessage(?string $pseudo = null): string
    {
        $salutation = $pseudo ? 'Bonjour ' . $pseudo . ' 👋' : 'Bonjour 👋';

        return $salutation . "\n\n"
            . "Vous avez récemment utilisé notre service *Boost Contact* sur *Dressur* 📢\n\n"
            . "Un petit avis pour nous aider à nous améliorer ? 💙\n\n"
            . "— L'équipe Dressur — TICKET—".$this->traitementsDS->genererMotAleatoire(rand(6, 10));
    }

    public function buildWhatsappPromoAffaireMessage(?string $pseudo = null): string
    {
        $salutation = $pseudo ? 'Bonjour ' . $pseudo . ' 👋' : 'Bonjour 👋';

        return $salutation . "\n\n"
            . "Vous avez récemment utilisé notre service *Promotion Affaire* sur *Dressur* 🎯\n\n"
            . "Un petit avis pour nous aider à nous améliorer ? 💙\n\n"
            . "— L'équipe Dressur — TICKET—".$this->traitementsDS->genererMotAleatoire(rand(6, 10));
    }

    public function buildWhatsappPromoReseauMessage(?string $pseudo = null): string
    {
        $salutation = $pseudo ? 'Bonjour ' . $pseudo . ' 👋' : 'Bonjour 👋';

        return $salutation . "\n\n"
            . "Vous avez récemment utilisé notre service *Promotion Réseaux Sociaux* sur *Dressur* 📱\n\n"
            . "Un petit avis pour nous aider à nous améliorer ? 💙\n\n"
            . "— L'équipe Dressur — TICKET—".$this->traitementsDS->genererMotAleatoire(rand(6, 10));
    }

    public function buildWhatsappConfirmMessage(?string $pseudo = null, ?string $confirmUrl = null): string
    {
        $salutation = $pseudo ? 'Bonjour ' . $pseudo . ' 👋' : 'Bonjour 👋';
        $url        = $confirmUrl ?? 'https://dressur.site/confirmer-tel/[uid]/[token]';

        return $salutation . "\n\n"
            . "Votre numéro a été enregistré sur *Dressur* 🐴\n"
            . "Confirmez-le en un clic :\n"
            . $url . "\n\n"
            . "🚫 *Vous n'êtes pas à l'origine de cette inscription ?*\n"
            . "Répondez simplement *SUPPRIMER* à ce message et nous supprimerons votre numéro immédiatement.\n\n"
            . "— L'équipe Dressur — TICKET—".$this->traitementsDS->genererMotAleatoire(rand(6, 10));
    }

    // ─── Contenu HTML : Confirmation d'adresse mail ──────────────────────────

    private static function buildConfirmMailContent(?string $pseudo = null, ?string $confirmUrl = null): string
    {
        $salutation = $pseudo ? 'Bonjour <strong>' . htmlspecialchars($pseudo) . '</strong>,' : 'Bonjour,';
        $btnUrl     = $confirmUrl ?? 'https://dressur.site/profil';

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
      <a href="{$btnUrl}"
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
