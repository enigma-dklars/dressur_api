<?php

namespace App\Controller\API;

use DateTime;
use App\Entity\AffiliationUsed;
use App\Entity\User;
use App\Repository\AffiliationUsedRepository;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use App\Utilities\SendMail;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class AffiliationController extends AbstractController
{
    private $em;
    private $cookieDS;
    private $sendMail;

    public function __construct(EntityManagerInterface $em, CookieDS $cookieDS, SendMail $sendMail)
    {
        $this->em = $em;
        $this->cookieDS = $cookieDS;
        $this->sendMail = $sendMail;
    }

    #[Route('/utiliserCodePartenaire', name: 'utiliserCodePartenaire', methods: ['POST'])]
    public function utiliserCodePartenaire(
        Request $request,
        UserRepository $userRepository,
        AffiliationUsedRepository $affiliationUsedRepository,
        TraitementsDS $traitementsDS
    ): Response {
        try {
            // --- 1. Récupération et validation du user demandeur ---
            $uid = $this->cookieDS->getWithFallback('uid', $request);
            if (!$uid) {
                return new JsonResponse(['error' => true, 'titre' => 'Erreur!', 'message' => 'Utilisateur non identifié.']);
            }
            $user = $userRepository->findOneBy(['uid' => $uid]);
            if (!$user) {
                return new JsonResponse(['error' => true, 'titre' => 'Erreur!', 'message' => 'Utilisateur introuvable.']);
            }
            // --- 2. Vérification : pas déjà accompagné ---
            if ($user->getPartenaire() !== null) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Non autorisé',
                    'message' => 'Vous avez déjà un partenaire. Un utilisateur ne peut être accompagné qu\'une seule fois.',
                ]);
            }
            // --- 3. Vérification : nom renseigné ---
            if (empty(trim((string) $user->getNom()))) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Profil incomplet',
                    'message' => 'Veuillez renseigner votre nom et prénom dans votre profil avant d\'utiliser un Code Partenaire.',
                ]);
            }
            // --- 4. Vérification : WhatsApp confirmé ---
            if (!$user->getTelIsVerified()) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'WhatsApp non confirmé',
                    'message' => 'Votre numéro WhatsApp doit être confirmé avant d\'utiliser un Code Partenaire.',
                ]);
            }
            // --- 5. Vérification : email confirmé ---
            if (!$user->getMailIsVerified()) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Email non confirmé',
                    'message' => 'Votre adresse e-mail doit être confirmée avant d\'utiliser un Code Partenaire.',
                ]);
            }
            // --- 6. Vérification : inscrit depuis moins de 24h ---
            // La fenêtre est strictement [0, 24h) et la date serveur fait foi.
            $createdAt = $user->getCreatedAt();
            $ageEnSecondes = $createdAt
                ? (new \DateTimeImmutable('now'))->getTimestamp() - $createdAt->getTimestamp()
                : PHP_INT_MAX;
            if ($ageEnSecondes < 0 || $ageEnSecondes >= 24 * 60 * 60) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Délai dépassé',
                    'message' => 'Le Code Partenaire ne peut être utilisé que dans les 24 heures suivant votre inscription.',
                ]);
            }
            // --- 7. Vérification anti-fraude : téléphone pas dans AffiliationUsed ---
            $telBlacklisted = $affiliationUsedRepository->findOneBy(['tel' => $user->getTel()]);
            if ($telBlacklisted) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Non autorisé',
                    'message' => 'Ce numéro de téléphone ne peut pas bénéficier d\'un Code Partenaire.',
                ]);
            }
            // --- 7. Vérification anti-fraude : email pas dans AffiliationUsed ---
            $mailBlacklisted = $affiliationUsedRepository->findOneBy(['mail' => $user->getMail()]);
            if ($mailBlacklisted) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Non autorisé',
                    'message' => 'Cette adresse e-mail ne peut pas bénéficier d\'un Code Partenaire.',
                ]);
            }
            // --- 8. Récupération et validation du code saisi ---
            // Accepte les formulaires classiques et les anciens payloads JSON.
            $codeBrut = $request->request->get('codePartenaire');
            if ($codeBrut === null || trim((string) $codeBrut) === '') {
                $codeBrut = $request->request->get('code');
            }
            if (($codeBrut === null || trim((string) $codeBrut) === '')
                && str_contains(strtolower((string) $request->headers->get('Content-Type')), 'application/json')) {
                $payload = json_decode($request->getContent(), true);
                if (is_array($payload)) {
                    $codeBrut = $payload['codePartenaire'] ?? $payload['code'] ?? null;
                }
            }
            $codeSaisi = strtoupper(trim((string) $codeBrut));
            if (empty($codeSaisi)) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Attention!',
                    'message' => 'Veuillez saisir un Code Partenaire.',
                ]);
            }
            // --- 9. Trouver le propriétaire du code ---
            $partenaire = $userRepository->findOneBy(['codePartenaire' => $codeSaisi]);
            if (!$partenaire) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Code invalide',
                    'message' => 'Ce Code Partenaire est invalide ou n\'existe pas.',
                ]);
            }
            // --- 10. Vérification : pas son propre code ---
            if ($partenaire->getId() === $user->getId()) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Non autorisé',
                    'message' => 'Vous ne pouvez pas utiliser votre propre Code Partenaire.',
                ]);
            }
            // --- 11. Vérification anti-circulaire : le partenaire n'est pas l'un des accompagnés du demandeur ---
            foreach ($user->getAccompagnes() as $accompagne) {
                if ($accompagne->getId() === $partenaire->getId()) {
                    return new JsonResponse([
                        'error' => true,
                        'titre' => 'Non autorisé',
                        'message' => 'Vous ne pouvez pas utiliser le code d\'un utilisateur que vous avez vous-même accompagné.',
                    ]);
                }
            }
            // --- 12. Vérification anti-circulaire : le demandeur n'est pas le partenaire du propriétaire du code ---
            if ($partenaire->getPartenaire() !== null && $partenaire->getPartenaire()->getId() === $user->getId()) {
                return new JsonResponse([
                    'error' => true,
                    'titre' => 'Non autorisé',
                    'message' => 'Vous ne pouvez pas utiliser le code de cet utilisateur.',
                ]);
            }
            // --- 13. Création du lien d'affiliation ---
            $user->setPartenaire($partenaire);
            // --- 14. Rotation du code partenaire ---
            do {
                $nouveauCode = User::generateCodePartenaire();
                $existingCode = $userRepository->findOneBy(['codePartenaire' => $nouveauCode]);
            } while ($existingCode !== null);
            $partenaire->setCodePartenaire($nouveauCode);
            // --- 15. Notifications ---
            $nomUser = !empty(trim((string) $user->getNom())) ? $user->getNom() : $user->getPseudo();
            $nomPartenaire = !empty(trim((string) $partenaire->getNom())) ? $partenaire->getNom() : $partenaire->getPseudo();
            $traitementsDS->addNotification(
                "Félicitations ! Vous avez un nouvel accompagné : {$nomUser}.",
                $partenaire
            );
            $traitementsDS->addNotification(
                "Félicitations ! Vous êtes maintenant accompagné par {$nomPartenaire}. Bienvenue dans le réseau Dressur !",
                $user
            );
            // --- 16. Enregistrement anti-fraude ---
            $affiliationUsed = new AffiliationUsed();
            $affiliationUsed->setTel((string) $user->getTel());
            $affiliationUsed->setMail((string) $user->getMail());
            $this->em->persist($affiliationUsed);
            // --- 17. Sauvegarde ---
            $this->em->flush();
            return new JsonResponse([
                'error' => false,
                'titre' => 'Succès !',
                'message' => "Vous êtes maintenant accompagné par {$nomPartenaire}. Bienvenue dans le réseau Dressur !",
            ]);
        } catch (\Throwable $th) {
            $this->sendMail->sendReport('Error utiliserCodePartenaire : AffiliationController', $th . '<br><br><br>');
            return new JsonResponse([
                'error' => true,
                'titre' => 'Erreur!',
                'message' => 'Service temporairement indisponible.',
            ], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }
}
