<?php

namespace App\Entity;

use App\Repository\DeveloperApiAuditLogRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeveloperApiAuditLogRepository::class)]
#[ORM\Table(name: 'developer_api_audit_log')]
class DeveloperApiAuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DeveloperProfile::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?DeveloperProfile $developerProfile = null;

    #[ORM\Column(length: 40)]
    private string $keyId;

    #[ORM\Column(length: 160)]
    private string $endpoint;

    #[ORM\Column(length: 10)]
    private string $method;

    #[ORM\Column(type: Types::INTEGER)]
    private int $responseStatus;

    #[ORM\Column(length: 64, nullable: true)]
    private ?string $ipAddress = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function setDeveloperProfile(DeveloperProfile $developerProfile): static
    {
        $this->developerProfile = $developerProfile;
        return $this;
    }

    public function setKeyId(string $keyId): static
    {
        $this->keyId = $keyId;
        return $this;
    }

    public function setEndpoint(string $endpoint): static
    {
        $this->endpoint = mb_substr($endpoint, 0, 160);
        return $this;
    }

    public function setMethod(string $method): static
    {
        $this->method = mb_substr($method, 0, 10);
        return $this;
    }

    public function setResponseStatus(int $responseStatus): static
    {
        $this->responseStatus = $responseStatus;
        return $this;
    }

    public function setIpAddress(?string $ipAddress): static
    {
        $this->ipAddress = $ipAddress !== null ? mb_substr($ipAddress, 0, 64) : null;
        return $this;
    }
}
