<?php

namespace App\Utilities;

use Swift_Mailer;
use Swift_Message;
use Swift_SmtpTransport;

class SendMail {
    public function smtpMail(string $to, string $subject, string $message, string $replyto = "whatsperson@gmail.com", string $title = "WhatsPerson Assistance"):bool {

        // ajrj nskf tfmk virv 

        $smtpPasse = 'ajrjnskftfmkvirv';
        $smtpServer = 'smtp.gmail.com';
        $smtpPort = '587';
        $smtpSecured = 'tls';

        $from = "whatsperson@gmail.com";

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