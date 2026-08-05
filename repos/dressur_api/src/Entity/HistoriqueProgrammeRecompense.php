<?php

namespace App\Entity;

use App\Repository\HistoriqueProgrammeRecompenseRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoriqueProgrammeRecompenseRepository::class)]
class HistoriqueProgrammeRecompense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Promotion $promotion = null;

    #[ORM\Column]
    private ?int $nbrVue = null;

    #[ORM\Column]
    private ?int $nbrPartage = null;

    #[ORM\Column]
    private ?int $recompense = null;

    #[ORM\Column(length: 50)]
    private ?string $status = null;

    #[ORM\Column(length: 255)]
    private ?string $referenceParticipation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiredAt = null;

    public function __construct()
    {
        $this->nbrVue = 0;
        $this->nbrPartage = 1;
        $this->recompense = 0;
        $this->referenceParticipation = $this->referenceParticipation();

        /**
         * en_cours
         * terminer
         * en_attente
         * approuver
         * echouer
         * refuser
         */
        $this->status = "en_cours";
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

        public function __toString(): string
    {
        // Ici tu choisis un champ représentatif
        return 'Historique #' . $this->getId();
        // ou si tu as un champ plus parlant, par exemple :
        // return $this->getReferenceParticipation();
    }

    public function referenceParticipation(): string
    {
        $unique = bin2hex(random_bytes(16));
        $unique = str_shuffle($unique);
        $unique = strtoupper($unique);
        return "DRESSUR_".$unique;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPromotion(): ?Promotion
    {
        return $this->promotion;
    }

    public function setPromotion(?Promotion $promotion): static
    {
        $this->promotion = $promotion;

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

    public function getNbrVue(): ?int
    {
        return $this->nbrVue;
    }

    public function setNbrVue(int $nbrVue): static
    {
        $this->nbrVue = $nbrVue;

        return $this;
    }

    public function getRecompense(): ?int
    {
        return $this->recompense;
    }

    public function setRecompense(int $recompense): static
    {
        $this->recompense = $recompense;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getReferenceParticipation(): ?string
    {
        return $this->referenceParticipation;
    }

    public function setReferenceParticipation(string $referenceParticipation): static
    {
        $this->referenceParticipation = $referenceParticipation;

        return $this;
    }

    public function getNbrPartage(): ?int
    {
        return $this->nbrPartage;
    }

    public function setNbrPartage(int $nbrPartage): static
    {
        $this->nbrPartage = $nbrPartage;

        return $this;
    }

    public function estPartager(): static
    {
        $this->nbrPartage++;

        return $this;
    }

    public function getExpiredAt(): ?\DateTimeInterface
    {
        return $this->expiredAt;
    }

    public function setExpiredAt(?\DateTimeInterface $expiredAt): static
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }
}
