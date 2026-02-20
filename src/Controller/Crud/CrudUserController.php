<?php

namespace App\Controller\Crud;

use App\Entity\User;
use App\Form\UserType;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/', name: 'app_crud_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository, Request $request): Response
    {
        $page = $request->query->getInt('page', 1);
        $search = $request->query->get('search', '');
        $limit = 100; // Nombre d'utilisateurs par page
        
        if ($search) {
            $usersPaginator = $userRepository->searchUsers($search, $page, $limit);
        } else {
            $usersPaginator = $userRepository->findAllPaginated($page, $limit);
        }
        
        $totalItems = $usersPaginator->count();
        $totalPages = ceil($totalItems / $limit);
        
        return $this->render('crud_user/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'users' => $usersPaginator,
            'option' => "All",
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'search' => $search,
            'limit' => $limit
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
        return $this->render('crud_user/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'users' => $userRepository->findBy(['telIsVerified' => false], ['id' => 'DESC']),
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
        return $this->render('crud_user/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'users' => $userRepository->findBy(['mailIsVerified' => false], ['id' => 'DESC']),
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
        return $this->render('crud_user/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'users' => $userRepository->findBy(['mailIsVerified' => false, 'telIsVerified' => false], ['id' => 'DESC']),
            'option' => "Tel Mail Not Verified",
            'currentPage' => "",
            'totalPages' => "",
            'totalItems' => "",
            'search' => "",
            'limit' => ""
        ]);
    }

    #[Route('/supprimer-user-inutile', name: 'app_crud_user_supprimer_user_inutile', methods: ['GET'])]
    public function supprimer_user_inutile(UserRepository $userRepository, TraitementsDS $traitementsDS): Response
    {
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
        $telcut = null;
        $teladd1 = null;
        $teladd2 = null;
        $teladd3 = null;
        $paysChoisies = [];

        // Process the form submission
        if ($request->isMethod('POST')) {
            $input = $request->request->get('identifier');
            $input = str_replace(" ", "", $input);
            $input = str_replace("	", "", $input);

            if (strpos($input, '+225') === 0) {
                // Vérifier s'il y a 10 caractères après +225
                if (strlen(substr($input, 4)) == 10) {
                    // Retirer les 2 caractères qui suivent +225
                    $telcut = substr($input, 0, 4) . substr($input, 6);
                } else {
                    $teladd1 = str_replace("+225", "+22501", $input);
                    $teladd2 = str_replace("+225", "+22505", $input);
                    $teladd3 = str_replace("+225", "+22507", $input);
                }
            }

            if (strpos($input, '+229') === 0) {
                // Vérifier s'il y a 10 caractères après +229
                if (strlen(substr($input, 4)) == 10) {
                    // Retirer les 2 caractères qui suivent +229
                    $telcut = substr($input, 0, 4) . substr($input, 6);
                } else {
                    $teladd1 = str_replace("+229", "+22901", $input);
                }
            }

            $user = 
                $userRepository->findOneBy(['pseudo' => $input]) ?? 
                $userRepository->findOneBy(['mail' => $input]) ?? 
                $userRepository->findOneBy(['uid' => $input]) ?? 
                $userRepository->findOneBy(['id' => $input]) ?? 
                $userRepository->findOneBy(['tel' => $input]) ?? 
                $userRepository->findOneBy(['tel' => $telcut]) ?? 
                $userRepository->findOneBy(['tel' => $teladd1]) ?? 
                $userRepository->findOneBy(['tel' => $teladd2]) ?? 
                $userRepository->findOneBy(['tel' => $teladd3])
            ;

            $user_array = [];

            if($user) {
                // Add a flash message to confirm deletion
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
        $telcut = null;
        $teladd1 = null;
        $teladd2 = null;
        $teladd3 = null;
        $message = [];

        // Process the form submission
        if ($request->isMethod('POST')) {
            $input = $request->request->get('identifier');
            $input = str_replace(" ", "", $input);
            $input = str_replace("	", "", $input);

            if (strpos($input, '+225') === 0) {
                // Vérifier s'il y a 10 caractères après +225
                if (strlen(substr($input, 4)) == 10) {
                    // Retirer les 2 caractères qui suivent +225
                    $telcut = substr($input, 0, 4) . substr($input, 6);
                } else {
                    $teladd1 = str_replace("+225", "+22501", $input);
                    $teladd2 = str_replace("+225", "+22505", $input);
                    $teladd3 = str_replace("+225", "+22507", $input);
                }
            }

            if (strpos($input, '+229') === 0) {
                // Vérifier s'il y a 10 caractères après +229
                if (strlen(substr($input, 4)) == 10) {
                    // Retirer les 2 caractères qui suivent +229
                    $telcut = substr($input, 0, 4) . substr($input, 6);
                } else {
                    $teladd1 = str_replace("+229", "+22901", $input);
                }
            }

            $user = 
                $userRepository->findOneBy(['pseudo' => $input]) ?? 
                $userRepository->findOneBy(['mail' => $input]) ?? 
                $userRepository->findOneBy(['uid' => $input]) ?? 
                $userRepository->findOneBy(['id' => $input]) ?? 
                $userRepository->findOneBy(['tel' => $input]) ?? 
                $userRepository->findOneBy(['tel' => $telcut]) ?? 
                $userRepository->findOneBy(['tel' => $teladd1]) ?? 
                $userRepository->findOneBy(['tel' => $teladd2]) ?? 
                $userRepository->findOneBy(['tel' => $teladd3])
            ;

            $user_array = [];

            if($user) {
                // Add a flash message to confirm deletion
                $this->addFlash('success', 'User found.');

                if(!$user->getMailIsVerified()) {
                    $message[] = "Vous n'avez pas confirmer votre adresse mail.";
                }
                if(empty($user->getNom())) {
                    $message[] = "Veuillez complété votre profil (au minimum nom et prenom ou nom d'entreprise).";
                }

                if($user->getMailIsVerified() and !empty($user->getNom())) {
                    $user->setTelIsVerified(true);
                    $entityManager->flush();
                    $this->addFlash('success', 'Numéro WhatsApp a été confirmé avec succès');
                } else {
                    $this->addFlash('danger', 'User not found.');
                }

                $user_array['user_info'] = $user;
            } else {
                // Add a flash message if user is not found
                $this->addFlash('danger', 'Echac de confirmation du numéro WhatsApp.');
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

    #[Route('/find_whatsapp_is_activatable/{tel}', name: 'app_crud_user_find_whatsapp_is_activatable', methods: ['GET', 'POST'])]
    public function find_whatsapp_is_activatable(Request $request, string $tel, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = null;
        $telcut = null;
        $teladd1 = null;
        $teladd2 = null;
        $teladd3 = null;
        $count_compt = 0;

        $input = "+$tel";
        $input = str_replace(" ", "", $input);
        $input = str_replace("	", "", $input);

        if (strpos($input, '+225') === 0) {
            if (strlen(substr($input, 4)) == 10) {
                $telcut = substr($input, 0, 4) . substr($input, 6);
            } else {
                $teladd1 = str_replace("+225", "+22501", $input);
                $teladd2 = str_replace("+225", "+22505", $input);
                $teladd3 = str_replace("+225", "+22507", $input);
            }
        }

        if (strpos($input, '+229') === 0) {
            if (strlen(substr($input, 4)) == 10) {
                $telcut = substr($input, 0, 4) . substr($input, 6);
            } else {
                $teladd1 = str_replace("+229", "+22901", $input);
            }
        }

        if (count($userRepository->findBy(['tel' => $input]))) {
            $count_compt++;
        }
        if (count($userRepository->findBy(['tel' => $telcut]))) {
            $count_compt++;
        }
        if (count($userRepository->findBy(['tel' => $teladd1]))) {
            $count_compt++;
        }
        if (count($userRepository->findBy(['tel' => $teladd2]))) {
            $count_compt++;
        }
        if (count($userRepository->findBy(['tel' => $teladd3]))) {
            $count_compt++;
        }

        if ($count_compt > 1) {
            return new Response("Apparemment, vous avez plusieurs comptes Dressur liés au numéro +$tel.\nVeuillez patienter, un assistant vous aidera sous peu.");
        } else if ($count_compt == 0) {
            return new Response("Veuillez utiliser le numéro WhatsApp lié à votre compte Dressur pour effectuer la demande de confirmation du numéro WhatsApp.");
        }

        $user = 
            $userRepository->findOneBy(['tel' => $input]) ?? 
            $userRepository->findOneBy(['tel' => $telcut]) ?? 
            $userRepository->findOneBy(['tel' => $teladd1]) ?? 
            $userRepository->findOneBy(['tel' => $teladd2]) ?? 
            $userRepository->findOneBy(['tel' => $teladd3]);

        if ($user) {
            if ($user->getTelIsVerified() == true) {
                return new Response("Le compte lié au numéro +$tel est déjà confirmé.");
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
                return new Response("Votre numéro WhatsApp a été confirmé avec succès.");
            }
            
            return new Response("$message\n\nVous pouvez renvoyer une nouvelle demande de confirmation après avoir rempli les exigences mentionnées.");
        }
        
        return new Response("Nous avons rencontré une erreur lors de la confirmation de votre numéro WhatsApp.\nVeuillez patienter, un assistant vous aidera sous peu.");
    }

    #[Route('/find_all_info_with_tel_user/{tel}', name: 'app_crud_user_find_all_info_with_tel_user', methods: ['GET', 'POST'])]
    public function find_all_info_with_tel_user(Request $request, string $tel, UserRepository $userRepository, EntityManagerInterface $em): Response
    {
        $user = null;
        $telcut = null;
        $teladd1 = null;
        $teladd2 = null;
        $teladd3 = null;

        $input = "+$tel";
        $input = str_replace(" ", "", $input);
        $input = str_replace("	", "", $input);

        if (strpos($input, '+225') === 0) {
            if (strlen(substr($input, 4)) == 10) {
                $telcut = substr($input, 0, 4) . substr($input, 6);
            } else {
                $teladd1 = str_replace("+225", "+22501", $input);
                $teladd2 = str_replace("+225", "+22505", $input);
                $teladd3 = str_replace("+225", "+22507", $input);
            }
        }

        if (strpos($input, '+229') === 0) {
            if (strlen(substr($input, 4)) == 10) {
                $telcut = substr($input, 0, 4) . substr($input, 6);
            } else {
                $teladd1 = str_replace("+229", "+22901", $input);
            }
        }

        $user = 
            $userRepository->findOneBy(['tel' => $input]) ?? 
            $userRepository->findOneBy(['tel' => $telcut]) ?? 
            $userRepository->findOneBy(['tel' => $teladd1]) ?? 
            $userRepository->findOneBy(['tel' => $teladd2]) ?? 
            $userRepository->findOneBy(['tel' => $teladd3]);

        if ($user) {
            $lines = [];

            $lines[] = "Pseudo : " . ($user->getPseudo() ?? '—');
            $lines[] = "Nom : " . ($user->getNom() ?? '—');
            $lines[] = "Numéro WhatsApp : " . ($user->getTel() ?? '—');
            $lines[] = "Adresse e-mail : " . ($user->getMail() ?? '—');

            $lines[] = "Pays : " . ($user->getPays() ?? '—');

            // formatage des dates si présentes
            $createdAt = $user->getCreatedAt();
            $lines[] = "Date de création du compte : " . ($createdAt instanceof \DateTimeInterface ? $createdAt->format('Y-m-d H:i:s') : '—');

            $lines[] = "À propos : " . ($user->getApropos() ?? '—');

            // attention à la priorité des opérateurs : mettre la ternaire entre parenthèses
            $lines[] = "Confirmation du numéro WhatsApp : " . ($user->getTelIsVerified() ? "Oui" : "Non");
            $lines[] = "Confirmation de l'adresse e-mail : " . ($user->getMailIsVerified() ? "Oui" : "Non");

            $lines[] = "Points bonus : " . ($user->getSoldeBonus() !== null ? $user->getSoldeBonus() : '0');
            $lines[] = "Code de parrainage : " . ($user->getCodeBonus() ?? '—');

            // parrain : peut être un objet User ou juste une valeur
            $parrain = $user->getParrain();
            if ($parrain) {
                if (is_object($parrain)) {
                    // supposition : l'entité parrain a une méthode getPseudo() ou getTel()
                    $parrainLabel = method_exists($parrain, 'getPseudo') && $parrain->getPseudo() ? $parrain->getPseudo() : ($parrain->getTel() ?? '—');
                } else {
                    $parrainLabel = (string) $parrain;
                }
                $lines[] = "Parrain : " . $parrainLabel;
            } else {
                $lines[] = "Parrain : —";
            }

            // Collections (vérifier s'il s'agit de tableaux ou de Collection)
            $filleuls = $user->getFilleuls();
            $lines[] = "Nombre de filleul(s) : " . (is_countable($filleuls) ? count($filleuls) : 0);

            $lastLogin = $user->getLastLoginTo();
            $lines[] = "Date de dernière connexion : " . ($lastLogin instanceof \DateTimeInterface ? $lastLogin->format('Y-m-d H:i:s') : '—');

            $boosts = $user->getBoosts();
            $promotions = $user->getPromotions();
            $promoReseaus = $user->getPromoReseaus();

            $lines[] = "Nombre de Boost Contact effectués : " . (is_countable($boosts) ? count($boosts) : 0);
            $lines[] = "Nombre de Promotion Affaire effectuées : " . (is_countable($promotions) ? count($promotions) : 0);
            $lines[] = "Nombre de Promotion Réseaux Sociaux effectuées : " . (is_countable($promoReseaus) ? count($promoReseaus) : 0);

            // préférences pays (vérifier null)
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

    #[Route('/purge', name: 'app_crud_user_purge', methods: ['GET', 'POST'])]
    public function purge(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, TraitementsDS $traitementsDS): Response
    {
        $telcut = null;

        // Process the form submission
        if ($request->isMethod('POST')) {
            $input = $request->request->get('identifier');
            $input = str_replace(" ", "", $input);
            $input = str_replace("	", "", $input);

            if (strpos($input, '+225') === 0) {
                // Vérifier s'il y a 10 caractères après +225
                if (strlen(substr($input, 4)) == 10) {
                    // Retirer les 2 caractères qui suivent +225
                    $telcut = substr($input, 0, 4) . substr($input, 6);
                } else {
                    $teladd1 = str_replace("+225", "+22501", $input);
                    $teladd2 = str_replace("+225", "+22505", $input);
                    $teladd3 = str_replace("+225", "+22507", $input);
                }
            }

            if (strpos($input, '+229') === 0) {
                // Vérifier s'il y a 10 caractères après +229
                if (strlen(substr($input, 4)) == 10) {
                    // Retirer les 2 caractères qui suivent +229
                    $telcut = substr($input, 0, 4) . substr($input, 6);
                } else {
                    $teladd1 = str_replace("+229", "+22901", $input);
                }
            }

            $user = 
                $userRepository->findOneBy(['pseudo' => $input]) ?? 
                $userRepository->findOneBy(['mail' => $input]) ?? 
                $userRepository->findOneBy(['uid' => $input]) ?? 
                $userRepository->findOneBy(['id' => $input]) ?? 
                $userRepository->findOneBy(['tel' => $input]) ?? 
                $userRepository->findOneBy(['tel' => $telcut]) ?? 
                $userRepository->findOneBy(['tel' => $teladd1]) ?? 
                $userRepository->findOneBy(['tel' => $teladd2]) ?? 
                $userRepository->findOneBy(['tel' => $teladd3])
            ;
            
            if($user) {
                $traitementsDS->execPurge($user);
                // Add a flash message to confirm deletion
                $this->addFlash('success', 'User and all related information have been deleted.');
                
                return $this->redirectToRoute('app_crud_user_check');
            }

            // Add a flash message if user is not found
            $this->addFlash('danger', 'User not found.');
        }

        return $this->render('crud_user/purge_user.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
        ]);
    }

    #[Route('/banned', name: 'app_crud_user_banned', methods: ['GET', 'POST'])]
    public function banned(Request $request, UserRepository $userRepository, EntityManagerInterface $entityManager, TraitementsDS $traitementsDS): Response
    {
        $telcut = null;

        // Process the form submission
        if ($request->isMethod('POST')) {
            $motif = $request->request->get('motif');
            $input = $request->request->get('identifier');
            $input = str_replace(" ", "", $input);
            $input = str_replace("	", "", $input);

            if (strpos($input, '+225') === 0) {
                // Vérifier s'il y a 10 caractères après +225
                if (strlen(substr($input, 4)) == 10) {
                    // Retirer les 2 caractères qui suivent +225
                    $telcut = substr($input, 0, 4) . substr($input, 6);
                } else {
                    $teladd1 = str_replace("+225", "+22501", $input);
                    $teladd2 = str_replace("+225", "+22505", $input);
                    $teladd3 = str_replace("+225", "+22507", $input);
                }
            }

            if (strpos($input, '+229') === 0) {
                // Vérifier s'il y a 10 caractères après +229
                if (strlen(substr($input, 4)) == 10) {
                    // Retirer les 2 caractères qui suivent +229
                    $telcut = substr($input, 0, 4) . substr($input, 6);
                } else {
                    $teladd1 = str_replace("+229", "+22901", $input);
                }
            }

            $user = 
                $userRepository->findOneBy(['pseudo' => $input]) ?? 
                $userRepository->findOneBy(['mail' => $input]) ?? 
                $userRepository->findOneBy(['uid' => $input]) ?? 
                $userRepository->findOneBy(['id' => $input]) ?? 
                $userRepository->findOneBy(['tel' => $input]) ?? 
                $userRepository->findOneBy(['tel' => $telcut]) ?? 
                $userRepository->findOneBy(['tel' => $teladd1]) ?? 
                $userRepository->findOneBy(['tel' => $teladd2]) ?? 
                $userRepository->findOneBy(['tel' => $teladd3])
            ;
            
            if($user) {
                $this->env->addUserBanned($user->getTel());
                $this->env->addUserBanned($user->getMail());
                $this->env->addUserBanned($motif);
                $this->em->flush();
                $traitementsDS->execPurge($user);
                // Add a flash message to confirm deletion
                $this->addFlash('success', 'User is Banned.');
                
                return $this->redirectToRoute('app_crud_user_check');
            }

            // Add a flash message if user is not found
            $this->addFlash('danger', 'User not found.');
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

    #[Route('/{id}/activerMail', name: 'app_crud_user_activerMail', methods: ['GET', 'POST'])]
    public function activerMail(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setMailIsVerified(true);
        $entityManager->flush();
        return $this->redirectToRoute('app_crud_user_check', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/activerTel', name: 'app_crud_user_activerTel', methods: ['GET', 'POST'])]
    public function activerTel(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setTelIsVerified(true);
        $entityManager->flush();
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
