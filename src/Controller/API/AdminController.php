<?php

namespace App\Controller\API;

use App\Entity\Story;
use App\Entity\User;
use App\Services\CookieDS;
use App\Entity\Promotion;
use App\Entity\PromotionMotifRefus;
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

    private function formatPromotionDate(?\DateTimeInterface $date): ?string
    {
        return $date?->format('d/m/Y H:i');
    }

    private function dateAfterDays(?int $days): \DateTime
    {
        if ($days === null || $days < 1) {
            throw new \RuntimeException('Le nombre de jours de la formule est invalide.');
        }

        $date = (new \DateTime())->modify('+' . $days . ' days');
        if ($date === false) {
            throw new \RuntimeException('Impossible de calculer la date d’expiration.');
        }

        return $date;
    }

    private function encodePromotionToken(int $id): ?string
    {
        if (!function_exists('openssl_encrypt')) {
            return null;
        }

        $key = substr(hash('sha256', $this->getParameter('kernel.secret'), true), 0, 16);
        $encrypted = openssl_encrypt((string) $id, 'AES-128-ECB', $key, OPENSSL_RAW_DATA);

        if ($encrypted === false) {
            return null;
        }

        return rtrim(strtr(base64_encode($encrypted), '+/', '-_'), '=');
    }

    private function serializePendingPromotion(Promotion $promotion): array
    {
        $promotionUser = $promotion->getUser();
        $formule = $promotion->getFormulePromoAffaire();
        $additionalInfo = $promotion->getAnnotherInfo();
        $refusalHistory = [];

        foreach ($promotion->getMotifsRefus() as $refusal) {
            if (!$refusal instanceof PromotionMotifRefus) {
                continue;
            }

            $refusalHistory[] = [
                'motif' => $refusal->getMotif(),
                'dateRefus' => $this->formatPromotionDate($refusal->getDateRefus()),
            ];
        }

        return [
            'id' => $promotion->getId(),
            'type' => $promotion->getTypePromotionAffaire(),
            'description' => $promotion->getDescription(),
            'image' => $promotion->getImage(),
            'publicUrl' => $promotion->getId() !== null
                ? 'https://dressur.site/actualite/pub/' . $this->encodePromotionToken($promotion->getId())
                : null,
            'source' => $promotion->getSource(),
            'mode' => $promotion->getMode(),
            'motif' => $promotion->getMotif(),
            'status' => $promotion->getStatus(),
            'statusLabel' => match ($promotion->getStatus()) {
                0 => 'Rejetée',
                1 => 'En attente',
                2 => 'En attente de paiement',
                3 => 'En cours',
                4 => 'Terminée',
                default => 'Inconnu',
            },
            'createdAt' => $this->formatPromotionDate($promotion->getCreatedAt()),
            'dateDebut' => $this->formatPromotionDate($promotion->getDateDebut()),
            'dateExp' => $this->formatPromotionDate($promotion->getDateExp()),
            'nombreDeVue' => $promotion->getNombreDeVue(),
            'nombreImpression' => $promotion->getNombreImpression(),
            'whoSawCount' => count($promotion->getWhoSaw() ?? []),
            'limited' => $promotion->isLimited(),
            'isFakeVue' => $promotion->getIsFakeVue(),
            'referencement' => $promotion->getReferencement(),
            'publishOnDressurStatus' => $promotion->isPublishOnDressurStatus(),
            'inProgrammeRecompense' => $promotion->isInProgrammeRecompense(),
            'boostFacebook' => $promotion->isBoostFacebook(),
            'montantBoostFacebook' => $promotion->getMontantBoostFacebook(),
            'whatsappContact' => $promotion->getWhatsappContact(),
            'nomSiteApp' => $promotion->getNomSiteApp(),
            'urlSiteApp' => $promotion->getUrlSiteApp(),
            'sousTypeSiteApp' => $promotion->getSousTypeSiteApp(),
            'formule' => $formule ? [
                'id' => $formule->getId(),
                'titre' => $formule->getTitre(),
                'prix' => $formule->getPrix(),
                'nbrJour' => $formule->getNbrJour(),
            ] : null,
            'annotherInfo' => is_array($additionalInfo) ? $additionalInfo : [],
            'motifsRefus' => $refusalHistory,
            'user' => $promotionUser ? [
                'pseudo' => $promotionUser->getPseudo(),
                'nom' => $promotionUser->getNom(),
                'mail' => $promotionUser->getMail(),
                'tel' => $promotionUser->getTel(),
            ] : null,
        ];
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

        $data = array_map(
            fn (Promotion $promotion): array => $this->serializePendingPromotion($promotion),
            $promotions
        );

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

        if ($promotion->getStatus() !== 1) {
            return new JsonResponse([
                'error'   => true,
                'message' => 'Cette promotion n’est plus en attente de validation.',
            ]);
        }

        try {
            $type = $promotion->getTypePromotionAffaire();
            if ($type === 'produit_service') {
                $formule = $promotion->getFormulePromoAffaire();
                if (!$formule) {
                    throw new \RuntimeException('La formule de cette promotion est introuvable ou invalide.');
                }
                $promotion->setDateExp($this->dateAfterDays($formule->getNbrJour()));
            }

            if (in_array($type, ['dmd_emploi', 'offre_emploi'], true)) {
                $formule = $formulePromoAffaireRepository->find(4);
                if (!$formule) {
                    throw new \RuntimeException('La formule par défaut des emplois est introuvable ou invalide.');
                }
                $promotion
                    ->setFormulePromoAffaire($formule)
                    ->setDateExp($this->dateAfterDays($formule->getNbrJour()));
            }

            if ($type === 'sites_applications') {
                $promotion->setDateExp($this->dateAfterDays(365));
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
                'message' => 'Une erreur est survenue lors du traitement de la promotion. Veuillez réessayer.',
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

        if ($promotion->getStatus() !== 1) {
            return new JsonResponse([
                'error'   => true,
                'message' => 'Cette promotion n’est plus en attente de validation.',
            ]);
        }

        $motif = trim((string) $request->request->get('motif', ''));
        if ($motif === '') {
            return new JsonResponse([
                'error'   => true,
                'message' => 'Un motif de refus est obligatoire.',
            ]);
        }

        try {
            $motifRefus = (new PromotionMotifRefus())->setMotif($motif);
            $promotion->addMotifRefus($motifRefus);
            $promotion->setMotif($motif)->setStatus(0);
            $this->em->persist($motifRefus);
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
                'message' => 'Une erreur est survenue lors du traitement de la promotion. Veuillez réessayer.',
            ]);
        }
    }
}
