<?php

namespace App\Entity;

use App\Repository\EnvMailSenderRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnvMailSenderRepository::class)]
class EnvMailSender
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $mailAdresse = null;

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    private ?string $smtpServer = null;

    #[ORM\Column(length: 255)]
    private ?string $smtpPort = null;

    #[ORM\Column(length: 255)]
    private ?string $smtpSecured = null;

    #[ORM\Column]
    private ?bool $activated = null;

    #[ORM\Column]
    private ?int $countMailSent = null;

    public function __construct()
    {
        $this->mailAdresse = "noreply1@dressur.site";
        $this->password = "nunewqi_DS3";
        $this->smtpServer = "smtp.hostinger.com";
        $this->smtpPort = "465";
        $this->smtpSecured = "ssl";
        $this->activated = true;
        $this->countMailSent = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMailAdresse(): ?string
    {
        return $this->mailAdresse;
    }

    public function setMailAdresse(string $mailAdresse): static
    {
        $this->mailAdresse = $mailAdresse;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getSmtpServer(): ?string
    {
        return $this->smtpServer;
    }

    public function setSmtpServer(string $smtpServer): static
    {
        $this->smtpServer = $smtpServer;

        return $this;
    }

    public function getSmtpPort(): ?string
    {
        return $this->smtpPort;
    }

    public function setSmtpPort(string $smtpPort): static
    {
        $this->smtpPort = $smtpPort;

        return $this;
    }

    public function getSmtpSecured(): ?string
    {
        return $this->smtpSecured;
    }

    public function setSmtpSecured(string $smtpSecured): static
    {
        $this->smtpSecured = $smtpSecured;

        return $this;
    }

    public function isActivated(): ?bool
    {
        return $this->activated;
    }

    public function setActivated(bool $activated): static
    {
        $this->activated = $activated;

        return $this;
    }

    public function getCountMailSent(): ?int
    {
        return $this->countMailSent;
    }

    public function setCountMailSent(int $countMailSent): static
    {
        $this->countMailSent = $countMailSent;

        return $this;
    }

    public function isUsed(): static
    {
        $this->countMailSent++;

        return $this;
    }
}
