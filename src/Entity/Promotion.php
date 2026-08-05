<?php

namespace App\Entity;

use App\Repository\PromotionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?bool $boostFacebook = null;

    #[ORM\Column(nullable: true)]
    private ?int $montantBoostFacebook = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $whatsappContact = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $nomSiteApp = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $urlSiteApp = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $sousTypeSiteApp = null;

    #[ORM\OneToMany(mappedBy: 'promotion', targetEntity: PromotionMotifRefus::class, cascade: ['persist'], orphanRemoval: false)]
    private Collection $motifsRefus;

    public function __construct()
    {
        $this->motifsRefus = new ArrayCollection();
        $this->status = 1;
        $this->referencement = false;
        $this->isFakeVue = false;
        $this->limited = true;
        $this->mode = "Gratuit";
        $this->createdAt = new \DateTime();
        $this->nombreDeVue = 0;
        $this->nombreImpression = 0;
        $this->whoSaw = [];
        $this->typePromotionAffaire = "produit_service";
        $this->inProgrammeRecompense = false;
        $this->publishOnDressurStatus = false;
        $this->boostFacebook = false;
        $this->montantBoostFacebook = 0;
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

    /**
     * Coefficient d'attractivité stable par promo (entre 4% et 25%).
     * Dérivé de l'ID — ne change jamais pour une promo donnée.
     * Simule le fait que certaines promos engagent naturellement plus que d'autres.
     */
    private function getAttractiviteCoeff(): int
    {
        $id = $this->id ?? 1;
        return (($id * 31 + 17) % 22) + 4;
    }

    /**
     * Multiplicateur de portée stable par promo (1, 2 ou 3).
     * Dérivé de l'ID — certaines promos touchent naturellement plus de monde.
     */
    private function getReachMultiplier(): int
    {
        $id = $this->id ?? 1;
        return (($id * 13 + 7) % 3) + 1;
    }

    public function setToWatch($user, $mode): self
    {
        $ctr   = $this->getAttractiviteCoeff(); // 4–25 %, propre à chaque promo
        $reach = $this->getReachMultiplier();   // 1, 2 ou 3, propre à chaque promo

        if ($mode == "fakeVue") {
            // Boost payant : grande portée, CTR doublé plafonné à 30 %
            $this->nombreImpression += rand(20, 50) * $reach;
            if (rand(1, 100) <= min($ctr * 2, 30)) {
                $this->nombreDeVue += rand(1, 3);
            }
        } elseif ($mode == "web") {
            $this->nombreImpression += rand(2, 8) * $reach;
        } elseif ($mode == "all" || $mode == "vue") {
            if ($user->getId() != $this->getUser()->getId()) {
                if (in_array($user->getId(), $this->whoSaw)) {
                    // Utilisateur déjà vu : petite impression de rappel seulement
                    $this->nombreImpression += rand(1, 5) * $reach;
                } else {
                    // Nouveau spectateur : impression + marquage whoSaw
                    $this->nombreImpression += rand(6, 15) * $reach;
                    array_push($this->whoSaw, $user->getId());
                    // Vue : probabilité propre à cette promo (jamais la même pour toutes)
                    if (rand(1, 100) <= $ctr) {
                        $this->nombreDeVue += 1;
                    }
                }
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

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isBoostFacebook(): ?bool
    {
        return $this->boostFacebook ?? false;
    }

    public function setBoostFacebook(bool $boostFacebook): static
    {
        $this->boostFacebook = $boostFacebook;

        return $this;
    }

    public function getMontantBoostFacebook(): ?int
    {
        return $this->montantBoostFacebook;
    }

    public function setMontantBoostFacebook(?int $montantBoostFacebook): static
    {
        $this->montantBoostFacebook = $montantBoostFacebook;

        return $this;
    }

    public function getWhatsappContact(): ?string
    {
        return $this->whatsappContact;
    }

    public function setWhatsappContact(?string $whatsappContact): static
    {
        $this->whatsappContact = $whatsappContact;

        return $this;
    }

    public function getNomSiteApp(): ?string
    {
        return $this->nomSiteApp;
    }

    public function setNomSiteApp(?string $nomSiteApp): static
    {
        $this->nomSiteApp = $nomSiteApp;

        return $this;
    }

    public function getUrlSiteApp(): ?string
    {
        return $this->urlSiteApp;
    }

    public function setUrlSiteApp(?string $urlSiteApp): static
    {
        $this->urlSiteApp = $urlSiteApp;

        return $this;
    }

    public function getSousTypeSiteApp(): ?string
    {
        return $this->sousTypeSiteApp;
    }

    public function setSousTypeSiteApp(?string $sousTypeSiteApp): static
    {
        $this->sousTypeSiteApp = $sousTypeSiteApp;

        return $this;
    }

    /**
     * @return Collection<int, PromotionMotifRefus>
     */
    public function getMotifsRefus(): Collection
    {
        return $this->motifsRefus;
    }

    public function addMotifRefus(PromotionMotifRefus $motifRefus): static
    {
        if (!$this->motifsRefus->contains($motifRefus)) {
            $this->motifsRefus->add($motifRefus);
            $motifRefus->setPromotion($this);
        }

        return $this;
    }

    public function removeMotifRefus(PromotionMotifRefus $motifRefus): static
    {
        if ($this->motifsRefus->removeElement($motifRefus)) {
            if ($motifRefus->getPromotion() === $this) {
                $motifRefus->setPromotion(null);
            }
        }

        return $this;
    }
}
