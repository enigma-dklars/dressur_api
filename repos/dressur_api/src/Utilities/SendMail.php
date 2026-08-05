<?php

namespace App\Utilities;

use DateTime;
use App\Entity\LogBoiteMail;
use App\Repository\EnvMailSenderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Swift_Mailer;
use Swift_Message;
use Swift_Signers_DKIMSigner;
use Swift_SmtpTransport;
use Psr\Log\LoggerInterface;

class SendMail
{
    private $em;
    private $logger;
    private $envMailSender;
    private $envMailSenderRepository;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        EnvMailSenderRepository $envMailSenderRepository
    ) {
        $this->em                      = $em;
        $this->logger                  = $logger;
        $this->envMailSenderRepository = $envMailSenderRepository;
        $this->envMailSender           = $this->verifyAndSelectSender();
    }

    // ─── Sélection round-robin ────────────────────────────────────────────────
    //
    //  Principe : parmi les comptes activés, on choisit celui dont
    //  lastUsedAt est le plus ancien (ou null = jamais utilisé → priorité max).
    //  Ainsi chaque envoi tourne automatiquement au compte suivant.

    private function getNextSenderRoundRobin()
    {
        $senders = $this->envMailSenderRepository->findBy(['activated' => true]);

        if (empty($senders)) {
            return false;
        }

        usort($senders, function ($a, $b) {
            $aDate = $a->getLastUsedAt();
            $bDate = $b->getLastUsedAt();

            // null (jamais utilisé) passe en premier
            if ($aDate === null && $bDate === null) {
                return 0;
            }
            if ($aDate === null) {
                return -1;
            }
            if ($bDate === null) {
                return 1;
            }

            // Le plus ancien passe en premier
            return $aDate <=> $bDate;
        });

        return $senders[0];
    }

    // Alias public conservé pour la rétrocompatibilité (utilisé dans TraitementsDS, etc.)
    public function getEnvMailSenderDisponible()
    {
        return $this->getNextSenderRoundRobin();
    }

    // ─── Connexion SMTP de vérification initiale ──────────────────────────────

    private function verifyAndSelectSender()
    {
        $maxAttempts = max(count($this->envMailSenderRepository->findBy(['activated' => true])), 1);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $candidate = $this->getNextSenderRoundRobin();
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
                $this->logger->error(
                    'SendMail init: sender désactivé (' . $candidate->getMailAdresse() . ') — ' . $e->getMessage()
                );
            }
        }

        $this->logger->error('SendMail init: aucun sender SMTP valide disponible.');

        return false;
    }

    // ─── Désactivation du sender courant ─────────────────────────────────────

    private function deactivateCurrentSender(string $reason): void
    {
        $this->envMailSender->setActivated(false);
        $this->em->flush();
        $this->logger->error(
            'Sender désactivé (' . $this->envMailSender->getMailAdresse() . ') — raison : ' . $reason
        );
    }

    private function isAuthError(string $msg): bool
    {
        $keywords = ['535', '534', '530', 'authentication', 'credentials', 'password', 'username', 'login failed', 'auth'];
        $lower    = strtolower($msg);
        foreach ($keywords as $kw) {
            if (strpos($lower, $kw) !== false) {
                return true;
            }
        }

        return false;
    }

    // ─── Enregistrement du log ────────────────────────────────────────────────

    private function logSend(string $raison, string $emailSender, string $emailRecepteur): void
    {
        try {
            $log = new LogBoiteMail($raison, $emailSender, $emailRecepteur);
            $this->em->persist($log);
            // flush différé : sera inclus dans le flush suivant du contexte appelant
        } catch (\Exception $e) {
            $this->logger->error('LogBoiteMail: impossible d\'enregistrer le log — ' . $e->getMessage());
        }
    }

    // ─── Signature DKIM ───────────────────────────────────────────────────────

    private function getDkimSigner(string $senderEmail): ?Swift_Signers_DKIMSigner
    {
        $domain = substr(strrchr($senderEmail, '@'), 1);
        $keyMap = [
            'dressur.site'      => 'config/dkim/dkim_dressur_site.pem',
            'bluelifetech.site' => 'config/dkim/dkim_bluelifetech_site.pem',
        ];
        if (!isset($keyMap[$domain])) {
            return null;
        }
        $keyPath = $keyMap[$domain];
        if (!file_exists($keyPath)) {
            $this->logger->error('DKIM : clé privée introuvable pour ' . $domain . ' — ' . $keyPath);
            return null;
        }
        $privateKey = file_get_contents($keyPath);
        return new Swift_Signers_DKIMSigner($privateKey, $domain, 'dressur');
    }

    // ─── Envoi individuel ─────────────────────────────────────────────────────

    private function sendEmail(string $to, string $subject, string $message, string $replyto, string $title, string $raison): bool
    {
        $maxAttempts = max(count($this->envMailSenderRepository->findBy(['activated' => true])), 1);

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            if (!$this->envMailSender) {
                $this->logger->error('Aucun sender SMTP disponible après ' . $attempt . ' tentative(s).');

                return false;
            }

            try {
                $transport = (new Swift_SmtpTransport(
                    $this->envMailSender->getSmtpServer(),
                    $this->envMailSender->getSmtpPort(),
                    $this->envMailSender->getSmtpSecured()
                ))
                    ->setUsername($this->envMailSender->getMailAdresse())
                    ->setPassword($this->envMailSender->getPassword());

                $mailer  = new Swift_Mailer($transport);
                $content = (new Swift_Message())
                    ->setSubject($subject)
                    ->setFrom([$this->envMailSender->getMailAdresse() => $title])
                    ->setReplyTo($replyto)
                    ->setTo($to)
                    ->setBody($message, 'text/html');

                $signer = $this->getDkimSigner($this->envMailSender->getMailAdresse());
                if ($signer) {
                    $content->attachSigner($signer);
                }

                if ($mailer->send($content)) {
                    // Round-robin : marquer comme utilisé (lastUsedAt = now) + compteur
                    $this->envMailSender->isUsed();
                    $this->logSend($raison, $this->envMailSender->getMailAdresse(), $to);
                    $this->em->flush();

                    // Rotation immédiate : le prochain appel smtpMail() dans la même
                    // requête utilisera le compte suivant dans la file round-robin.
                    $this->envMailSender = $this->getNextSenderRoundRobin();

                    return true;
                }

                return false;
            } catch (\Exception $e) {
                $msgError = (string) $e;
                if (strpos($msgError, 'hostinger_out_ratelimit') !== false) {
                    $this->deactivateCurrentSender('hostinger_out_ratelimit');
                } elseif ($this->isAuthError($msgError)) {
                    $this->deactivateCurrentSender('erreur d\'authentification SMTP');
                } else {
                    $this->logger->error('Erreur non récupérable lors de l\'envoi : ' . $e->getMessage());

                    return false;
                }

                $this->logger->error('Tentative ' . ($attempt + 1) . ' échouée — basculement vers le prochain sender (round-robin).');
                // Fallback : le prochain sender dans la rotation
                $this->envMailSender = $this->getNextSenderRoundRobin();
            }
        }

        $this->logger->error('Échec définitif : tous les senders SMTP ont été essayés.');

        return false;
    }

    // ─── Envoi multiple (BCC par lots de 20) ─────────────────────────────────

    private function sendEmailMultiple(array $to, string $subject, string $message, string $replyto, string $title, string $raison): bool
    {
        $maxAttempts = max(count($this->envMailSenderRepository->findBy(['activated' => true])), 1);
        $batches     = array_chunk($to, 20);

        foreach ($batches as $batch) {
            for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
                if (!$this->envMailSender) {
                    $this->logger->error('Aucun sender SMTP disponible pour ce lot après ' . $attempt . ' tentative(s).');
                    break;
                }

                try {
                    $transport = (new Swift_SmtpTransport(
                        $this->envMailSender->getSmtpServer(),
                        $this->envMailSender->getSmtpPort(),
                        $this->envMailSender->getSmtpSecured()
                    ))
                        ->setUsername($this->envMailSender->getMailAdresse())
                        ->setPassword($this->envMailSender->getPassword());

                    $mailer  = new Swift_Mailer($transport);
                    $content = (new Swift_Message())
                        ->setSubject($subject)
                        ->setFrom([$this->envMailSender->getMailAdresse() => $title])
                        ->setReplyTo($replyto)
                        ->setBcc($batch)
                        ->setBody($message, 'text/html');

                    $signer = $this->getDkimSigner($this->envMailSender->getMailAdresse());
                    if ($signer) {
                        $content->attachSigner($signer);
                    }

                    if ($mailer->send($content)) {
                        // Round-robin : marquer comme utilisé
                        $this->envMailSender->isUsed();
                        foreach ($batch as $recipient) {
                            $this->logSend($raison, $this->envMailSender->getMailAdresse(), $recipient);
                        }
                        $this->em->flush();
                    } else {
                        $this->logger->error('Échec de l\'envoi du lot d\'emails.');
                    }
                    break;
                } catch (\Exception $e) {
                    $msgError = (string) $e;
                    if (strpos($msgError, 'hostinger_out_ratelimit') !== false) {
                        $this->deactivateCurrentSender('hostinger_out_ratelimit');
                    } elseif ($this->isAuthError($msgError)) {
                        $this->deactivateCurrentSender('erreur d\'authentification SMTP');
                    } else {
                        $this->logger->error('Erreur non récupérable sur le lot : ' . $e->getMessage());
                        break;
                    }
                    $this->logger->error('Lot : tentative ' . ($attempt + 1) . ' échouée — basculement round-robin.');
                    $this->envMailSender = $this->getNextSenderRoundRobin();
                }
            }

            sleep(2);
            // Rotation : après chaque lot, passer au sender suivant
            $this->envMailSender = $this->getNextSenderRoundRobin();
        }

        return true;
    }

    // ─── Point d'entrée public ────────────────────────────────────────────────

    public function smtpMail(
        $to,
        string $subject,
        string $message,
        string $replyto  = 'dressur.ds@gmail.com',
        string $title    = 'Dressur Assistance',
        string $raison   = 'general'
    ): bool {
        if (is_array($to)) {
            return $this->sendEmailMultiple($to, $subject, $message, $replyto, $title, $raison);
        }

        return $this->sendEmail($to, $subject, $message, $replyto, $title, $raison);
    }

    public function sendReport(string $subject, string $message): bool
    {
        $to      = 'dressur.ds@gmail.com';
        $replyto = 'dressur.ds@gmail.com';
        $title   = 'Dressur Report : ' . time();

        return $this->sendEmail($to, $subject, $message, $replyto, $title, 'report');
    }
}
