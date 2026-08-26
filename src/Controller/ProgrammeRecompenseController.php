<?php

namespace App\Controller;

use App\Entity\HistoriqueProgrammeRecompense;
use App\Entity\Preuve;
use App\Entity\User;
use App\Repository\HistoriqueProgrammeRecompenseRepository;
use App\Repository\PromotionRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Services\CookieDS;
use App\Services\TraitementsDS;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class ProgrammeRecompenseController extends AbstractController
{
    public function __construct(
        private readonly TraitementsDS $traitementsDS,
        private readonly CookieDS $cookieDS,
        private readonly UserRepository $userRepository,
        private readonly TransactionRepository $transactionRepository,
        private readonly PromotionRepository $promotionRepository,
        private readonly HistoriqueProgrammeRecompenseRepository $historiqueRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route('/programme-recompense', name: 'app_programme_recompense', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->redirectToRoute('app_connexion');
        }

        if ($user->getIsInscritProgrammeRecompense()) {
            return $this->redirectToRoute('app_programme_recompense_dashboard');
        }

        return $this->render('private/programme_recompense/start.html.twig', [
            'user' => $this->traitementsDS->infosUser($user),
            'theme' => $this->theme(),
            'conditions' => $this->programmeConditions($user),
        ]);
    }

    #[Route('/programme-recompense/inscription', name: 'app_programme_recompense_inscription', methods: ['POST'])]
    public function inscription(Request $request): Response
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->redirectToRoute('app_connexion');
        }

        if (!$this->isCsrfTokenValid('programme_recompense_inscription', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'La session du formulaire a expiré. Veuillez réessayer.');
            return $this->redirectToRoute('app_programme_recompense');
        }

        if (!$user->getIsInscritProgrammeRecompense()) {
            $conditions = $this->programmeConditions($user);
            if (!$conditions['toutesConditionsRemplies']) {
                $this->addFlash('warning', 'Toutes les conditions doivent être remplies avant de rejoindre le programme.');
                return $this->redirectToRoute('app_programme_recompense');
            }

            $user->setIsInscritProgrammeRecompense(true);
            $this->entityManager->flush();
        }

        return $this->redirectToRoute('app_programme_recompense_dashboard');
    }

    #[Route('/programme-recompense/dashboard', name: 'app_programme_recompense_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->redirectToRoute('app_connexion');
        }
        if (!$user->getIsInscritProgrammeRecompense()) {
            return $this->redirectToRoute('app_programme_recompense');
        }

        $this->refreshHistoryStatuses($user);
        $history = $this->historiqueRepository->findBy(['user' => $user], ['id' => 'DESC']);
        $views = 0;
        $earnings = 0;
        foreach ($history as $item) {
            $views += (int) ($item->getNbrVue() ?? 0);
            $earnings += (int) ($item->getRecompense() ?? 0);
        }

        return $this->render('private/programme_recompense/dashboard.html.twig', [
            'user' => $this->traitementsDS->infosUser($user),
            'theme' => $this->theme(),
            'history' => $history,
            'views' => $views,
            'earnings' => $earnings,
            'balance' => (int) ($user->getSoldeProgrammeRecompense() ?? 0),
        ]);
    }

    #[Route('/programme-recompense/promotions', name: 'app_programme_recompense_promotions', methods: ['GET'])]
    public function promotions(): Response
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->redirectToRoute('app_connexion');
        }
        if (!$user->getIsInscritProgrammeRecompense()) {
            return $this->redirectToRoute('app_programme_recompense');
        }

        return $this->render('private/programme_recompense/promotions.html.twig', [
            'user' => $this->traitementsDS->infosUser($user),
            'theme' => $this->theme(),
            'promotions' => $this->traitementsDS->listePromotionAffaireInProgrammeRecompense($user),
        ]);
    }

    #[Route('/programme-recompense/promotions/{id}/partager', name: 'app_programme_recompense_partager', methods: ['POST'])]
    public function partager(Request $request, int $id): JsonResponse
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->json(['error' => true, 'message' => 'Votre session a expiré.'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$user->getIsInscritProgrammeRecompense()) {
            return $this->json(['error' => true, 'message' => 'Vous devez rejoindre le programme avant de participer.'], Response::HTTP_FORBIDDEN);
        }
        if (!$this->isCsrfTokenValid('programme_recompense_partager_'.$id, (string) $request->request->get('_token'))) {
            return $this->json(['error' => true, 'message' => 'La session du formulaire a expiré.'], Response::HTTP_BAD_REQUEST);
        }

        $promotion = $this->promotionRepository->find($id);
        if (!$promotion || !$promotion->isInProgrammeRecompense()) {
            return $this->json(['error' => true, 'message' => 'Cette promotion n’est plus disponible dans le programme.'], Response::HTTP_NOT_FOUND);
        }

        $isEligible = false;
        foreach ($this->traitementsDS->listePromotionAffaireInProgrammeRecompense($user) as $available) {
            if ((int) ($available['id'] ?? 0) === $id) {
                $isEligible = true;
                break;
            }
        }
        if (!$isEligible) {
            return $this->json(['error' => true, 'message' => 'Cette promotion n’est pas disponible pour votre compte actuellement.'], Response::HTTP_CONFLICT);
        }

        $approved = $this->historiqueRepository->findOneBy(
            ['promotion' => $promotion, 'status' => 'approuver', 'user' => $user],
            ['id' => 'DESC']
        );
        if ($approved && $approved->getExpiredAt() && new DateTime() < $approved->getExpiredAt()) {
            return $this->json(['error' => true, 'message' => 'Votre précédente participation à cette promotion est encore active.'], Response::HTTP_CONFLICT);
        }

        $history = $this->historiqueRepository->findOneBy([
            'user' => $user,
            'promotion' => $promotion,
            'status' => 'en_cours',
        ]);
        if (!$history) {
            $history = (new HistoriqueProgrammeRecompense())
                ->setUser($user)
                ->setPromotion($promotion);
            $this->entityManager->persist($history);
        } else {
            $history->estPartager()->setUpdatedAt(new DateTime());
        }
        $this->entityManager->flush();

        $description = (string) $promotion->getDescription();
        $reference = (string) $history->getReferenceParticipation();
        $shareText = "Ref : {$reference}\n\n{$description}\n\nRef : {$reference}";

        return $this->json([
            'error' => false,
            'referenceParticipation' => $reference,
            'shareText' => $shareText,
            'imageUrl' => '/promotion/'.rawurlencode((string) $promotion->getImage()),
        ]);
    }

    #[Route('/programme-recompense/preuves', name: 'app_programme_recompense_preuves', methods: ['POST'])]
    public function preuves(Request $request): Response
    {
        $user = $this->currentUser();
        if (!$user) {
            return $this->redirectToRoute('app_connexion');
        }
        if (!$user->getIsInscritProgrammeRecompense()) {
            return $this->redirectToRoute('app_programme_recompense');
        }
        if (!$this->isCsrfTokenValid('programme_recompense_preuves', (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'La session du formulaire a expiré. Veuillez réessayer.');
            return $this->redirectToRoute('app_programme_recompense_dashboard');
        }

        $history = $this->historiqueRepository->find((int) $request->request->get('idHistorique'));
        $captureListe = $request->files->get('capture1');
        $captureOuvert = $request->files->get('capture2');
        if (!$history || !$history->getUser() || $history->getUser()->getId() !== $user->getId()) {
            $this->addFlash('danger', 'Cette participation ne vous appartient pas.');
            return $this->redirectToRoute('app_programme_recompense_dashboard');
        }
        if (!in_array($history->getStatus(), ['en_cours', 'terminer'], true)) {
            $this->addFlash('warning', 'Cette participation ne peut plus recevoir de preuve.');
            return $this->redirectToRoute('app_programme_recompense_dashboard');
        }
        if (!$captureListe instanceof UploadedFile || !$captureOuvert instanceof UploadedFile || !$captureListe->isValid() || !$captureOuvert->isValid()) {
            $this->addFlash('danger', 'Veuillez joindre les deux captures demandées.');
            return $this->redirectToRoute('app_programme_recompense_dashboard');
        }

        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($captureListe->getMimeType(), $allowedMimeTypes, true) || !in_array($captureOuvert->getMimeType(), $allowedMimeTypes, true)) {
            $this->addFlash('danger', 'Les preuves doivent être des images JPG, PNG ou WEBP.');
            return $this->redirectToRoute('app_programme_recompense_dashboard');
        }

        $directory = (string) $this->getParameter('preuve_recompense');
        $filesystem = new Filesystem();
        if (!$filesystem->exists($directory)) {
            $filesystem->mkdir($directory, 0775);
        }

        try {
            $fileName1 = 'preuve_'.$user->getUid().'_'.Uuid::v4()->toRfc4122().'.'.$captureListe->guessExtension();
            $fileName2 = 'preuve_'.$user->getUid().'_'.Uuid::v4()->toRfc4122().'.'.$captureOuvert->guessExtension();
            $captureListe->move($directory, $fileName1);
            $captureOuvert->move($directory, $fileName2);

            $history->setStatus('en_attente');
            $preuve = (new Preuve())
                ->setUser($user)
                ->setHistoriqueProgrammeRecompense($history)
                ->setCaptureListeStatut($fileName1)
                ->setCaptureStatutOuvert($fileName2);
            $this->entityManager->persist($preuve);
            $this->entityManager->flush();
            $this->addFlash('success', 'Vos deux preuves ont été enregistrées. Envoyez maintenant la vidéo demandée à l’assistance Dressur sur WhatsApp.');
        } catch (\Throwable) {
            $this->addFlash('danger', 'Les preuves n’ont pas pu être enregistrées. Veuillez réessayer.');
        }

        return $this->redirectToRoute('app_programme_recompense_dashboard');
    }

    private function currentUser(): ?User
    {
        $user = $this->traitementsDS->getUserByUidInCookies();
        return $user instanceof User ? $user : null;
    }

    private function programmeConditions(User $user): array
    {
        $orders = $this->transactionRepository->countPaidServicesTransactions($user);
        $conditions = [
            'inscritDepuis7Jours' => $user->getCreatedAt() <= new DateTime('-7 days'),
            'mailConfirme' => $user->getMailIsVerified() === true,
            'whatsappConfirme' => $user->getTelIsVerified() === true,
            'cinqCommandes' => $orders >= 5,
            'nbrCommandes' => $orders,
        ];
        $conditions['toutesConditionsRemplies'] = $conditions['inscritDepuis7Jours']
            && $conditions['mailConfirme']
            && $conditions['whatsappConfirme']
            && $conditions['cinqCommandes'];

        return $conditions;
    }

    private function refreshHistoryStatuses(User $user): void
    {
        $changed = false;
        $deadline = new DateTime('-23 hours', new \DateTimeZone('Africa/Lagos'));
        foreach ($this->historiqueRepository->findBy(['user' => $user], ['id' => 'DESC']) as $history) {
            if (!in_array($history->getStatus(), ['en_cours', 'terminer'], true)) {
                continue;
            }
            if (($history->getCreatedAt() && $history->getCreatedAt() <= $deadline)
                || !$history->getPromotion()->isInProgrammeRecompense()) {
                $history->setStatus('echouer');
                $changed = true;
            }
        }
        if ($changed) {
            $this->entityManager->flush();
        }
    }

    private function theme(): string
    {
        return $this->cookieDS->get('theme') === 'dark-theme' ? 'dark-theme' : 'light-theme';
    }
}
