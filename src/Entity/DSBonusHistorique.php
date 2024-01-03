<?php

namespace App\Entity;

use DateTime;
use App\Entity\User;
use App\Entity\DSBonus;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\DSBonusHistoriqueRepository;

#[ORM\Entity(repositoryClass: DSBonusHistoriqueRepository::class)]
class DSBonusHistorique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $user;

    #[ORM\ManyToOne(targetEntity: DSBonus::class)]
    #[ORM\JoinColumn(nullable: true)]
    private $wpbonus;

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $titre;

    #[ORM\Column(type: 'float', nullable: true)]
    private $montant;

    public function __construct()
    {
        $this->createdAt = new DateTime();
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

    public function getWpbonus(): ?DSBonus
    {
        return $this->wpbonus;
    }

    public function setWpbonus(?DSBonus $wpbonus): self
    {
        $this->wpbonus = $wpbonus;

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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(?string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getMontant(): ?float
    {
        return $this->montant;
    }

    public function setMontant(?float $montant): self
    {
        $this->montant = $montant;

        return $this;
    }
}
