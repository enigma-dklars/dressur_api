<?php

namespace App\Controller\Crud;

use App\Entity\User;
use App\Form\UserType;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/crud/user')]
class CrudUserController extends AbstractController
{
    private $em;
    private $env;
    private $theme;
    private $cookieDS;
    private $traitementsDS;

    public function __construct(EntityManagerInterface $em, CookieDS $cookieDS, TraitementsDS $traitementsDS, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1);
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

    private function buildContactCounts(iterable $users, array $allUserIds): array
    {
        $allUserIdsFlip = array_flip($allUserIds);
        $counts = [];
        foreach ($users as $user) {
            $contact = $user->getContact();
            $whoIAdd = $contact ? $contact->getWhoIAdd() : [];
            $whoAddMe = $contact ? $contact->getWhoAddMe() : [];
            $merged = array_unique(array_merge($whoIAdd, $whoAddMe));
            $counts[$user->getId()] = count(array_intersect_key(array_flip($merged), $allUserIdsFlip));
        }
        return $counts;
    }

    #[Route('/', name: 'app_crud_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search', '');
        $sourceFilter = $request->query->get('source', '');
        $limit = 100; // Nombre d'utilisateurs par page

        $usersPaginator = $userRepository->findAllPaginatedFiltered($search, $sourceFilter, $page, $limit);
        
        $totalItems = $usersPaginator->count();
        $totalPages = ceil($totalItems / $limit);

        $allUserIds = $userRepository->findAllIds();
        $contactCounts = $this->buildContactCounts($usersPaginator, $allUserIds);
        $collectionCounts = $userRepository->findCollectionCountsByUserIds(array_keys($contactCounts));
        
