<?php

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\TransactionRepository;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $idTransaction;

    #[ORM\Column(type: 'string', length: 255)]
    private $reference;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $amount;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $status;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $customerId;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $currencyId;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $updatedAt;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private $user;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private array $annotherInfo = [];

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $transactionFor = null;

    #[ORM\ManyToOne]
    private ?UserBot $userBot = null;

    public function __construct()
    {
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdTransaction(): ?string
    {
        return $this->idTransaction;
    }

    public function setIdTransaction(?string $idTransaction): self
    {
        $this->idTransaction = $idTransaction;
        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;
        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(?int $amount): self
    {
        $this->amount = $amount;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): self
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getCurrencyId(): ?int
    {
        return $this->currencyId;
    }

    public function setCurrencyId(?int $currencyId): self
    {
        $this->currencyId = $currencyId;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isUpdated(): self
    {
        $this->updatedAt = new DateTime();
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

    public function getAnnotherInfo(): array
    {
        return $this->annotherInfo;
    }

    public function setAnnotherInfo(?array $annotherInfo): self
    {
        $this->annotherInfo = $annotherInfo;
        return $this;
    }

    public function getTransactionFor(): ?string
    {
        return $this->transactionFor;
    }

    public function setTransactionFor(?string $transactionFor): self
    {
        $this->transactionFor = $transactionFor;
        return $this;
    }

    public function getUserBot(): ?UserBot
    {
        return $this->userBot;
    }

    public function setUserBot(?UserBot $userBot): static
    {
        $this->userBot = $userBot;

        return $this;
    }
}


// "modes": [
//     "mtn",
//     "cybersource",
//     "moov",
//     "mtn_ci",
//     "moov_tg",
//     "orange_ci",
//     "orange_sn",
//     "free_sn",
//     "airtel_ne",
//     "togocel",
//     "orange_ml",
//     "mtn_open",
//     "mtn_ecw",
//     "ecobank_tpe",
//     "orabank_tpe",
//     "uba",
//     "stripe_gw"
//    ]