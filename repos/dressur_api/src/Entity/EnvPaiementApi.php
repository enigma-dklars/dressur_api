<?php

namespace App\Entity;

use App\Repository\EnvPaiementApiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnvPaiementApiRepository::class)]
class EnvPaiementApi
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $aggregator = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $apiKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $environment = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $endpointSecret = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $routeWebhook = null;

    #[ORM\Column(nullable: true)]
    private ?int $countTransactionApproved = null;

    #[ORM\Column(nullable: true)]
    private ?bool $activated = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $accountName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $linkPaiement = null;

    public function __construct()
    {
        $this->activated = false;
        $this->environment = "live";
        $this->aggregator = "FedaPay";
        $this->countTransactionApproved = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAggregator(): ?string
    {
        return $this->aggregator;
    }

    public function setAggregator(?string $aggregator): static
    {
        $this->aggregator = $aggregator;

        return $this;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function setApiKey(?string $apiKey): static
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    public function setEnvironment(?string $environment): static
    {
        $this->environment = $environment;

        return $this;
    }

    public function getEndpointSecret(): ?string
    {
        return $this->endpointSecret;
    }

    public function setEndpointSecret(?string $endpointSecret): static
    {
        $this->endpointSecret = $endpointSecret;

        return $this;
    }

    public function getRouteWebhook(): ?string
    {
        return $this->routeWebhook;
    }

    public function setRouteWebhook(?string $routeWebhook): static
    {
        $this->routeWebhook = $routeWebhook;

        return $this;
    }

    public function getCountTransactionApproved(): ?int
    {
        return $this->countTransactionApproved;
    }

    public function setCountTransactionApproved(?int $countTransactionApproved): static
    {
        $this->countTransactionApproved = $countTransactionApproved;

        return $this;
    }

    public function isUsedApproved(): static
    {
        $this->countTransactionApproved++;

        return $this;
    }

    public function isActivated(): ?bool
    {
        return $this->activated;
    }

    public function setActivated(?bool $activated): static
    {
        $this->activated = $activated;

        return $this;
    }

    public function getAccountName(): ?string
    {
        return $this->accountName;
    }

    public function setAccountName(?string $accountName): static
    {
        $this->accountName = $accountName;

        return $this;
    }

    public function getLinkPaiement(): ?string
    {
        return $this->linkPaiement;
    }

    public function setLinkPaiement(?string $linkPaiement): static
    {
        $this->linkPaiement = $linkPaiement;

        return $this;
    }
}
