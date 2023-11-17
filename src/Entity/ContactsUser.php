<?php

namespace App\Entity;

use App\Repository\ContactsUserRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactsUserRepository::class)]
class ContactsUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nameTel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $displayNameTel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numberTel = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNameTel(): ?string
    {
        return $this->nameTel;
    }

    public function setNameTel(?string $nameTel): self
    {
        $this->nameTel = $nameTel;

        return $this;
    }

    public function getDisplayNameTel(): ?string
    {
        return $this->displayNameTel;
    }

    public function setDisplayNameTel(?string $displayNameTel): self
    {
        $this->displayNameTel = $displayNameTel;

        return $this;
    }

    public function getNumberTel(): ?string
    {
        return $this->numberTel;
    }

    public function setNumberTel(?string $numberTel): self
    {
        $this->numberTel = $numberTel;

        return $this;
    }
}
