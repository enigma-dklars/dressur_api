<?php

namespace App\Entity;

use App\Repository\UserBotRepository;
use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserBotRepository::class)]
class UserBot
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    private ?string $numero = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adresseMac = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $uuidMachine = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $diskSerialNumber = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $expiratedAt = null;

    #[ORM\Column(length: 255)]
    private ?string $typeMachine = null;

    #[ORM\Column(length: 4)]
    private ?string $signature = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nbrMsgSent = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->nbrMsgSent = "0";
        $this->typeMachine = "pc";
        $this->signature = "oui";
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->expiratedAt = new DateTime("-5 minutes");
    }

    public function __toString()
    {
        return $this->nom;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getNumero(): ?string
    {
        return $this->numero;
    }

    public function setNumero(string $numero): static
    {
        $this->numero = $numero;

        return $this;
    }

    public function getAdresseMac(): ?string
    {
        return $this->adresseMac;
    }

    public function setAdresseMac(string $adresseMac): static
    {
        $this->adresseMac = $adresseMac;

        return $this;
    }

    public function getUuidMachine(): ?string
    {
        return $this->uuidMachine;
    }

    public function setUuidMachine(string $uuidMachine): static
    {
        $this->uuidMachine = $uuidMachine;

        return $this;
    }

    public function getDiskSerialNumber(): ?string
    {
        return $this->diskSerialNumber;
    }

    public function setDiskSerialNumber(string $diskSerialNumber): static
    {
        $this->diskSerialNumber = $diskSerialNumber;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getExpiratedAt(): ?\DateTimeInterface
    {
        return $this->expiratedAt;
    }

    public function setExpiratedAt(\DateTimeInterface $expiratedAt): static
    {
        $this->expiratedAt = $expiratedAt;

        return $this;
    }

    public function getTypeMachine(): ?string
    {
        return $this->typeMachine;
    }

    public function setTypeMachine(string $typeMachine): static
    {
        $this->typeMachine = $typeMachine;

        return $this;
    }

    public function getSignature(): ?string
    {
        return $this->signature;
    }

    public function setSignature(string $signature): static
    {
        $this->signature = $signature;

        return $this;
    }

    public function getNbrMsgSent(): ?string
    {
        return $this->nbrMsgSent;
    }

    public function setNbrMsgSent(?string $nbrMsgSent): static
    {
        $this->nbrMsgSent = $nbrMsgSent;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function isUpdated(): static
    {
        $this->updatedAt = new DateTime();

        return $this;
    }
}
