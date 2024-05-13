<?php

namespace App\Entity;

use App\Repository\FormulePromoReseauRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormulePromoReseauRepository::class)]
class FormulePromoReseau
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'sonFormulePromoReseaus')]
    private ?self $parent = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $titre = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $iconFlutterName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $descriptionEn = null;

    #[ORM\Column(nullable: true)]
    private ?float $prix = null;

    #[ORM\Column(nullable: true)]
    private ?int $qte = null;

    #[ORM\Column(nullable: true)]
    private ?int $qteMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $qteMax = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $sonFormulePromoReseaus;

    #[ORM\Column]
    private ?bool $available = null;

    public function __construct()
    {
        $this->sonFormulePromoReseaus = new ArrayCollection();
    }

    public function __toString()
    {
        if($this->parent) {
            return $this->parent->getTitre()." ".$this->titre;
        }
        return $this->titre;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getIconFlutterName(): ?string
    {
        return $this->iconFlutterName;
    }

    public function setIconFlutterName(?string $iconFlutterName): self
    {
        $this->iconFlutterName = $iconFlutterName;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getQteMin(): ?int
    {
        return $this->qteMin;
    }

    public function setQteMin(?int $qteMin): self
    {
        $this->qteMin = $qteMin;

        return $this;
    }

    public function getQteMax(): ?int
    {
        return $this->qteMax;
    }

    public function setQteMax(?int $qteMax): self
    {
        $this->qteMax = $qteMax;

        return $this;
    }

    public function isAvailable(): ?bool
    {
        return $this->available;
    }

    public function setAvailable(bool $available): self
    {
        $this->available = $available;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getSonFormulePromoReseaus(): Collection
    {
        return $this->sonFormulePromoReseaus;
    }

    public function addSonFormulePromoReseau(self $sonFormulePromoReseau): self
    {
        if (!$this->sonFormulePromoReseaus->contains($sonFormulePromoReseau)) {
            $this->sonFormulePromoReseaus->add($sonFormulePromoReseau);
            $sonFormulePromoReseau->setParent($this);
        }

        return $this;
    }

    public function removeSonFormulePromoReseau(self $sonFormulePromoReseau): self
    {
        if ($this->sonFormulePromoReseaus->removeElement($sonFormulePromoReseau)) {
            // set the owning side to null (unless already changed)
            if ($sonFormulePromoReseau->getParent() === $this) {
                $sonFormulePromoReseau->setParent(null);
            }
        }

        return $this;
    }

    public function getDescriptionEn(): ?string
    {
        return $this->descriptionEn;
    }

    public function setDescriptionEn(?string $descriptionEn): self
    {
        $this->descriptionEn = $descriptionEn;

        return $this;
    }

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(?float $prix): self
    {
        $this->prix = $prix;

        return $this;
    }

    public function getQte(): ?int
    {
        return $this->qte;
    }

    public function setQte(?int $qte): self
    {
        $this->qte = $qte;

        return $this;
    }
}
