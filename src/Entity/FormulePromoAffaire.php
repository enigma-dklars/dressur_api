<?php

namespace App\Entity;

use App\Repository\FormulePromoAffaireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormulePromoAffaireRepository::class)]
class FormulePromoAffaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private $titre;

    #[ORM\Column(type: 'float')]
    private $prix;

    #[ORM\Column(type: 'integer')]
    private $nbrJour;

    #[ORM\Column(type: 'boolean')]
    private $alert;

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
}
