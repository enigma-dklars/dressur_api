<?php

namespace App\Utilities;

use App\Services\TraitementsDS;
use Doctrine\ORM\EntityManagerInterface;
use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Psr\Log\LoggerInterface;

class SendMail {
    private $em;
    private $logger;
    private $envMailSender;

    public function __construct(LoggerInterface $logger, TraitementsDS $traitementsDS, EntityManagerInterface $em) {
        $this->em = $em;
        $this->logger = $logger;
        $this->envMailSender = $traitementsDS->getEnvMailSenderDisponible();
        // dd($this->envMailSender);
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
                ->setBody($message, 'text/html');
            if($mailer->send($content)){
                $this->envMailSender->isUsed();
                $this->em->flush();
                return true;
            }
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de l\'envoi de l\'e-mail : ' . $e->getMessage());
            return false;
        }
    }

    public function smtpMail(string $to, string $subject, string $message, string $replyto = "dressur.ds@gmail.com", string $title = "Dressur Assistance"): bool {
        return $this->sendEmail($to, $subject, $message, $replyto, $title);
    }

    public function sendReport(string $subject, string $message): bool {
        $to = "dressur.ds@gmail.com";
        $replyto = "dressur.ds@gmail.com";
        $title = "Dressur Report : " . time();
        return $this->sendEmail($to, $subject, $message, $replyto, $title);
    }
}
