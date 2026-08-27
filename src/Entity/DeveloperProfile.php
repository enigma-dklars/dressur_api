<?php

namespace App\Entity;

use App\Repository\DeveloperProfileRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeveloperProfileRepository::class)]
#[ORM\Table(name: 'developer_profile')]
class DeveloperProfile
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'developerProfile', targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, unique: true, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 20, options: ['default' => 'pending'])]
    private string $status = 'pending';

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $conditionsVersion = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $conditionsAcceptedAt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $activationTransactionReference = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $activationAmount = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $activatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $suspendedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $revokedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $updatedAt;

    public function __construct()
    {
        $now = new DateTime();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function getConditionsVersion(): ?string
    {
        return $this->conditionsVersion;
    }

    public function setConditionsVersion(?string $conditionsVersion): static
    {
        $this->conditionsVersion = $conditionsVersion;
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function getConditionsAcceptedAt(): ?DateTimeInterface
    {
        return $this->conditionsAcceptedAt;
    }

    public function setConditionsAcceptedAt(?DateTimeInterface $conditionsAcceptedAt): static
    {
        $this->conditionsAcceptedAt = $conditionsAcceptedAt;
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function getActivationTransactionReference(): ?string
    {
        return $this->activationTransactionReference;
    }

    public function setActivationTransactionReference(?string $activationTransactionReference): static
    {
        $this->activationTransactionReference = $activationTransactionReference;
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function getActivationAmount(): ?int
    {
        return $this->activationAmount;
    }

    public function setActivationAmount(?int $activationAmount): static
    {
        $this->activationAmount = $activationAmount;
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function getActivatedAt(): ?DateTimeInterface
    {
        return $this->activatedAt;
    }

    public function setActivatedAt(?DateTimeInterface $activatedAt): static
    {
        $this->activatedAt = $activatedAt;
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function getSuspendedAt(): ?DateTimeInterface
    {
        return $this->suspendedAt;
    }

    public function setSuspendedAt(?DateTimeInterface $suspendedAt): static
    {
        $this->suspendedAt = $suspendedAt;
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function getRevokedAt(): ?DateTimeInterface
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?DateTimeInterface $revokedAt): static
    {
        $this->revokedAt = $revokedAt;
        $this->updatedAt = new DateTime();

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTimeInterface
    {
        return $this->updatedAt;
    }
}
