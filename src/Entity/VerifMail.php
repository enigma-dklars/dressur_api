<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\VerifMailRepository;

#[ORM\Entity(repositoryClass: VerifMailRepository::class)]
class VerifMail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $code;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private $user;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $dateExp;

    public function __construct()
    {
        $this->dateExp = new DateTime("+5 minutes");
        $this->code = $this->code();
    }

    public function code(int $length = 6): ?string
    {
        // allowed characters
        $chars = "0123456789";
        // make sure we have enough length
        while (strlen($chars) < $length) {
            $chars .= $chars;
        }
        return substr(str_shuffle($chars), 0, $length);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

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

    public function getDateExp(): ?\DateTimeInterface
    {
        return $this->dateExp;
    }

    public function setDateExp(?\DateTimeInterface $dateExp): self
    {
        $this->dateExp = $dateExp;

        return $this;
    }
}
