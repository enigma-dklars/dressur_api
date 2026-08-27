<?php

namespace App\Entity;

use App\Repository\UserUsedIdentityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserUsedIdentityRepository::class)]
#[ORM\Table(name: 'user_used_identity')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_USED_IDENTITY_TYPE_VALUE', columns: ['type', 'value'])]
class UserUsedIdentity
{
    public const TYPE_TEL = 'tel';
    public const TYPE_MAIL = 'mail';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 10)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $value = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $firstUsedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $lastUsedAt = null;

    public function __construct()
    {
        $now = new \DateTime();
        $this->firstUsedAt = $now;
        $this->lastUsedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): self
    {
        $this->value = $value;
        return $this;
    }

    public function getFirstUsedAt(): ?\DateTimeInterface
    {
        return $this->firstUsedAt;
    }

    public function setFirstUsedAt(\DateTimeInterface $firstUsedAt): self
    {
        $this->firstUsedAt = $firstUsedAt;
        return $this;
    }

    public function getLastUsedAt(): ?\DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(\DateTimeInterface $lastUsedAt): self
    {
        $this->lastUsedAt = $lastUsedAt;
        return $this;
    }
}
