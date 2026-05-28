<?php

namespace App\Utilities;

use App\Repository\EnvMailSenderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Psr\Log\LoggerInterface;

class SendMail {
    private $em;
    private $logger;
    private $envMailSender;
    private $envMailSenderRepository;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, EnvMailSenderRepository $envMailSenderRepository) {
        $this->em = $em;
        $this->logger = $logger;
        $this->envMailSenderRepository = $envMailSenderRepository;
        $this->envMailSender = $this->verifyAndSelectSender();
    }

    private function verifyAndSelectSender() {
        $maxAttempts = max(count($this->envMailSenderRepository->findBy(['activated' => true])), 1);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $candidate = $this->getEnvMailSenderDisponible();
            if (!$candidate) {
                return false;
            }
            try {
                $transport = new Swift_SmtpTransport(
                    $candidate->getSmtpServer(),
                    $candidate->getSmtpPort(),
                    $candidate->getSmtpSecured()
                );
                $transport->setUsername($candidate->getMailAdresse());
                $transport->setPassword($candidate->getPassword());
                $transport->start();
                $transport->stop();
                return $candidate;
            } catch (\Exception $e) {
                if ($candidate->isActivated()) {
                    $candidate->setActivated(false);
                    $this->em->flush();
                }
                $this->logger->error('SendMail init: sender désactivé (' . $candidate->getMailAdresse() . ') — connexion invalide : ' . $e->getMessage());
            }
        }

        $this->logger->error('SendMail init: aucun sender SMTP valide disponible.');
        return false;
    }
    

    public function getEnvMailSenderDisponible() {
        $envMailSenders = $this->envMailSenderRepository->findBy(['activated' => true]);
        foreach ($envMailSenders as $envMailSender) {
            if($envMailSender->getCountMailSent() < 99) {
                return $envMailSender;
            }
        }
        if (!empty($envMailSenders)) {
            foreach ($envMailSenders as $envMailSender) {
                $envMailSender->setCountMailSent(0);
            }
            $this->em->flush();
            return $envMailSenders[0];
        }
        return false;
    }

    private function isAuthError(string $msg): bool {
        $authKeywords = ['535', '534', '530', 'authentication', 'credentials', 'password', 'username', 'login failed', 'auth'];
        $lower = strtolower($msg);
        foreach ($authKeywords as $keyword) {
            if (strpos($lower, $keyword) !== false) {
                return true;
            }
        }
        return false;
    }

    private function deactivateCurrentSender(string $reason): void {
        $this->envMailSender->setActivated(false);
        $this->em->flush();
        $this->logger->error('Sender désactivé (' . $this->envMailSender->getMailAdresse() . ') — raison : ' . $reason);
    }

    private function sendEmail($to, $subject, $message, $replyto, $title) {
        $maxAttempts = max(count($this->envMailSenderRepository->findBy(['activated' => true])), 1);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            if (!$this->envMailSender) {
                $this->logger->error('Aucun sender SMTP disponible après ' . $attempt . ' tentative(s).');
                return false;
            }

            try {
                $transport = (new Swift_SmtpTransport($this->envMailSender->getSmtpServer(), $this->envMailSender->getSmtpPort(), $this->envMailSender->getSmtpSecured()))
                    ->setUsername($this->envMailSender->getMailAdresse())
                    ->setPassword($this->envMailSender->getPassword())
                ;
                $mailer = new Swift_Mailer($transport);
                $content = (new Swift_Message())
                    ->setSubject($subject)
                    ->setFrom([$this->envMailSender->getMailAdresse() => $title])
                    ->setReplyTo($replyto)
                    ->setTo($to)
                    ->setBody($message, 'text/html')
                ;

                if ($mailer->send($content)) {
                    $this->envMailSender->isUsed();
                    $this->em->flush();
                    return true;
                }
                return false;
            } catch (\Exception $e) {
                $msgError = (string)$e;
                if (strpos($msgError, "hostinger_out_ratelimit") !== false) {
                    $this->deactivateCurrentSender('hostinger_out_ratelimit');
                } elseif ($this->isAuthError($msgError)) {
                    $this->deactivateCurrentSender('erreur d\'authentification SMTP');
                } else {
                    $this->logger->error('Erreur non récupérable lors de l\'envoi : ' . $e->getMessage());
                    return false;
                }
                $this->logger->error('Tentative ' . ($attempt + 1) . ' échouée, basculement vers le sender suivant.');
                $this->envMailSender = $this->getEnvMailSenderDisponible();
            }
        }

        $this->logger->error('Échec définitif : tous les senders SMTP ont été essayés.');
        return false;
    }

    private function sendEmailMultiple(array $to, $subject, $message, $replyto, $title) {
        $maxAttempts = max(count($this->envMailSenderRepository->findBy(['activated' => true])), 1);
        $batches = array_chunk($to, 20); // Découpe en groupes de 20 emails

        foreach ($batches as $batch) {
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                if (!$this->envMailSender) {
                    $this->logger->error('Aucun sender SMTP disponible pour ce lot après ' . $attempt . ' tentative(s).');
                    break;
                }

                try {
                    $transport = (new Swift_SmtpTransport($this->envMailSender->getSmtpServer(), $this->envMailSender->getSmtpPort(), $this->envMailSender->getSmtpSecured()))
                        ->setUsername($this->envMailSender->getMailAdresse())
                        ->setPassword($this->envMailSender->getPassword());
                    $mailer = new Swift_Mailer($transport);
                    $content = (new Swift_Message())
                        ->setSubject($subject)
                        ->setFrom([$this->envMailSender->getMailAdresse() => $title])
                        ->setReplyTo($replyto)
                        ->setBcc($batch) // Utilisation de BCC pour cacher les destinataires
                        ->setBody($message, 'text/html')
                    ;

                    if ($mailer->send($content)) {
                        $this->envMailSender->setCountMailSent($this->envMailSender->getCountMailSent() + 20);
                        $this->em->flush();
                    } else {
                        $this->logger->error('Échec de l\'envoi du lot d\'emails.');
                    }
                    break;
                } catch (\Exception $e) {
                    $msgError = (string)$e;
                    if (strpos($msgError, "hostinger_out_ratelimit") !== false) {
                        $this->deactivateCurrentSender('hostinger_out_ratelimit');
                    } elseif ($this->isAuthError($msgError)) {
                        $this->deactivateCurrentSender('erreur d\'authentification SMTP');
                    } else {
                        $this->logger->error('Erreur non récupérable sur le lot : ' . $e->getMessage());
                        break;
                    }
                    $this->logger->error('Lot : tentative ' . ($attempt + 1) . ' échouée, basculement vers le sender suivant.');
                    $this->envMailSender = $this->getEnvMailSenderDisponible();
                }
            }

            sleep(2); // Pause de 2 secondes pour éviter un éventuel blocage SMTP
            $this->envMailSender = $this->getEnvMailSenderDisponible();
        }

        return true;
    }

    public function smtpMail($to, string $subject, string $message, string $replyto = "dressur.ds@gmail.com", string $title = "Dressur Assistance"): bool {
        if(is_array($to)) {
            return $this->sendEmailMultiple($to, $subject, $message, $replyto, $title);
        } else {
            return $this->sendEmail($to, $subject, $message, $replyto, $title);
        }
    }

    public function sendReport(string $subject, string $message): bool {
        $to = "dressur.ds@gmail.com";
        $replyto = "dressur.ds@gmail.com";
        $title = "Dressur Report : " . time();
        return $this->sendEmail($to, $subject, $message, $replyto, $title);
    }
}
