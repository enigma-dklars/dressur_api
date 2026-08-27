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
        LogBoiteMailRepository $logRepo
    ): Response {
        return $this->render('communication_mail/portal.html.twig', [
            'theme'               => $this->theme,
            'user'                => $this->traitementsDS->getUserByUidInCookies(),
            'nb_attente'          => $fileAttenteRepo->countByStatut('en_attente'),
            'nb_whatsapp_attente' => $whatsappRepo->countByStatut('en_attente'),
            'nb_prospects'        => $prospectRepo->countAll(),
            'nb_logs'             => $logRepo->countAll(),
        ]);
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

    /**
     * Conserve une seule entrée par adresse e-mail valide pour les campagnes mail.
     */
    private function uniqueMessagePersonnaliseAudienceMailUsers(array $users): array
    {
        $uniqueUsers = [];

        foreach ($users as $user) {
            $mail = strtolower(trim((string) ($user['mail'] ?? '')));
            if ($mail === '' || !filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            if (isset($uniqueUsers[$mail])) {
                continue;
            }

            $user['mail'] = $mail;
            $uniqueUsers[$mail] = $user;
        }

        return array_values($uniqueUsers);
    }

    #[Route('/message-personnalise-mail', name: 'app_communication_mail_message_personnalise_mail', methods: ['GET', 'POST'])]
    public function messagePersonnaliseMail(
        Request $request,
        BoostRepository $boostRepository,
        PromotionRepository $promotionRepository,
        PromoReseauRepository $promoReseauRepository,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $audiences = self::getMessagePersonnaliseAudiences();
        $replyto = 'dressur.ds@gmail.com';

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('message_personnalise_mail', $request->request->get('_token'))) {
                $this->addFlash('danger', 'Token CSRF invalide.');
                return $this->redirectToRoute('app_communication_mail_message_personnalise_mail');
            }

            $audienceKey = $request->request->get('audience', '');
            $subjectTemplate = trim($request->request->get('subject', ''));
            $messageTemplate = trim($request->request->get('message', ''));

            if (!isset($audiences[$audienceKey])) {
                $this->addFlash('danger', 'Audience invalide.');
                return $this->redirectToRoute('app_communication_mail_message_personnalise_mail');
            }

            if ($subjectTemplate === '') {
                $this->addFlash('danger', 'Le sujet du mail ne peut pas être vide.');
                return $this->redirectToRoute('app_communication_mail_message_personnalise_mail');
            }

            if ($messageTemplate === '') {
                $this->addFlash('danger', 'Le corps du mail ne peut pas être vide.');
                return $this->redirectToRoute('app_communication_mail_message_personnalise_mail');
            }

            $audienceConfig = $audiences[$audienceKey];
            $titre = $audienceConfig['titre'];
            $users = $this->uniqueMessagePersonnaliseAudienceMailUsers(
                $this->getMessagePersonnaliseAudienceUsers(
                    $audienceKey,
                    $boostRepository,
                    $promotionRepository,
                    $promoReseauRepository,
                    $userRepository
                )
            );

            $variables = ['{nom}', '{pseudo}', '{mail}', '{tel}', '{uid}'];
            $added = 0;

            foreach ($users as $user) {
                $values = [
                    trim((string) ($user['nom'] ?? '')),
                    trim((string) ($user['pseudo'] ?? '')),
                    trim((string) ($user['mail'] ?? '')),
                    trim((string) ($user['tel'] ?? '')),
                    trim((string) ($user['uid'] ?? '')),
                ];

                $subject = str_replace($variables, $values, $subjectTemplate);
                $message = str_replace($variables, $values, $messageTemplate);
                $message = nl2br(htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));

                $entry = (new FileAttenteProspectMail())
                    ->setSendto($user['mail'])
                    ->setTitre($titre)
                    ->setSujet($subject)
                    ->setReplyto($replyto)
                    ->setContentmail($message);

                $entityManager->persist($entry);
                $added++;
            }

            $entityManager->flush();

            $this->addFlash('success', $added . ' message(s) mail personnalisé(s) ajouté(s) à la file d\'attente.');
            return $this->redirectToRoute('app_communication_mail_message_personnalise_mail');
        }

        $audienceCounts = [];
        foreach (array_keys($audiences) as $audienceKey) {
            $audienceCounts[$audienceKey] = count(
                $this->uniqueMessagePersonnaliseAudienceMailUsers(
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

        return $this->render('communication_mail/message_personnalise_mail.html.twig', [
            'theme'            => $this->theme,
            'user'             => $this->traitementsDS->getUserByUidInCookies(),
            'audiences'        => $audiences,
            'audiencesByGroup' => $audiencesByGroup,
            'audienceCounts'   => $audienceCounts,
        ]);
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

    #[Route('/prospects/clear', name: 'app_communication_mail_prospects_clear', methods: ['POST'])]
    public function clearProspects(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->traitementsDS->getUserByUidInCookies();
        if (!$user || $user->getAdmin() !== true) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('clear_mail_prospects', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide. Les prospects n\'ont pas été supprimés.');
            return $this->redirectToRoute('app_communication_mail_prospects');
        }

        $deleted = $entityManager->createQuery(
            'DELETE FROM App\\Entity\\MailProspect p'
        )->execute();

        $this->addFlash('success', sprintf('%d prospect(s) supprimé(s). Les mails déjà en file d\'attente sont conservés.', $deleted));
        return $this->redirectToRoute('app_communication_mail_prospects');
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
            'total_logs'   => $logRepo->countAll(),
            'stats_sender' => $statsSender,
            'raisons'      => $raisons,
            'senders'      => $senders,
            'filters'      => $filters,
        ]);
    }

    #[Route('/log-boite-mail/clear', name: 'app_communication_mail_log_clear', methods: ['POST'])]
    public function clearLogBoiteMail(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->traitementsDS->getUserByUidInCookies();
        if (!$user || $user->getAdmin() !== true) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('clear_mail_logs', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide. Les logs n\'ont pas été supprimés.');
            return $this->redirectToRoute('app_communication_mail_log');
        }

        $deleted = $entityManager->createQuery(
            'DELETE FROM App\\Entity\\LogBoiteMail l'
        )->execute();

        $this->addFlash('success', sprintf('%d log(s) supprimé(s).', $deleted));
        return $this->redirectToRoute('app_communication_mail_log');
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
