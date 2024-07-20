<?php

namespace App\Utilities;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class SendMail {
    private $smtpServer = 'smtp.gmail.com';
    private $smtpPort = '587';
    private $smtpSecured = 'tls';
    private $smtpMails = [
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
    ];

    public function smtpMail(string $to, string $subject, string $message, string $replyto = "dressur.ds@gmail.com", string $title = "Dressur Assistance"):bool
    {
        $randomIndex = array_rand($this->smtpMails);
        $randomSmtpMail = $this->smtpMails[$randomIndex];

        $from = $randomSmtpMail[0];
        $smtpPasse = $randomSmtpMail[1];

        try{
            $transport = (new Swift_SmtpTransport($this->smtpServer, $this->smtpPort, $this->smtpSecured)) 
                ->setUsername($from)
                ->setPassword($smtpPasse);
            $mailer = new Swift_Mailer($transport); 
            $content = (new Swift_Message())
                ->setSubject($subject)
                ->setFrom($from, $title)
                ->setReplyTo($replyto)
                ->setTo($to)
                ->setBody($message, 'text/html');
            if ($mailer->send($content)) {
                return true;
            } else {
                return false;
            }
        }catch (\Exception $e) {
            return false;
        }
    }

    public function sendReport(string $subject, string $message):bool
    {
        $randomIndex = array_rand($this->smtpMails);
        $randomSmtpMail = $this->smtpMails[$randomIndex];

        $from = $randomSmtpMail[0];
        $smtpPasse = $randomSmtpMail[1];

        $to = "dressur.ds@gmail.com";
        $replyto = "dressur.ds@gmail.com";
        $title = "Dressur Report : ".time();

        try{
            $transport = (new Swift_SmtpTransport($this->smtpServer, $this->smtpPort, $this->smtpSecured)) 
                ->setUsername($from)
                ->setPassword($smtpPasse);
            $mailer = new Swift_Mailer($transport); 
            $content = (new Swift_Message())
                ->setSubject($subject)
                ->setFrom($from, $title)
                ->setReplyTo($replyto)
                ->setTo($to)
                ->setBody($message, 'text/html');
            if ($mailer->send($content)) {
                return true;
            } else {
                return false;
            }
        }catch (\Exception $e) {
            return false;
        }
    }
}