<?php

namespace App\Utilities;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;
use Psr\Log\LoggerInterface;

class SendMail {
    private $logger;
    private $smtpServer = 'smtp.gmail.com';
    private $smtpPort = '587';
    private $smtpSecured = 'tls';
    // private $smtpServer = 'smtp.aol.com';
    private $smtpMails = [
        // ["noreply1@dressur.site", "nunewqi_DS3", "smtp.titan.email", "465", "ssl"],
        // ["noreply2@dressur.site", "nunewqi_DS3", "smtp.titan.email", "465", "ssl"],
        // ["noreply3@dressur.site", "nunewqi_DS3", "smtp.titan.email", "465", "ssl"],
        // ["noreply4@dressur.site", "nunewqi_DS3", "smtp.titan.email", "465", "ssl"],
        // ["noreply5@dressur.site", "nunewqi_DS3", "smtp.titan.email", "465", "ssl"],
        ["noreply1.dressur.ds@gmail.com", "maatzpxlfnunewqi"],
        ["noreply2.dressur.ds@gmail.com", "eeufhrlrlbicxdmr"],
        ["noreply3.dressur.ds@gmail.com", "styzjjntshtyhzcr"],
        ["noreply4.dressur.ds@gmail.com", "vutvoskfzywhpgjp"],
        ["noreply5.dressur.ds@gmail.com", "znjaliibcopwqgea"],
        ["noreply6.dressur.ds@gmail.com", "uxfzdpsgqkbzryhy"],
        ["noreply7.dressur.ds@gmail.com", "fkfpukqzwlgueadu"],
        ["noreply8.dressur.ds@gmail.com", "gsdmjxfqnzjbwpxq"],
        ["noreply9.dressur.ds@gmail.com", "amjybgcikdvtsrem"],
        ["noreply10.dressur.ds@gmail.com", "ylmdtwreacsymamc"],

        ["noreply11.dressur.ds@gmail.com", "rlbsslizifaozwvx"],
        ["noreply12.dressur.ds@gmail.com", "lqcndnuyhlszgpxa"],
        ["noreply13.dressur.ds@gmail.com", "gksmtwsrsanndorr"],
        ["noreply14.dressur.ds@gmail.com", "htzremgpiyfvkfdi"],
        // ["noreply15.dressur.ds@gmail.com", "puolmrjnisqxpkhz"],
        // ["noreply16.dressur.ds@gmail.com", "rwyslcjbdzfwlbyx"],
        // ["noreply17.dressur.ds@gmail.com", "ygxrqmdjlbmmcsey"],
        // ["dressurun@aol.com", "Fhp*k8M%&r%3QnS"],
    ];

    public function __construct(LoggerInterface $logger) {
        $this->logger = $logger;
    }

    private function sendEmail($from, $smtpPasse, $to, $subject, $message, $replyto, $title) {
        try {
            $transport = (new Swift_SmtpTransport($this->smtpServer, $this->smtpPort, $this->smtpSecured))
                ->setUsername($from)
                ->setPassword($smtpPasse)
            ;
            $mailer = new Swift_Mailer($transport);
            $content = (new Swift_Message())
                ->setSubject($subject)
                ->setFrom([$from => $title])
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
        $randomIndex = array_rand($this->smtpMails);
        $randomSmtpMail = $this->smtpMails[$randomIndex];
        $from = $randomSmtpMail[0];
        $smtpPasse = $randomSmtpMail[1];

        return $this->sendEmail($from, $smtpPasse, $to, $subject, $message, $replyto, $title);
    }

    public function sendReport(string $subject, string $message): bool {
        $randomIndex = array_rand($this->smtpMails);
        $randomSmtpMail = $this->smtpMails[$randomIndex];
        $from = $randomSmtpMail[0];
        $smtpPasse = $randomSmtpMail[1];
        $to = "dressur.ds@gmail.com";
        $replyto = "dressur.ds@gmail.com";
        $title = "Dressur Report : " . time();

        return $this->sendEmail($from, $smtpPasse, $to, $subject, $message, $replyto, $title);
    }
}
