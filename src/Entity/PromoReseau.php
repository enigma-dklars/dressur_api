<?php

namespace App\Entity;

use App\Repository\PromoReseauRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PromoReseauRepository::class)]
class PromoReseau
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'promoReseaus')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?FormulePromoReseau $formulePromoReseau = null;

    #[ORM\Column]
    private ?int $qteDemander = null;

    #[ORM\Column]
    private ?int $prixFixer = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $url = null;

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idZefame = null;

    #[ORM\Column(nullable: true)]
    private ?int $compteurDebut = null;

    #[ORM\Column(nullable: true)]
    private ?int $compteurRestant = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?float $prixZefame = null;

    public function __construct()
    {
        $this->status = 1;
        $this->idZefame = "*****";
        $this->compteurDebut = 0;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        /**
         * status values description
         * 0 : remboursée
         * 1 : en attente
         * 2 : en cours
         * 3 : terminer
         */
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getFormulePromoReseau(): ?FormulePromoReseau
    {
        return $this->formulePromoReseau;
    }

    public function setFormulePromoReseau(?FormulePromoReseau $formulePromoReseau): self
    {
        $this->formulePromoReseau = $formulePromoReseau;

        return $this;
    }

    public function getQteDemander(): ?int
    {
        return $this->qteDemander;
    }

    public function setQteDemander(int $qteDemander): self
    {
        $this->qteDemander = $qteDemander;
        $this->compteurRestant = $qteDemander;

        return $this;
    }

    public function getPrixFixer(): ?int
    {
        return $this->prixFixer;
    }

    public function setPrixFixer(int $prixFixer): self
    {
        $this->prixFixer = $prixFixer;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getIdZefame(): ?string
    {
        return $this->idZefame;
    }

    public function setIdZefame(?string $idZefame): self
    {
        $this->idZefame = $idZefame;

        return $this;
    }

    public function getCompteurDebut(): ?int
    {
        return $this->compteurDebut;
    }

    public function setCompteurDebut(?int $compteurDebut): self
    {
        $this->compteurDebut = $compteurDebut;

        return $this;
    }

    public function getCompteurRestant(): ?int
    {
        return $this->compteurRestant;
    }

    public function setCompteurRestant(?int $compteurRestant): self
    {
        $this->compteurRestant = $compteurRestant;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getPrixZefame(): ?float
    {
        return $this->prixZefame;
    }

    public function setPrixZefame(?float $prixZefame): static
    {
        $this->prixZefame = $prixZefame;

        return $this;
    }
}
