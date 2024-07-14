<?php

namespace App\Entity;

use App\Repository\FormuleDressurBotRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormuleDressurBotRepository::class)]
class FormuleDressurBot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column]
    private ?int $prix = null;

    #[ORM\Column]
    private ?int $nbrJour = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getPrix(): ?int
    {
        return $this->prix;
    }

    public function setPrix(int $prix): static
    {
        $this->prix = $prix;

        return $this;
    }

    public function getNbrJour(): ?int
    {
        return $this->nbrJour;
    }

    public function setNbrJour(int $nbrJour): static
    {
        $this->nbrJour = $nbrJour;

        return $this;
    }
}
