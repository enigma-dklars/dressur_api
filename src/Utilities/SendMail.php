<?php

namespace App\Utilities;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Psr\Log\LoggerInterface;

class SendMail {
    private $logger;
    private $from;
    private $password;
    private $smtpServer;
    private $smtpPort;
    private $smtpSecured;
    private $smtpMails = [
        ["noreply1@dressur.site", "nunewqi_DS3", "smtp.titan.email", "465", "ssl"],
        ["noreply2@dressur.site", "nunewqi_DS3", "smtp.titan.email", "465", "ssl"],
        ["noreply3@dressur.site", "nunewqi_DS3", "smtp.titan.email", "465", "ssl"],
        ["noreply4@dressur.site", "nunewqi_DS3", "smtp.titan.email", "465", "ssl"],
        ["noreply5@dressur.site", "nunewqi_DS3", "smtp.titan.email", "465", "ssl"],
        
        // ["noreply1.dressur.ds@gmail.com", "maatzpxlfnunewqi", "smtp.gmail.com", "587", "tls"],
        // ["noreply2.dressur.ds@gmail.com", "eeufhrlrlbicxdmr", "smtp.gmail.com", "587", "tls"],
        // ["noreply3.dressur.ds@gmail.com", "styzjjntshtyhzcr", "smtp.gmail.com", "587", "tls"],
        // ["noreply4.dressur.ds@gmail.com", "vutvoskfzywhpgjp", "smtp.gmail.com", "587", "tls"],

        // ["noreply6.dressur.ds@gmail.com", "uxfzdpsgqkbzryhy", "smtp.gmail.com", "587", "tls"],
        // ["noreply7.dressur.ds@gmail.com", "fkfpukqzwlgueadu", "smtp.gmail.com", "587", "tls"],
        // ["noreply8.dressur.ds@gmail.com", "gsdmjxfqnzjbwpxq", "smtp.gmail.com", "587", "tls"],
        // ["noreply9.dressur.ds@gmail.com", "amjybgcikdvtsrem", "smtp.gmail.com", "587", "tls"],
        // ["noreply10.dressur.ds@gmail.com", "ylmdtwreacsymamc", "smtp.gmail.com", "587", "tls"],
    ];

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
        $$randomSmtpMail = $this->smtpMails[array_rand($this->smtpMails)];
        $this->from = $randomSmtpMail[0];
        $this->password = $randomSmtpMail[1];
        $this->smtpServer = $randomSmtpMail[2];
        $this->smtpPort = $randomSmtpMail[3];
        $this->smtpSecured = $randomSmtpMail[4];
    }

    private function sendEmail($to, $subject, $message, $replyto, $title) {
        try {
            $transport = (new Swift_SmtpTransport($this->smtpServer, $this->smtpPort, $this->smtpSecured))
                ->setUsername($this->from)
                ->setPassword($this->password)
            ;
            $mailer = new Swift_Mailer($transport);
            $content = (new Swift_Message())
                ->setSubject($subject)
                ->setFrom([$this->from => $title])
                ->setReplyTo($replyto)
                ->setTo($to)
                ->setBody($message, 'text/html');
            return $mailer->send($content);
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
