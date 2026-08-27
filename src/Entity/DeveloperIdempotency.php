<?php

namespace App\Entity;

use App\Repository\DeveloperIdempotencyRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeveloperIdempotencyRepository::class)]
#[ORM\Table(name: 'developer_idempotency')]
#[ORM\UniqueConstraint(name: 'UNIQ_DEVELOPER_IDEMPOTENCY_PROFILE_KEY', columns: ['developer_profile_id', 'idempotency_key'])]
class DeveloperIdempotency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DeveloperProfile::class)]
    #[ORM\JoinColumn(name: 'developer_profile_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?DeveloperProfile $developerProfile = null;

    #[ORM\Column(name: 'idempotency_key', length: 160)]
    private string $idempotencyKey;

    #[ORM\Column(name: 'request_hash', length: 64)]
    private string $requestHash;

    #[ORM\Column(name: 'response_body', type: Types::JSON)]
    private array $responseBody = [];

    #[ORM\Column(name: 'order_reference', length: 40, nullable: true)]
    private ?string $orderReference = null;

    #[ORM\Column(name: 'balance_after', type: Types::INTEGER, nullable: true)]
    private ?int $balanceAfter = null;

    #[ORM\Column(name: 'response_status', type: Types::INTEGER)]
    private int $responseStatus = 200;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $createdAt;

    #[ORM\Column(name: 'expires_at', type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $expiresAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->expiresAt = new DateTime('+24 hours');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDeveloperProfile(): ?DeveloperProfile
    {
        return $this->developerProfile;
    }

    public function setDeveloperProfile(DeveloperProfile $developerProfile): static
    {
        $this->developerProfile = $developerProfile;

        return $this;
    }

    public function getIdempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function setIdempotencyKey(string $idempotencyKey): static
    {
        $this->idempotencyKey = $idempotencyKey;

        return $this;
    }

    public function getRequestHash(): string
    {
        return $this->requestHash;
    }

    public function setRequestHash(string $requestHash): static
    {
        $this->requestHash = $requestHash;

        return $this;
    }

    public function getResponseBody(): array
    {
        return $this->responseBody;
    }

    public function setResponseBody(array $responseBody): static
    {
        $this->responseBody = $responseBody;

        return $this;
    }

    public function getOrderReference(): ?string
    {
        return $this->orderReference;
    }

    public function setOrderReference(?string $orderReference): static
    {
        $this->orderReference = $orderReference;

        return $this;
    }

    public function getBalanceAfter(): ?int
    {
        return $this->balanceAfter;
    }

    public function setBalanceAfter(?int $balanceAfter): static
    {
        $this->balanceAfter = $balanceAfter;

        return $this;
    }

    public function getResponseStatus(): int
    {
        return $this->responseStatus;
    }

    public function setResponseStatus(int $responseStatus): static
    {
        $this->responseStatus = $responseStatus;

        return $this;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt <= new DateTime();
    }
}
