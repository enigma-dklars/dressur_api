<?php

namespace App\Entity;

use App\Repository\PromotionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PromotionRepository::class)]
class Promotion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    private ?FormuleBoost $formule_boost = null;

    #[ORM\ManyToOne(inversedBy: 'promotions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $image = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateExp = null;

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\Column]
    private ?int $nombreDeVue = null;

    #[ORM\Column(length: 255)]
    private ?string $mode = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombreImpression = null;

    #[ORM\Column]
    private ?bool $limited = null;

    #[ORM\Column(nullable: true)]
    private array $whoSaw = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    public function __construct()
    {
        $this->status = 1;
        $this->limited = true;
        $this->mode = "Gratuit";
        $this->nombreDeVue = 0;
        $this->nombreImpression = 0;
        $this->whoSaw = [];
        /**
         * status values description
         * 0 : rejeter
         * 1 : en attente
         * 2 : accepter et en attente de paiement
         * 3 : accepter et en cours
         * 4 : terminer
         */
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFormuleBoost(): ?FormuleBoost
    {
        return $this->formule_boost;
    }

    public function setFormuleBoost(?FormuleBoost $formule_boost): self
    {
        $this->formule_boost = $formule_boost;

        return $this;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getImage(): ?string
    {
        return $this->image;
    }

    public function setImage(string $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(?\DateTimeInterface $dateDebut): self
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateExp(): ?\DateTimeInterface
    {
        return $this->dateExp;
    }

    public function setDateExp(?\DateTimeInterface $dateExp): self
    {
        $this->dateExp = $dateExp;

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

    public function getNombreDeVue(): ?int
    {
        return $this->nombreDeVue;
    }

    public function setNombreDeVue(int $nombreDeVue): self
    {
        $this->nombreDeVue = $nombreDeVue;

        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setToWatch($user, $mode): self
    {
        if (in_array($user->getId(), $this->whoSaw)) {
            $this->nombreImpression += rand(0, 1);
        } else {
            $this->nombreImpression += rand(1, 2);
            array_push($this->whoSaw, $user->getId());
        }        
        $this->nombreDeVue += rand(0, 1);
        return $this;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getNombreImpression(): ?int
    {
        return $this->nombreImpression;
    }

    public function setNombreImpression(?int $nombreImpression): self
    {
        $this->nombreImpression = $nombreImpression;

        return $this;
    }

    public function isLimited(): ?bool
    {
        return $this->limited;
    }

    public function setLimited(bool $limited): self
    {
        $this->limited = $limited;

        return $this;
    }

    public function getWhoSaw(): array
    {
        return $this->whoSaw;
    }

    public function setWhoSaw(?array $whoSaw): self
    {
        $this->whoSaw = $whoSaw;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): self
    {
        $this->motif = $motif;

        return $this;
    }
}
