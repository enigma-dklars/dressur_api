<?php

namespace App\Controller\API;

use App\Entity\Story;
use App\Entity\User;
use App\Services\CookieDS;
use App\Services\SessionDS;
use App\Entity\Promotion;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\BoostRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\FormuleBoostRepository;
use App\Repository\FormulePromoAffaireRepository;
use App\Repository\PromotionRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Transaction as EntityTransaction;
use App\Repository\PromoReseauRepository;
use App\Repository\UserRepository;
use App\Utilities\SendMail;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api', name: 'api_')]

class AdminController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1); 
    }

    #[Route('/sendMailToDressur', name: 'sendMailToDressur')]
    public function sendMailToDressur(Request $request, SendMail $sendMail): Response
    {
        $objet = $request->get("objet");
        $name = $request->get("name");
        $email = $request->get("email");
        $message = $request->get("message");

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response("<b>$email</b> n'est pas une adresse e-mail valide.");
        }

        $html = $this->renderView('emails/contactMail.html.twig',[
            "objet" => $objet,
            "name" => $name,
            "email" => $email,
            "message" => $message,
        ]);

        try {
            $sent = $sendMail->smtpMail(
                "dressur.ds@gmail.com", 
                "Page Contact Web Dressur",
                $html,
                $email,
                "Message From Web No ".time(), 
            );

            if (!$sent) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Erreur!',
                    'message' => "Mail non envoyé. Veuillez réessayer.",
                ]);
            }

            return new JsonResponse([
                'error' => false,
            ]);
        } catch (\Throwable $th) {
            $sendMail->sendReport('Error sendMailToDressur : AdminController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => "Mail non envoyé.",
            ]);
        }
    }

    /**
     * Retourne la liste des promotions en attente de validation (status = 1).
     * Réservé aux administrateurs.
     */
    #[Route('/admin/promos-en-attente', name: 'admin_promos_en_attente', methods: ['GET'])]
    public function promosEnAttente(
        Request $request,
        CookieDS $cookieDS,
        VerificationsDS $verificationsDS,
        PromotionRepository $promotionRepository
    ): JsonResponse {
        // getWithFallback couvre cookie + body POST ; le client mobile envoie uid en query string sur les GET
        $uid = $cookieDS->getWithFallback('uid', $request) ?: $request->query->get('uid') ?: null;
        $verificationUser = $verificationsDS->verifUSer($uid);

        if ($verificationUser['error'] == true) {
            return new JsonResponse([
                'error'   => true,
                'message' => $verificationUser['message'],
            ]);
        }

        $adminUser = $verificationUser['user'];
        if (!$adminUser->getAdmin()) {
            return new JsonResponse([
                'error'   => true,
                'message' => 'Accès refusé. Droits administrateur requis.',
            ]);
        }

        $promotions = $promotionRepository->findBy(['status' => 1], ['id' => 'DESC']);

        $data = [];
        foreach ($promotions as $promo) {
            $promoUser = $promo->getUser();
            $data[] = [
                'id'          => $promo->getId(),
                'type'        => $promo->getTypePromotionAffaire(),
                'description' => $promo->getDescription(),
                'image'       => $promo->getImage(),
                'source'      => $promo->getSource(),
                'createdAt'   => $promo->getCreatedAt()
                    ? $promo->getCreatedAt()->format('d/m/Y H:i')
                    : null,
                'user' => $promoUser ? [
                    'pseudo' => $promoUser->getPseudo(),
                    'nom'    => $promoUser->getNom(),
                    'mail'   => $promoUser->getMail(),
                    'tel'    => $promoUser->getTel(),
                ] : null,
            ];
        }

        return new JsonResponse(['error' => false, 'promotions' => $data]);
    }

    /**
     * Accepte une promotion en attente.
     * Réservé aux administrateurs.
     */
    #[Route('/admin/promos/{id}/accepter', name: 'admin_promos_accepter', methods: ['POST'])]
    public function accepterPromo(
        Request $request,
        int $id,
        CookieDS $cookieDS,
        VerificationsDS $verificationsDS,
        PromotionRepository $promotionRepository,
        FormulePromoAffaireRepository $formulePromoAffaireRepository,
        SendMail $sendMail,
        TraitementsDS $traitementsDS
    ): JsonResponse {
        $uid = $cookieDS->getWithFallback('uid', $request) ?: null;
        $verificationUser = $verificationsDS->verifUSer($uid);

        if ($verificationUser['error'] == true) {
            return new JsonResponse([
                'error'   => true,
                'message' => $verificationUser['message'],
            ]);
        }

        $adminUser = $verificationUser['user'];
        if (!$adminUser->getAdmin()) {
            return new JsonResponse([
                'error'   => true,
                'message' => 'Accès refusé. Droits administrateur requis.',
            ]);
        }

        $promotion = $promotionRepository->find($id);
        if (!$promotion) {
            return new JsonResponse([
                'error'   => true,
                'message' => "Promotion #{$id} introuvable.",
            ]);
        }

        try {
            if ($promotion->getTypePromotionAffaire() === 'produit_service') {
                $promotion->setDateExp(
                    new \DateTime('+ ' . $promotion->getFormulePromoAffaire()->getNbrJour() . ' days')
                );
            }

            if ($promotion->getTypePromotionAffaire() === 'dmd_emploi') {
                $formule = $formulePromoAffaireRepository->find(4);
                $promotion
                    ->setFormulePromoAffaire($formule)
                    ->setDateExp(new \DateTime('+ ' . $formule->getNbrJour() . ' days'));
            }

            if ($promotion->getTypePromotionAffaire() === 'offre_emploi') {
                $formule = $formulePromoAffaireRepository->find(4);
                $promotion
                    ->setFormulePromoAffaire($formule)
                    ->setDateExp(new \DateTime('+ ' . $formule->getNbrJour() . ' days'));
            }

            $promotion->setMotif('')->setStatus(3)->setDateDebut(new \DateTime());
            $user = $promotion->getUser();
            $traitementsDS->addNotification("Votre promotion a été acceptée 🎉.", $user);

            // Création automatique de la Story si publishOnDressurStatus est activé
            if ($promotion->isPublishOnDressurStatus()) {
                // Description : premiers 150 caractères de la description de la promo
                $descriptionBrute = $promotion->getDescription() ?? '';
                $descriptionStory = mb_strlen($descriptionBrute) > 150
                    ? mb_substr($descriptionBrute, 0, 150) . '…'
                    : $descriptionBrute;

                // URL : lien WhatsApp de l'utilisateur (tel en format international → wa.me)
                $telUser = $user ? $user->getTel() : '';
                $telClean = ltrim((string)$telUser, '+');
                $urlWhatsApp = $telClean ? 'https://wa.me/' . $telClean : null;

                $story = new Story();
                $story->setUser($user);
                $story->setDescription($descriptionStory);
                $story->setUrl($urlWhatsApp);
                $story->setExpiredAt($promotion->getDateExp());

                // Image de la promotion
                $imagePromo = $promotion->getImage();
                if ($imagePromo) {
                    $story->setImages([$imagePromo]);
                }

                $this->em->persist($story);
            }

            $this->em->flush();

            $promoUser = $promotion->getUser();
            if ($promoUser && $promoUser->getMail()) {
                $formulePromoAffaire = $promotion->getFormulePromoAffaire();
                $html = $this->renderView('emails/promo_affaire_acceptee_user.html.twig', [
                    'user_nom'         => $promoUser->getNom(),
                    'formule_titre'    => $formulePromoAffaire ? $formulePromoAffaire->getTitre() : null,
                    'formule_nbr_jour' => $formulePromoAffaire ? $formulePromoAffaire->getNbrJour() : null,
                ]);
                $sendMail->smtpMail($promoUser->getMail(), 'Votre promotion a été acceptée 🎉', $html);
            }

            return new JsonResponse([
                'error'   => false,
                'message' => "Promotion #{$id} acceptée avec succès.",
            ]);
        } catch (\Throwable $th) {
            $sendMail->sendReport('Error accepterPromo : AdminController', $th . '<br><br><br>');
            return new JsonResponse([
                'error'   => true,
                'message' => 'Erreur : ' . $th->getMessage(),
            ]);
        }
    }

    /**
     * Refuse une promotion en attente avec un motif.
     * Réservé aux administrateurs.
     */
    #[Route('/admin/promos/{id}/refuser', name: 'admin_promos_refuser', methods: ['POST'])]
    public function refuserPromo(
        Request $request,
        int $id,
        CookieDS $cookieDS,
        VerificationsDS $verificationsDS,
        PromotionRepository $promotionRepository,
        SendMail $sendMail,
        TraitementsDS $traitementsDS
    ): JsonResponse {
        $uid = $cookieDS->getWithFallback('uid', $request) ?: null;
        $verificationUser = $verificationsDS->verifUSer($uid);

        if ($verificationUser['error'] == true) {
            return new JsonResponse([
                'error'   => true,
                'message' => $verificationUser['message'],
            ]);
        }

        $adminUser = $verificationUser['user'];
        if (!$adminUser->getAdmin()) {
            return new JsonResponse([
                'error'   => true,
                'message' => 'Accès refusé. Droits administrateur requis.',
            ]);
        }

        $promotion = $promotionRepository->find($id);
        if (!$promotion) {
            return new JsonResponse([
                'error'   => true,
                'message' => "Promotion #{$id} introuvable.",
            ]);
        }

        $motif = trim((string) $request->request->get('motif', ''));

        try {
            $promotion->setMotif($motif)->setStatus(0);
            $user = $promotion->getUser();
            $traitementsDS->addNotification("Votre promotion a été refusée.", $user);
            $this->em->flush();

            $promoUser = $promotion->getUser();
            if ($promoUser && $promoUser->getMail()) {
                $html = $this->renderView('emails/promo_affaire_refusee_user.html.twig', [
                    'user_nom' => $promoUser->getNom(),
                    'motif'    => $motif,
                ]);
                $sendMail->smtpMail($promoUser->getMail(), 'Votre promotion a été refusée', $html);
            }

            return new JsonResponse([
                'error'   => false,
                'message' => "Promotion #{$id} refusée.",
            ]);
        } catch (\Throwable $th) {
            $sendMail->sendReport('Error refuserPromo : AdminController', $th . '<br><br><br>');
            return new JsonResponse([
                'error'   => true,
                'message' => 'Erreur : ' . $th->getMessage(),
            ]);
        }
    }
}
