<?php

namespace App\Entity;

use App\Repository\MethodePaiementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MethodePaiementRepository::class)]
class MethodePaiement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $aggregator = null;

    #[ORM\Column(length: 255)]
    private ?string $pays = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column]
    private ?bool $activated = null;

    #[ORM\Column]
    private ?bool $isdirect = null;

    #[ORM\Column(type: Types::ARRAY)]
    private array $requires = [];

    #[ORM\OneToOne(targetEntity: self::class, cascade: ['persist', 'remove'])]
    private ?self $autreMethodeUn = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAggregator(): ?string
    {
        return $this->aggregator;
    }

    public function setAggregator(string $aggregator): static
    {
        $this->aggregator = $aggregator;

        return $this;
    }

    public function getPays(): ?string
    {
        return $this->pays;
    }

    public function setPays(string $pays): static
    {
        $this->pays = $pays;

        return $this;
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

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

    public function isIsdirect(): ?bool
    {
        return $this->isdirect;
    }

    public function setIsdirect(bool $isdirect): static
    {
        $this->isdirect = $isdirect;

        return $this;
    }

    public function getRequires(): array
    {
        return $this->requires;
    }

    public function setRequires(array $requires): static
    {
        $this->requires = $requires;

        return $this;
    }

    public function getAutreMethodeUn(): ?self
    {
        return $this->autreMethodeUn;
    }

    public function setAutreMethodeUn(?self $autreMethodeUn): static
    {
        $this->autreMethodeUn = $autreMethodeUn;

        return $this;
    }
}
