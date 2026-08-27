<?php

namespace App\Entity;

use App\Repository\DeveloperApiKeyRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeveloperApiKeyRepository::class)]
#[ORM\Table(name: 'developer_api_key')]
class DeveloperApiKey
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DeveloperProfile::class, inversedBy: 'apiKeys')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeveloperProfile $developerProfile = null;

    #[ORM\Column(length: 80, unique: true)]
    private string $keyId;

    #[ORM\Column(length: 255)]
    private string $secretHash;

    #[ORM\Column(length: 24)]
    private string $secretPrefix;

    #[ORM\Column(length: 120)]
    private string $label;

    #[ORM\Column(type: Types::JSON)]
    private array $scopes = [];

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $lastUsedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $expiresAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?DateTimeInterface $revokedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
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

    public function getKeyId(): string
    {
        return $this->keyId;
    }

    public function setKeyId(string $keyId): static
    {
        $this->keyId = $keyId;

        return $this;
    }

    public function getSecretHash(): string
    {
        return $this->secretHash;
    }

    public function setSecretHash(string $secretHash): static
    {
        $this->secretHash = $secretHash;

        return $this;
    }

    public function getSecretPrefix(): string
    {
        return $this->secretPrefix;
    }

    public function setSecretPrefix(string $secretPrefix): static
    {
        $this->secretPrefix = $secretPrefix;

        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return list<string>
     */
    public function getScopes(): array
    {
        return array_values($this->scopes);
    }

    /**
     * @param list<string> $scopes
     */
    public function setScopes(array $scopes): static
    {
        $this->scopes = array_values(array_unique($scopes));

        return $this;
    }

    public function hasScope(string $scope): bool
    {
        return in_array($scope, $this->scopes, true);
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getLastUsedAt(): ?DateTimeInterface
    {
        return $this->lastUsedAt;
    }

    public function setLastUsedAt(?DateTimeInterface $lastUsedAt): static
    {
        $this->lastUsedAt = $lastUsedAt;

        return $this;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function getRevokedAt(): ?DateTimeInterface
    {
        return $this->revokedAt;
    }

    public function setRevokedAt(?DateTimeInterface $revokedAt): static
    {
        $this->revokedAt = $revokedAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->revokedAt === null
            && ($this->expiresAt === null || $this->expiresAt > new DateTime())
            && $this->developerProfile?->isActive() === true;
    }
}
