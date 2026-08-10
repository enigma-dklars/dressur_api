<?php

namespace App\Controller\API;

use App\Repository\AffiliationUsedRepository;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class EspacePartenaireController extends AbstractController
{
    public function __construct(
        private TraitementsDS $traitementsDS,
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private AffiliationUsedRepository $affiliationUsedRepository,
        private CookieDS $cookieDS,
    ) {}

    private function garantirCodePartenaire(\App\Entity\User $user): string
    {
        if ($user->getCodePartenaire()) {
            return $user->getCodePartenaire();
        }

        do {
            $code = \App\Entity\User::generateCodePartenaire();
        } while ($this->userRepository->findOneBy(['codePartenaire' => $code]));

        $user->setCodePartenaire($code);
        return $code;
    }

    private function listerAccompagnes(\App\Entity\User $user): array
    {
        $liste = [];
        foreach ($user->getAccompagnes() as $acc) {
            $affiliationUsed = $this->affiliationUsedRepository->findOneBy([
                'tel'  => $acc->getTel(),
                'mail' => $acc->getMail(),
            ]);
            $dateAffiliation = $affiliationUsed
                ? $affiliationUsed->getCreatedAt()->format('d/m/Y')
                : '—';
            $liste[] = [
                'nom'             => $acc->getNom() ?? '—',
                'pseudo'          => $acc->getPseudo() ?? '—',
                'tel'             => $acc->getTel() ?? '—',
                'mail'            => $acc->getMail() ?? '—',
                'dateAffiliation' => $dateAffiliation,
            ];
        }
        return $liste;
    }

    #[Route('/api/espacePartenaire', name: 'api_espace_partenaire', methods: ['POST'])]
    public function espacePartenaire(Request $request): JsonResponse
    {
        $uid = $this->cookieDS->getWithFallback('uid', $request);
        $user = $uid ? $this->userRepository->findOneBy(['uid' => $uid]) : null;
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        $codePartenaire = $this->garantirCodePartenaire($user);
        $this->em->flush();

        return new JsonResponse([
            'success'         => true,
            'codePartenaire'  => $codePartenaire,
            'estPartenaire'   => $user->getEstPartenaire(),
            'accompagnes'     => $this->listerAccompagnes($user),
        ]);
    }

    #[Route('/api/devenirPartenaire', name: 'api_devenir_partenaire', methods: ['POST'])]
    public function devenirPartenaire(Request $request): JsonResponse
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Non authentifié.'], 401);
        }
        // 1. Pas déjà Partenaire
        if ($user->getEstPartenaire()) {
            return new JsonResponse(['success' => false, 'message' => 'Vous êtes déjà Partenaire Dressur.']);
        }
        // 2. Nom complet renseigné
        if (!$user->getNom() || trim($user->getNom()) === '') {
            return new JsonResponse(['success' => false, 'message' => 'Votre nom complet doit être renseigné dans votre profil.']);
        }
        // 3. WhatsApp confirmé
        if (!$user->getTelIsVerified()) {
            return new JsonResponse(['success' => false, 'message' => 'Votre numéro WhatsApp doit être confirmé.']);
        }
        // 4. E-mail confirmé
        if (!$user->getMailIsVerified()) {
            return new JsonResponse(['success' => false, 'message' => 'Votre adresse e-mail doit être confirmée.']);
        }
        // 5. Inscrit depuis au moins 7 jours
        $now = new \DateTime();
        $diff = $now->diff($user->getCreatedAt());
        if ($diff->days < 7) {
            return new JsonResponse(['success' => false, 'message' => 'Vous devez être inscrit depuis au moins 7 jours.']);
        }
        // 6. Cumul ≥ 2 000 FCFA en transactions payantes
        $cumul = 0;
        // Boosts contact (tous les boosts sont payés)
        foreach ($user->getBoosts() as $boost) {
            if ($boost->getFormuleBoost()) {
                $cumul += $boost->getFormuleBoost()->getPrix();
            }
        }
        // Promotions affaire (status 3 = accepté+en cours, status 4 = terminé)
        foreach ($user->getPromotions() as $promo) {
            if (in_array($promo->getStatus(), [3, 4]) && $promo->getFormulePromoAffaire()) {
                $cumul += $promo->getFormulePromoAffaire()->getPrix();
            }
        }
        // Promos réseau (status 2 = en cours, status 3 = terminé)
        foreach ($user->getPromoReseaus() as $promoReseau) {
            if (in_array($promoReseau->getStatus(), [2, 3]) && $promoReseau->getFormulePromoReseau()) {
                $cumul += $promoReseau->getFormulePromoReseau()->getPrix();
            }
        }
        if ($cumul < 2000) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Vous devez avoir cumulé au moins 2 000 FCFA en transactions payantes (boost contact, promo affaire, promo réseau). Cumul actuel : ' . number_format($cumul, 0, ',', ' ') . ' FCFA.'
            ]);
        }
        // ── Tout est bon : activer le statut Partenaire ───────────────
        $codePartenaire = $this->garantirCodePartenaire($user);
        $user->setEstPartenaire(true);
        // Notification pour le user
        $this->traitementsDS->addNotification(
            '🤝 Vous êtes maintenant Partenaire Dressur ! Félicitations ! Votre statut Partenaire est activé. Votre code partenaire est disponible dans votre Espace Partenaire.',
            $user
        );
        $this->em->flush();
        return new JsonResponse([
            'success'        => true,
            'message'        => 'Félicitations ! Vous êtes maintenant Partenaire Dressur.',
            'codePartenaire' => $codePartenaire,
        ]);
    }

    #[Route('/api/accompagnesPartenaire', name: 'api_accompagnes_partenaire', methods: ['GET'])]
    public function accompagnesPartenaire(): JsonResponse
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        if (!$user) {
            return new JsonResponse(['success' => false, 'message' => 'Non authentifié.'], 401);
        }
        return new JsonResponse([
            'success'     => true,
            'accompagnes' => $this->listerAccompagnes($user),
        ]);
    }
}
