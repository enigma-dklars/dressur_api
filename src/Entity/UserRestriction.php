<?php

namespace App\Entity;

use App\Repository\UserRestrictionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRestrictionRepository::class)]
#[ORM\Table(name: 'user_restriction')]
#[ORM\UniqueConstraint(name: 'UNIQ_USER_RESTRICTION_USER_TYPE', columns: ['user_id', 'type'])]

class UserRestriction
{
    public const TYPE_BLOCK_FREE_BOOST = 'block_free_boost';
    public const TYPE_MINIMUM_TRANSACTION = 'minimum_transaction_amount';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $identityTel = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $identityMail = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $minimumTransactionAmount = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    public function __construct()
    {
        $now = new \DateTime();
        $this->active = true;
        $this->createdAt = $now;
        $this->updatedAt = clone $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getIdentityTel(): ?string
    {
        return $this->identityTel;
    }

    public function setIdentityTel(?string $identityTel): static
    {
        $this->identityTel = $identityTel;
        return $this;
    }

    public function getIdentityMail(): ?string
    {
        return $this->identityMail;
    }

    public function setIdentityMail(?string $identityMail): static
    {
        $this->identityMail = $identityMail;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getMinimumTransactionAmount(): ?int
    {
        return $this->minimumTransactionAmount;
    }

    public function setMinimumTransactionAmount(?int $amount): static
    {
        $this->minimumTransactionAmount = $amount;
        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function isCurrentlyActive(): bool
    {
        return $this->active && ($this->expiresAt === null || $this->expiresAt > new \DateTime());
    }
}
