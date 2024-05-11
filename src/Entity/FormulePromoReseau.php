<?php

namespace App\Entity;

use App\Repository\FormulePromoReseauRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormulePromoReseauRepository::class)]
class FormulePromoReseau
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $iconFlutterName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?int $qteMin = null;

    #[ORM\Column(nullable: true)]
    private ?int $qteMax = null;

    #[ORM\Column(nullable: true)]
    private ?int $prixQteMin = null;

    #[ORM\Column]
    private ?bool $available = null;

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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

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

    public function getPrixQteMin(): ?int
    {
        return $this->prixQteMin;
    }

    public function setPrixQteMin(?int $prixQteMin): self
    {
        $this->prixQteMin = $prixQteMin;

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
}
