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
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeveloperProfile $developerProfile = null;

    #[ORM\Column(length: 160)]
    private string $idempotencyKey;

    #[ORM\Column(length: 64)]
    private string $requestHash;

    #[ORM\Column(type: Types::JSON)]
    private array $responseBody = [];

    #[ORM\Column(type: Types::INTEGER)]
    private int $responseStatus = 200;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
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