        return $this->render('crud_user/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'users' => $usersPaginator,
            'contactCounts' => $contactCounts,
            'collectionCounts' => $collectionCounts,
            'option' => "All",
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'search' => $search,
            'limit' => $limit,
            'sourceFilter' => $sourceFilter,
            'sourceCounts' => $userRepository->getRegisterSourceCounts()
        ]);
    }

    #[Route('/users-inutiles', name: 'app_crud_user_inutiles', methods: ['GET'])]
    public function inutiles(UserRepository $userRepository): Response
    {
        return $this->render('crud_user/users-inutiles.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'users' => $userRepository->findBy([], ['id' => 'DESC']),
            'option' => "Users Inutiles",
        ]);
    }

    #[Route('/not-verif-tel', name: 'app_crud_user_not_verif_tel', methods: ['GET'])]
    public function not_verif_tel(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy(['telIsVerified' => false], ['id' => 'DESC']);
        $allUserIds = $userRepository->findAllIds();
        $contactCounts = $this->buildContactCounts($users, $allUserIds);
        return $this->render('crud_user/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'users' => $users,
            'contactCounts' => $contactCounts,
            'collectionCounts' => $userRepository->findCollectionCountsByUserIds(array_keys($contactCounts)),
            'option' => "Tel Not Verified",
            'currentPage' => "",
            'totalPages' => "",
            'totalItems' => "",
            'search' => "",
            'limit' => ""
        ]);
    }

    #[Route('/not-verif-mail', name: 'app_crud_user_not_verif_mail', methods: ['GET'])]
    public function not_verif_mail(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy(['mailIsVerified' => false], ['id' => 'DESC']);
        $allUserIds = $userRepository->findAllIds();
        $contactCounts = $this->buildContactCounts($users, $allUserIds);
        return $this->render('crud_user/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'users' => $users,
            'contactCounts' => $contactCounts,
            'collectionCounts' => $userRepository->findCollectionCountsByUserIds(array_keys($contactCounts)),
            'option' => "Mail Not Verified",
            'currentPage' => "",
            'totalPages' => "",
            'totalItems' => "",
            'search' => "",
            'limit' => ""
        ]);
    }

    #[Route('/not-verif-tel-mail', name: 'app_crud_user_not_verif_tel_mail', methods: ['GET'])]
    public function not_verif_tel_mail(UserRepository $userRepository): Response
    {
        $users = $userRepository->findBy(['mailIsVerified' => false, 'telIsVerified' => false], ['id' => 'DESC']);
        $allUserIds = $userRepository->findAllIds();
        $contactCounts = $this->buildContactCounts($users, $allUserIds);
        return $this->render('crud_user/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'users' => $users,
            'contactCounts' => $contactCounts,
            'collectionCounts' => $userRepository->findCollectionCountsByUserIds(array_keys($contactCounts)),
            'option' => "Tel Mail Not Verified",
            'currentPage' => "",
            'totalPages' => "",
            'totalItems' => "",
            'search' => "",
            'limit' => ""
        ]);
    }

    #[Route('/supprimer-user-inutile', name: 'app_crud_user_supprimer_user_inutile', methods: ['POST'])]
    public function supprimer_user_inutile(Request $request, UserRepository $userRepository, TraitementsDS $traitementsDS): Response
    {
        if (!$this->isCsrfTokenValid('supprimer_user_inutile', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_crud_user_check');
        }
        foreach ($userRepository->findBy(['mailIsVerified' => false, 'telIsVerified' => false], [], 20) as $user) {
            $traitementsDS->execPurge($user);
        }
        $this->addFlash('success', '20 user inutile supprimer.');
        return $this->redirectToRoute('app_crud_user_check');
    }

    #[Route('/new', name: 'app_crud_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_user/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/check', name: 'app_crud_user_check', methods: ['GET', 'POST'])]
    public function check(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, TraitementsDS $traitementsDS): Response
    {
        $user = null;
        $paysChoisies = [];

        // Process the form submission
        if ($request->isMethod('POST')) {
            $input = $request->request->get('identifier');
            $input = str_replace(" ", "", $input);
            $input = str_replace("      ", "", $input);

            $found = [];
            foreach (['pseudo', 'mail', 'uid', 'id', 'tel'] as $field) {
                foreach ($userRepository->findBy([$field => $input]) as $u) {
                    $found[$u->getId()] = $u;
                }
            }

            if (strpos($input, '+229') === 0 && strpos($input, '+22901') !== 0) {
                $teladd1 = str_replace('+229', '+22901', $input);
                foreach ($userRepository->findBy(['tel' => $teladd1]) as $u) {
                    $found[$u->getId()] = $u;
                }
            }

            $user_array = [];

            if (count($found) > 1) {
                $this->addFlash('warning', count($found) . ' comptes trouvés pour "' . $input . '".');
            } elseif (count($found) === 1) {
                $user = array_values($found)[0];
                $this->addFlash('success', 'User found.');
                $user_array['user_info'] = $user;
                $paysChoisies = $user->getPreference()->getPaysChoisies();
            } else {
                // Add a flash message if user is not found
                $this->addFlash('danger', 'User not found.');
            }
        }

        return $this->render('crud_user/check_user.html.twig', [
            'theme' => $this->theme,
            'users' => $userRepository->findAll(),
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'user_check' => $user ? $user_array : null,
            'nbr_pays_preference' => count($paysChoisies),
            'pays_preference' => implode(", ", $paysChoisies),
        ]);
    }

    #[Route('/check-and-confirme', name: 'app_crud_user_check_and_confirme', methods: ['GET', 'POST'])]
    public function check_and_confirme(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, TraitementsDS $traitementsDS): Response
    {
        $user = null;
        $message = [];

        // Process the form submission
        if ($request->isMethod('POST')) {
            $input = $request->request->get('identifier');
            $input = str_replace(" ", "", $input);
            $input = str_replace("      ", "", $input);

            $found = [];
            foreach (['pseudo', 'mail', 'uid', 'id', 'tel'] as $field) {
                foreach ($userRepository->findBy([$field => $input]) as $u) {
                    $found[$u->getId()] = $u;
                }
            }

            if (strpos($input, '+229') === 0 && strpos($input, '+22901') !== 0) {
                $teladd1 = str_replace('+229', '+22901', $input);
                foreach ($userRepository->findBy(['tel' => $teladd1]) as $u) {
                    $found[$u->getId()] = $u;
                }
            }

            $user_array = [];

            if (count($found) > 1) {
                $this->addFlash('warning', count($found) . ' comptes trouvés pour "' . $input . '". Veuillez préciser la recherche.');
            } elseif (count($found) === 1) {
                $user = array_values($found)[0];
                $this->addFlash('info', 'User found.');

                if(!$user->getMailIsVerified()) {
                    $message[] = "Vous n'avez pas confirmer votre adresse mail.";
                }
                if(empty($user->getNom())) {
                    $message[] = "Veuillez complété votre profil (au minimum nom et prenom ou nom d'entreprise).";
                }

                if(!$user->getTelIsVerified()) {
                    if($user->getMailIsVerified() and !empty($user->getNom())) {
                        $user->setTelIsVerified(true);
                        $entityManager->flush();
                        $this->addFlash('success', 'Le numéro WhatsApp a été confirmé avec succès.');
                    } else {
                        $this->addFlash('danger', 'Echec de confirmation du numéro fournis.');
                    }
                } else {
                    $message[] = "Le numéro WhatsApp (".$user->getTel().") avais déja été confirmé.";
                }

                $user_array['user_info'] = $user;
            } else {
                $this->addFlash('danger', 'User not found.');
                $message[] = "Aucune correspondance avec le " . $input;
            }
        }

        return $this->render('crud_user/check_and_confirme.html.twig', [
            'theme' => $this->theme,
            'users' => $userRepository->findAll(),
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'user_check' => $user ? $user_array : null,
            'message' => implode("<br>", $message),
        ]);
    }

    #[Route('/find_whatsapp_is_activatable/{lid}', name: 'app_crud_user_find_whatsapp_is_activatable', methods: ['GET', 'POST'])]
    public function find_whatsapp_is_activatable(Request $request, string $lid, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $lid = trim($lid);

        if ($lid === '') {
            return new Response("Veuillez utiliser le numéro WhatsApp lié à votre compte Dressur pour effectuer la demande de confirmation du numéro WhatsApp.");
        }

        $matches = $userRepository->findBy(['lid' => $lid]);

        if (count($matches) > 1) {
            return new Response("Apparemment, vous avez plusieurs comptes Dressur liés à ce numéro.\nVeuillez patienter, un assistant vous aidera sous peu.");
        }

        if (count($matches) === 0) {
            return new Response("Veuillez utiliser le numéro WhatsApp lié à votre compte Dressur pour effectuer la demande de confirmation du numéro WhatsApp.");
        }

        $user = $matches[0];

        if ($user->getTelIsVerified() == true) {
            return new Response("Le compte lié à ce numéro est déjà confirmé.");
        }

        $message = "";
        if (empty($user->getNom())) {
            $message .= "Veuillez ajouter votre nom et prénom(s) sur Dressur.\n";
        }
        if ($user->getMailIsVerified() == false) {
            $message .= "Veuillez confirmer votre adresse e-mail sur Dressur.\n";
        }

        if ($message == "") {
            $user->setTelIsVerified(true);
            $em->flush();
            return new Response("✅ Votre numéro WhatsApp a été confirmé avec succès.\n\nVous pouvez donc profiter pleinement des fonctionnalités de Dressur :\n\n* Boost Contact (ADD)\n* Promotion Affaire\n* Promotion Réseau Sociaux\n* Participé au programme des récompenses\n\n📢 *Suivez la chaîne sur WhatsApp* pour rester informé de toutes les nouveautés :  \nwhatsapp.com/channel/0029Vag8B6cCBtxMRvCqaA3t\n\nNous restons disponibles pour toutes vos préoccupations.");
        }

        return new Response("$message\nVous pouvez renvoyer une nouvelle demande de confirmation après avoir rempli les exigences mentionnées.");
    }

    #[Route('/find_all_info_with_tel_user/{search}', name: 'app_crud_user_find_all_info_with_tel_user', methods: ['GET', 'POST'])]
    public function find_all_info_with_tel_user(Request $request, string $search, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = null;

        // Tentative 1 : recherche par numéro de téléphone (ajout du préfixe +)
        $telInput = "+" . str_replace([" ", "  "], "", $search);
        $user = $userRepository->findOneBy(['tel' => $telInput]);

        // Tentative 2 : recherche par LID si non trouvé par numéro
        if (!$user) {
            $user = $userRepository->findOneBy(['lid' => $search]);
        }

        if ($user) {
            $lines = [];

            $lines[] = "Pseudo : " . ($user->getPseudo() ?? '—');
            $lines[] = "Nom : " . ($user->getNom() ?? '—');
            $lines[] = "Numéro WhatsApp : " . ($user->getTel() ?? '—');
            $lines[] = "LID : " . ($user->getLid() ?? '—');
            $lines[] = "Adresse e-mail : " . ($user->getMail() ?? '—');

            $lines[] = "Pays : " . ($user->getPays() ?? '—');

            $createdAt = $user->getCreatedAt();
            $lines[] = "Date de création du compte : " . ($createdAt instanceof \DateTimeInterface ? $createdAt->format('Y-m-d H:i:s') : '—');

            $lines[] = "À propos : " . ($user->getApropos() ?? '—');

            $lines[] = "Confirmation du numéro WhatsApp : " . ($user->getTelIsVerified() ? "Oui" : "Non");
            $lines[] = "Confirmation de l'adresse e-mail : " . ($user->getMailIsVerified() ? "Oui" : "Non");

            $lastLogin = $user->getLastLoginTo();
            $lines[] = "Date de dernière connexion : " . ($lastLogin instanceof \DateTimeInterface ? $lastLogin->format('Y-m-d H:i:s') : '—');

            $boosts = $user->getBoosts();
            $promotions = $user->getPromotions();
            $promoReseaus = $user->getPromoReseaus();

            $lines[] = "Nombre de Boost Contact effectués : " . (is_countable($boosts) ? count($boosts) : 0);
            $lines[] = "Nombre de Promotion Affaire effectuées : " . (is_countable($promotions) ? count($promotions) : 0);
            $lines[] = "Nombre de Promotion Réseaux Sociaux effectuées : " . (is_countable($promoReseaus) ? count($promoReseaus) : 0);

            $prefs = $user->getPreference();
            $paysChoisis = [];
            if ($prefs && method_exists($prefs, 'getPaysChoisies')) {
                $paysChoisis = $prefs->getPaysChoisies() ?: [];
            }
            $lines[] = "Les préférences pays : " . (is_array($paysChoisis) ? implode(',', $paysChoisis) : '—');

            $infos = implode("\n", $lines);

            return new Response($infos);
        }

        return new Response("⚠️ Aucune information disponible sur cet utilisateur. Il ne possède pas encore de compte Dressur.");
    }

    #[Route('/find_number_not_have_lid', name: 'app_crud_user_find_number_not_have_lid', methods: ['GET'])]
    public function findNumberNotHaveLid(UserRepository $userRepository): JsonResponse
    { 
        try {
            $rows = $userRepository->findUsersWithTelAndWithoutLid();

            $numbers = [];
            foreach ($rows as $row) {
                $tel = $row['tel'] ?? null;
                if ($tel === null) {
                    continue;
                }
                $tel = str_replace(['+', ' ', "\t", "\n", "\r"], '', $tel);
                if ($tel === '') {
                    continue;
                }
                $numbers[$tel] = $tel;
            }
            shuffle($numbers);

            return new JsonResponse(array_values($numbers), Response::HTTP_OK);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/number_and_lid', name: 'app_crud_user_number_and_lid', methods: ['POST'])]
    public function numberAndLid(Request $request, UserRepository $userRepository, LoggerInterface $logger): Response
    {
        try {
            $body = json_decode($request->getContent(), true);

            if (!is_array($body) || !isset($body['number_and_lid']) || !is_array($body['number_and_lid'])) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Clé "number_and_lid" manquante ou invalide.',
                ], Response::HTTP_BAD_REQUEST);
            }

            $pairs = $body['number_and_lid'];
            $updated = 0;
            $skipped = 0;

            foreach ($pairs as $number => $lid) {
                $number = (string) $number;
                $lid    = trim((string) $lid);

                if ($lid === '') {
                    $logger->info('[number_and_lid] LID vide pour le numéro ' . $number . ', ignoré.');
                    $skipped++;
                    continue;
                }

                $clean = str_replace(['+', ' ', "\t", "\n", "\r"], '', $number);
                if ($clean === '') {
                    $logger->info('[number_and_lid] Numéro vide après nettoyage, ignoré.');
                    $skipped++;
                    continue;
                }

                $telFormatted = '+' . $clean;

                $user = $userRepository->findOneBy(['tel' => $telFormatted]);

                if (!$user && str_starts_with($clean, '229') && !str_starts_with($clean, '22901')) {
                    $telWithO1 = '+22901' . substr($clean, 3);
                    $user = $userRepository->findOneBy(['tel' => $telWithO1]);
                    if ($user) {
                        $logger->info('[number_and_lid] Trouvé via fallback +22901 pour ' . $telFormatted);
                    }
                }

                if (!$user) {
                    $logger->warning('[number_and_lid] Aucun utilisateur trouvé pour ' . $telFormatted . ', ignoré.');
                    $skipped++;
                    continue;
                }

                if ($user->getLid() !== null && $user->getLid() !== '') {
                    $logger->info('[number_and_lid] LID déjà présent pour ' . $telFormatted . ' (lid=' . $user->getLid() . '), ignoré.');
                    $skipped++;
                    continue;
                }

                $user->setLid($lid);
                $updated++;
                $logger->info('[number_and_lid] LID mis à jour : ' . $telFormatted . ' => ' . $lid);
            }

            $this->em->flush();

            $logger->info('[number_and_lid] Traitement terminé : ' . $updated . ' mis à jour, ' . $skipped . ' ignorés.');

            return new Response('OK', Response::HTTP_OK);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue : ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/purge', name: 'app_crud_user_purge', methods: ['GET', 'POST'])]
    public function purge(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, TraitementsDS $traitementsDS): Response
    {
        // Process the form submission
        if ($request->isMethod('POST')) {
            $input = $request->request->get('identifier');
            $input = str_replace(" ", "", $input);
            $input = str_replace("      ", "", $input);

            $found = [];
            foreach (['pseudo', 'mail', 'uid', 'id', 'tel'] as $field) {
                foreach ($userRepository->findBy([$field => $input]) as $u) {
                    $found[$u->getId()] = $u;
                }
            }

            if (count($found) > 1) {
                $this->addFlash('warning', count($found) . ' comptes trouvés pour "' . $input . '". Veuillez préciser la recherche avant de purger.');
            } elseif (count($found) === 1) {
                $user = array_values($found)[0];
                $traitementsDS->execPurge($user);
                $this->addFlash('success', 'User and all related information have been deleted.');
                return $this->redirectToRoute('app_crud_user_check');
            } else {
                $this->addFlash('danger', 'User not found.');
            }
        }

        return $this->render('crud_user/purge_user.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
        ]);
    }

    #[Route('/banned', name: 'app_crud_user_banned', methods: ['GET', 'POST'])]
    public function banned(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, TraitementsDS $traitementsDS): Response
    {
        // Process the form submission
        if ($request->isMethod('POST')) {
            $motif = $request->request->get('motif');
            $input = $request->request->get('identifier');
            $input = str_replace(" ", "", $input);
            $input = str_replace("      ", "", $input);

            $found = [];
            foreach (['pseudo', 'mail', 'uid', 'id', 'tel'] as $field) {
                foreach ($userRepository->findBy([$field => $input]) as $u) {
                    $found[$u->getId()] = $u;
                }
            }

            if (count($found) > 1) {
                $this->addFlash('warning', count($found) . ' comptes trouvés pour "' . $input . '". Veuillez préciser la recherche avant de bannir.');
            } elseif (count($found) === 1) {
                $user = array_values($found)[0];
                $this->env->addUserBanned($user->getTel());
                $this->env->addUserBanned($user->getMail());
                $this->env->addUserBanned($motif);
                $this->em->flush();
                $traitementsDS->execPurge($user);
                $this->addFlash('success', 'User is Banned.');
                return $this->redirectToRoute('app_crud_user_check');
            } else {
                $this->addFlash('danger', 'User not found.');
            }
        }

        return $this->render('crud_user/banned_user.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
        ]);
    }

    #[Route('/banned-liste', name: 'app_crud_user_banned_liste', methods: ['GET', 'POST'])]
    public function banned_liste(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, TraitementsDS $traitementsDS): Response
    {
        $indice = count($this->env->getUserBanned()) / 3;
        $organizedUserBanned = [];
        for ($i = 0; $i < count($this->env->getUserBanned()); $i += 3) {
            $bannedInfo = [
                'indice' => $indice,
                'tel' => $this->env->getUserBanned()[$i],
                'mail' => $this->env->getUserBanned()[$i + 1],
                'motif' => $this->env->getUserBanned()[$i + 2]
            ];
            $organizedUserBanned[] = $bannedInfo;
            $indice--;
        }
        return $this->render('crud_user/banned_user_liste.html.twig', [
            'usersBanned' => $organizedUserBanned,
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
        ]);
    }

    #[Route('/{id}', name: 'app_crud_user_show', methods: ['GET'])]
    public function show(User $user): Response
    {
        return $this->render('crud_user/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'user_show' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_user/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            // 'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/activerMail', name: 'app_crud_user_activerMail', methods: ['POST'])]
    public function activerMail(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('activer_mail'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_crud_user_check', [], Response::HTTP_SEE_OTHER);
        }
        $user->setMailIsVerified(true);
        $entityManager->flush();
        $this->addFlash('success', 'Adresse mail activée avec succès.');
        return $this->redirectToRoute('app_crud_user_check', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/activerTel', name: 'app_crud_user_activerTel', methods: ['POST'])]
    public function activerTel(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('activer_tel'.$user->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_crud_user_check', [], Response::HTTP_SEE_OTHER);
        }
        $user->setTelIsVerified(true);
        $entityManager->flush();
        $this->addFlash('success', 'Numéro WhatsApp activé avec succès.');
        return $this->redirectToRoute('app_crud_user_check', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_crud_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $entityManager, TraitementsDS $traitementsDS): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            // $entityManager->remove($user);
            // $entityManager->flush();
            if($user) {
                $traitementsDS->execPurge($user);
                // Add a flash message to confirm deletion
                $this->addFlash('success', 'User and all related information have been deleted.');
                
                return $this->redirectToRoute('app_crud_user_check');
            }

            // Add a flash message if user is not found
            $this->addFlash('danger', 'User not found.');
        }

        return $this->redirectToRoute('app_crud_user_index', [], Response::HTTP_SEE_OTHER);
    }
}
