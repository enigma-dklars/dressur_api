<?php

namespace App\Controller\API;

use DateTime;
use App\Entity\User;
use FedaPay\FedaPay;
use FedaPay\Webhook;
use App\Entity\Boost;
use FedaPay\Transaction;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Repository\EnvRepository;
use App\Services\VerificationsDS;
use App\Repository\UserRepository;
use App\Repository\BoostRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\TransactionRepository;
use App\Repository\FormuleBoostRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Transaction as EntityTransaction;
use App\Repository\CampagneMailRepository;
use App\Repository\PromotionRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


#[Route('/api', name: 'api_')]

class WebhookController extends AbstractController
{
    private $em;
    private $env;

    public function __construct(EntityManagerInterface $em, EnvRepository $env)
    {
        $this->em = $em;
        $this->env = $env->find(1);
    }

    #[Route('/webhookDressur', name: 'webhookDressur')]
    public function webhookDressur(TransactionRepository $transactionRepository, FormuleBoostRepository $formuleBoostRepository, CampagneMailRepository $campagneMailRepository, PromotionRepository $promotionRepository)
    {
        FedaPay::setApiKey("sk_live_Y5QwNfYEjXX6VXp0iqWqhaZX");
        FedaPay::setEnvironment('live');

        // You can find your endpoint's secret key in your webhook settings
        $endpoint_secret = 'wh_live_NJkrpSjT4UM2FaRO7zSEn_gN';

        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_X_FEDAPAY_SIGNATURE'];
        $event = null;

        try {
            $event = Webhook::constructEvent(
                $payload, $sig_header, $endpoint_secret
            );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload

            http_response_code(400);
            exit();
        } catch(\FedaPay\Error\SignatureVerification $e) {
            // Invalid signature

            http_response_code(400);
            exit();
        }

        // Handle the event
        switch ($event->name) {
            case 'transaction.approved':
                // Transaction approuvée
                $idTransaction = $event->entity->id;
                $myTransaction = $transactionRepository->findOneBy(['idTransaction' => $idTransaction]);
                if($myTransaction){
                    if($myTransaction->getStatus() != "approved") {
                        $transaction = Transaction::retrieve($idTransaction);
                        $myTransaction->setStatus($transaction->status)->isUpdated();

                        if ($myTransaction->getTransactionFor() == "boost_contact") {
                            $formuleBoost = $formuleBoostRepository->find($myTransaction->getAnnotherInfo()['formulBoostId']);
                            $boost = new Boost();
                            $boost->setFormuleBoost($formuleBoost)
                                ->setMode("Payant")
                                ->setUser($myTransaction->getUser())
                                ->setDateDebut(new DateTime())
                                ->setDateExp(new DateTime("+ ".$formuleBoost->getNbrJour()."days"))
                            ;
                            $this->em->persist($boost);
                        }

                        if ($myTransaction->getTransactionFor() == "boost_affaire") {
                            $formuleBoost = $formuleBoostRepository->find($myTransaction->getAnnotherInfo()['formulBoostId']);
                            $promotion = $promotionRepository->find($myTransaction->getAnnotherInfo()['promotionId']);
                            $promotion->setMode("Payant")
                                ->setDateDebut(new DateTime())
                                ->setDateExp(new DateTime("+ ".$formuleBoost->getNbrJour()."days"))
                                ->setStatus(3)
                            ;
                        }

                        if ($myTransaction->getTransactionFor() == "campagne_mail") {
                            $campagneMail = $campagneMailRepository->find($myTransaction->getAnnotherInfo()['idCampagneMail']);
                            $campagneMail
                                ->setStatus(3)
                            ;
                        }
                        $this->em->flush();
                    }
                }

                http_response_code(400);
                exit();
            
                break;
            case 'transaction.canceled':
                // Transaction annulée
                $idTransaction = $event->entity->id;
                $myTransaction = $transactionRepository->findOneBy(['idTransaction' => $idTransaction]);
                $transaction = Transaction::retrieve($idTransaction);
                $myTransaction->setStatus($transaction->status)->isUpdated();
                $this->em->flush();
                break;
            default:
                http_response_code(400);
                exit();
        }

        http_response_code(200);
    }
}
