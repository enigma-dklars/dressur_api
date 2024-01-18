<?php

namespace App\Utilities;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class SendMail {
    public function smtpMail(string $to, string $subject, string $message, string $replyto = "dressur.ds@gmail.com", string $title = "Dressur Assistance"):bool
    {
        $smtpMails = [
            ["dressur.ds@gmail.com", "cbecrafioykvabbl"],
            ["no_reply_1_dressur.ds@gmail.com", "zgcyvmfiguinqddz"],
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