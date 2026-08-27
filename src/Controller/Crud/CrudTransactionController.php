<?php

namespace App\Controller\Crud;

use App\Entity\Transaction;
use App\Form\TransactionType;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\CookieDS;
use App\Services\TraitementsDS;

#[Route('/crud/transaction')]
class CrudTransactionController extends AbstractController
{
    private $theme;
    private $cookieDS;
    private $traitementsDS;

    public function __construct(CookieDS $cookieDS, TraitementsDS $traitementsDS)
    {
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
    
    #[Route('/', name: 'app_crud_transaction_index', methods: ['GET'])]
    public function index(TransactionRepository $transactionRepository, Request $request): Response
    {
        $deletedTransactionsCount = $transactionRepository->deleteNonApprovedOlderThan(
            new \DateTimeImmutable('-6 months')
        );

        if ($deletedTransactionsCount > 0) {
            $this->addFlash(
                'success',
                sprintf(
                    'Nettoyage automatique des transactions : %d transaction(s) non approuvée(s), datant de plus de six mois, ont été supprimée(s).',
                    $deletedTransactionsCount
                )
            );
        }

        $sourceFilter = $request->query->get('source', '');

        if ($sourceFilter) {
            $transactions = $transactionRepository->findBySourceFilter($sourceFilter);
        } else {
            $transactions = $transactionRepository->findBy([], ['id' => 'DESC']);
        }

        return $this->render('crud_transaction/index.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'transactions' => $transactions,
            'sourceFilter' => $sourceFilter,
            'sourceCounts' => $transactionRepository->getSourceCounts(),
        ]);
    }

    #[Route('/new', name: 'app_crud_transaction_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $transaction = new Transaction();
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($transaction);
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_transaction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_transaction/new.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'transaction' => $transaction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_transaction_show', methods: ['GET'])]
    public function show(Transaction $transaction): Response
    {
        return $this->render('crud_transaction/show.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'transaction' => $transaction,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_crud_transaction_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TransactionType::class, $transaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_crud_transaction_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('crud_transaction/edit.html.twig', [
            'theme' => $this->theme,
            'user' => $this->traitementsDS->getUserByUidInCookies(),
            'transaction' => $transaction,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_crud_transaction_delete', methods: ['POST'])]
    public function delete(Request $request, Transaction $transaction, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$transaction->getId(), $request->request->get('_token'))) {
            $entityManager->remove($transaction);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_crud_transaction_index', [], Response::HTTP_SEE_OTHER);
    }
}
