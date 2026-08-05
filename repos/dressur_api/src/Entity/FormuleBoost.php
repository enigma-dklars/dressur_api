<?php

namespace App\Entity;

use App\Repository\FormuleBoostRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormuleBoostRepository::class)]
class FormuleBoost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $titre;

    #[ORM\Column(type: 'float')]
    private $prix;

    #[ORM\Column(type: 'integer')]
    private $nbrJour;

    #[ORM\Column(type: 'boolean')]
    private $alert;

    #[ORM\Column]
    private ?bool $activated = null;

    #[ORM\Column(type: 'string', length: 10)]
    private string $typeBoost = 'date';

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $nbContactsMax = null;

    public function __construct()
    {
        $this->alert = false;
    }

    public function __toString()
    {
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

    public function getPrix(): ?float
    {
        return $this->prix;
    }

    public function setPrix(float $prix): self
    {
        $this->prix = $prix;

        return $this;
    }

    public function getNbrJour(): ?int
    {
        return $this->nbrJour;
    }

    public function setNbrJour(int $nbrJour): self
    {
        $this->nbrJour = $nbrJour;

        return $this;
    }

    public function isAlert(): ?bool
    {
        return $this->alert;
    }

    public function setAlert(bool $alert): self
    {
        $this->alert = $alert;

        return $this;
    }

    public function isActivated(): ?bool
    {
        return $this->activated;
    }

    public function setActivated(bool $activated): static
    {
        $this->activated = $activated;

        return $this;
    }

    public function getTypeBoost(): string
    {
        return $this->typeBoost;
    }

    public function setTypeBoost(string $typeBoost): self
    {
        $this->typeBoost = $typeBoost;
        return $this;
    }

    public function getNbContactsMax(): ?int
    {
        return $this->nbContactsMax;
    }

    public function setNbContactsMax(?int $nbContactsMax): self
    {
        $this->nbContactsMax = $nbContactsMax;
        return $this;
    }
}
