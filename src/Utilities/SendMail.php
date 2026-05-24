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
        $this->envMailSender = $this->getEnvMailSenderDisponible();
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

    private function sendEmail($to, $subject, $message, $replyto, $title) {
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
            
            if($mailer->send($content)){
                $this->envMailSender->isUsed();
                $this->em->flush();
                return true;
            }
        } catch (\Exception $e) {
            $msgError = (string)$e;
            if (strpos($msgError, "hostinger_out_ratelimit") !== false) {
                $this->envMailSender->setActivated(false);
                $this->em->flush();
            }
            $this->logger->error('Erreur lors de l\'envoi de l\'e-mail : ' . $e->getMessage());
            return false;
        }
    }

    private function sendEmailMultiple(array $to, $subject, $message, $replyto, $title) {
        try {
            $transport = (new Swift_SmtpTransport($this->envMailSender->getSmtpServer(), $this->envMailSender->getSmtpPort(), $this->envMailSender->getSmtpSecured()))
                ->setUsername($this->envMailSender->getMailAdresse())
                ->setPassword($this->envMailSender->getPassword());
    
            $mailer = new Swift_Mailer($transport);
            $batches = array_chunk($to, 20); // Découpe en groupes de 20 emails
            
            foreach ($batches as $batch) {
                $content = (new Swift_Message())
                    ->setSubject($subject)
                    ->setFrom([$this->envMailSender->getMailAdresse() => $title])
                    ->setReplyTo($replyto)
                    ->setBcc($batch) // Utilisation de BCC pour cacher les destinataires
                    ->setBody($message, 'text/html')
                ;
    
                if ($mailer->send($content)) {
                    $this->envMailSender->setCountMailSent($this->envMailSender->getCountMailSent() + 20);
                } else {
                    $this->logger->error('Échec de l\'envoi du lot d\'emails.');
                }
                
                sleep(2); // Pause de 2 secondes pour éviter un éventuel blocage SMTP
                $this->envMailSender = $this->getEnvMailSenderDisponible();
            }
            
            $this->em->flush();
    
            return true;
        } catch (\Exception $e) {
            $msgError = (string)$e;
            if (strpos($msgError, "hostinger_out_ratelimit") !== false) {
                $this->envMailSender->setActivated(false);
                $this->em->flush();
            }
            $this->logger->error('Erreur lors de l\'envoi de l\'e-mail : ' . $e->getMessage());
            return false;
        }
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
