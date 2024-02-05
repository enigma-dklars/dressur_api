<?php

namespace App\Utilities;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class SendMail {
    public function smtpMail(string $to, string $subject, string $message, string $replyto = "dressur.ds@gmail.com", string $title = "Dressur Assistance"):bool
    {
        $smtpMails = [
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
        ];
        
        $randomIndex = array_rand($smtpMails);
        $randomSmtpMail = $smtpMails[$randomIndex];

        $from = $randomSmtpMail[0];
        $smtpPasse = $randomSmtpMail[1];
        $smtpServer = 'smtp.gmail.com';
        $smtpPort = '587';
        $smtpSecured = 'tls';

        try{
            $transport = (new Swift_SmtpTransport($smtpServer, $smtpPort, $smtpSecured)) 
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