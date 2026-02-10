<?php

namespace App\Entity;

use App\Repository\PreuveRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PreuveRepository::class)]
class Preuve
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $captureListeStatut = null;

    #[ORM\Column(length: 255)]
    private ?string $captureStatutOuvert = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?HistoriqueProgrammeRecompense $historiqueProgrammeRecompense = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCaptureListeStatut(): ?string
    {
        return $this->captureListeStatut;
    }

    public function setCaptureListeStatut(string $captureListeStatut): static
    {
        $this->captureListeStatut = $captureListeStatut;

        return $this;
    }

    public function getCaptureStatutOuvert(): ?string
    {
        return $this->captureStatutOuvert;
    }

    public function setCaptureStatutOuvert(string $captureStatutOuvert): static
    {
        $this->captureStatutOuvert = $captureStatutOuvert;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getHistoriqueProgrammeRecompense(): ?HistoriqueProgrammeRecompense
    {
        return $this->historiqueProgrammeRecompense;
    }

    public function setHistoriqueProgrammeRecompense(?HistoriqueProgrammeRecompense $historiqueProgrammeRecompense): static
    {
        $this->historiqueProgrammeRecompense = $historiqueProgrammeRecompense;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
