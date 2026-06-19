<?php

namespace App\Entity;

use DateTime;
use App\Entity\User;
use App\Entity\FormuleBoost;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BoostRepository;

#[ORM\Entity(repositoryClass: BoostRepository::class)]
class Boost
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\ManyToOne(targetEntity: FormuleBoost::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $formuleBoost;

    #[ORM\Column(type: 'datetime')]
    private $dateDebut;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $dateExp = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'boosts')]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[ORM\Column(type: 'string', length: 255)]
    private $mode;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(type: 'string', length: 10)]
    private string $typeBoost = 'date';

    #[ORM\Column(type: 'integer')]
    private int $nbContactsObtenus = 0;

    public function __construct()
    {
        $this->dateDebut = new DateTime();
        $this->mode = "Gratuit";

        /**
         * modeNumber
         * 1 : gratuit
         * 2 : payant
         * 3 : kdo
         */

        /**
         * statusNumber
         * 1 : en cours
         * 2 : programmer
         * 3 : terminer
         */
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFormuleBoost(): ?FormuleBoost
    {
        return $this->formuleBoost;
    }

    public function setFormuleBoost(?FormuleBoost $formuleBoost): self
    {
        $this->formuleBoost = $formuleBoost;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): self
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): self
    {
        $this->source = $source;

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

    public function getNbContactsObtenus(): int
    {
        return $this->nbContactsObtenus;
    }

    public function setNbContactsObtenus(int $nbContactsObtenus): self
    {
        $this->nbContactsObtenus = $nbContactsObtenus;
        return $this;
    }
}
