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
    private ?FormulePromoAffaire $formulePromoAffaire = null;

    #[ORM\ManyToOne(inversedBy: 'promotions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $image = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateExp = null;

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\Column]
    private ?int $nombreDeVue = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mode = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombreImpression = null;

    #[ORM\Column]
    private ?bool $limited = null;

    #[ORM\Column(nullable: true)]
    private array $whoSaw = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(nullable: true)]
    private ?bool $isFakeVue = null;

    #[ORM\Column]
    private ?bool $referencement = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $annotherInfo = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typePromotionAffaire = null;

    #[ORM\Column]
    private ?bool $inProgrammeRecompense = null;

    #[ORM\Column]
    private ?bool $publishOnDressurStatus = null;

    public function __construct()
    {
        $this->status = 1;
        $this->referencement = false;
        $this->isFakeVue = false;
        $this->limited = true;
        $this->mode = "Gratuit";
        $this->nombreDeVue = 0;
        $this->nombreImpression = 0;
        $this->whoSaw = [];
        $this->typePromotionAffaire = "produit_service";
        $this->inProgrammeRecompense = false;
        $this->publishOnDressurStatus = false;
        /**
         * status values description
         * 0 : rejeter
         * 1 : en attente
         * 2 : accepter et en attente de paiement
         * 3 : accepter et en cours
         * 4 : terminer
         */

        /**
         * typePromotionAffaire description
         * produit_service
         * dmd_emploi
         * offre_emploi
         */
    }

    public function __toString(): string
    {
        return 'Promotion #' . $this->getId(); // sûr et toujours valide
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFormulePromoAffaire(): ?FormulePromoAffaire
    {
        return $this->formulePromoAffaire;
    }

    public function setFormulePromoAffaire(?FormulePromoAffaire $formulePromoAffaire): self
    {
        $this->formulePromoAffaire = $formulePromoAffaire;

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
        if($mode == "fakeVue") {
            $this->nombreImpression += rand(0, 20);
            $this->nombreDeVue += rand(0, 8);
        } else if($mode == "web") {
            $this->nombreImpression += rand(0, 6);
        } else if($mode == "all" || $mode == "vue") {
            if($user->getId() != $this->getUser()->getId()) {
                if (in_array($user->getId(), $this->whoSaw)) {
                    $this->nombreImpression += rand(0, 4);
                } else {
                    $this->nombreImpression += rand(0, 5);
                    array_push($this->whoSaw, $user->getId());
                }
                $this->nombreDeVue += rand(0, 1);
            }
        }
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

    public function getIsFakeVue(): ?bool
    {
        return $this->isFakeVue;
    }

    public function setIsFakeVue(?bool $isFakeVue): static
    {
        $this->isFakeVue = $isFakeVue;

        return $this;
    }

    public function getReferencement(): ?bool
    {
        return $this->referencement;
    }

    public function setReferencement(bool $referencement): static
    {
        $this->referencement = $referencement;

        return $this;
    }

    public function getAnnotherInfo(): ?array
    {
        return $this->annotherInfo;
    }

    public function setAnnotherInfo(?array $annotherInfo): static
    {
        $this->annotherInfo = $annotherInfo;

        return $this;
    }

    public function getTypePromotionAffaire(): ?string
    {
        return $this->typePromotionAffaire;
    }

    public function setTypePromotionAffaire(?string $typePromotionAffaire): static
    {
        $this->typePromotionAffaire = $typePromotionAffaire;

        return $this;
    }

    public function isInProgrammeRecompense(): ?bool
    {
        return $this->inProgrammeRecompense ?? false;
    }

    public function setInProgrammeRecompense(bool $inProgrammeRecompense): static
    {
        $this->inProgrammeRecompense = $inProgrammeRecompense;

        return $this;
    }

    public function isPublishOnDressurStatus(): ?bool
    {
        return $this->publishOnDressurStatus;
    }

    public function setPublishOnDressurStatus(bool $publishOnDressurStatus): static
    {
        $this->publishOnDressurStatus = $publishOnDressurStatus;

        return $this;
    }
}
