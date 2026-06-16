<?php

namespace App\Entity;

use App\Repository\PreferenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PreferenceRepository::class)]
class Preference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\OneToOne(inversedBy: 'preference', targetEntity: User::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private $user;

    #[ORM\Column(nullable: true)]
    private array $paysChoisies = [];

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $addPageActu = true;

    public function __construct()
    {
        // $this->paysChoisies = [$this->user->getPays()];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getPaysChoisies(): array
    {
        return $this->paysChoisies;
    }

    public function setPaysChoisies(?array $paysChoisies): self
    {
        $this->paysChoisies = $paysChoisies;

        return $this;
    }

    public function getAddPageActu(): bool
    {
        return $this->addPageActu;
    }

    public function setAddPageActu(bool $addPageActu): self
    {
        $this->addPageActu = $addPageActu;

        return $this;
    }

}
