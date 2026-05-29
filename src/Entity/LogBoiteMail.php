<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\LogBoiteMailRepository;

#[ORM\Entity(repositoryClass: LogBoiteMailRepository::class)]
class LogBoiteMail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $raison;

    #[ORM\Column(length: 255)]
    private string $emailSender;

    #[ORM\Column(length: 255)]
    private string $emailRecepteur;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $datEnvoi;

    public function __construct(string $raison, string $emailSender, string $emailRecepteur)
    {
        $this->raison         = $raison;
        $this->emailSender    = $emailSender;
        $this->emailRecepteur = $emailRecepteur;
        $this->datEnvoi       = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRaison(): string
    {
        return $this->raison;
    }

    public function setRaison(string $raison): static
    {
        $this->raison = $raison;

        return $this;
    }

    public function getEmailSender(): string
    {
        return $this->emailSender;
    }

    public function setEmailSender(string $emailSender): static
    {
        $this->emailSender = $emailSender;

        return $this;
    }

    public function getEmailRecepteur(): string
    {
        return $this->emailRecepteur;
    }

    public function setEmailRecepteur(string $emailRecepteur): static
    {
        $this->emailRecepteur = $emailRecepteur;

        return $this;
    }

    public function getDatEnvoi(): \DateTimeInterface
    {
        return $this->datEnvoi;
    }

    public function setDatEnvoi(\DateTimeInterface $datEnvoi): static
    {
        $this->datEnvoi = $datEnvoi;

        return $this;
    }
}
