<?php

namespace App\Entity;

use App\Repository\EnvRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EnvRepository::class)]
class Env
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $commissionBonus;

    #[ORM\Column(type: 'string', length: 255)]
    private $versionApp;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $importantUpdate;

    #[ORM\Column(nullable: true)]
    private array $usersTel = [];

    #[ORM\Column(nullable: true)]
    private ?bool $doBoostPayant = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $linkLocalServer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $versionDressurBot = null;

    #[ORM\Column(nullable: true)]
    private ?array $usersParrainer = [];

    #[ORM\Column(nullable: true)]
    private ?array $userBanned = null;

    public function addUsersParrainer($tel_or_mail): self
    {
        if($this->usersParrainer == NULL) {
            $this->usersParrainer = [];
        }
        if (!in_array($tel_or_mail, $this->usersParrainer)) {
            array_push($this->usersParrainer, $tel_or_mail);
        }
        return $this;
    }

    public function addUsersTel($tel_or_mail): self
    {
        if (!in_array($tel_or_mail, $this->usersTel)) {
            array_push($this->usersTel, $tel_or_mail);
        }
        return $this;
    }

    public function addUserBanned($tel_or_mail): self
    {
        if($this->userBanned == Null) {
            $this->userBanned = [];
        }
        if (!in_array($tel_or_mail, $this->userBanned)) {
            array_push($this->userBanned, $tel_or_mail);
        }
        return $this;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCommissionBonus(): ?int
    {
        return $this->commissionBonus;
    }

    public function setCommissionBonus(?int $commissionBonus): self
    {
        $this->commissionBonus = $commissionBonus;

        return $this;
    }

    public function getVersionApp(): ?string
    {
        return $this->versionApp;
    }

    public function setVersionApp(string $versionApp): self
    {
        $this->versionApp = $versionApp;

        return $this;
    }

    public function getImportantUpdate(): ?bool
    {
        return $this->importantUpdate;
    }

    public function setImportantUpdate(?bool $importantUpdate): self
    {
        $this->importantUpdate = $importantUpdate;

        return $this;
    }

    public function getUsersTel(): array
    {
        return $this->usersTel;
    }

    public function setUsersTel(?array $usersTel): self
    {
        $this->usersTel = $usersTel;

        return $this;
    }

    public function getDoBoostPayant(): ?bool
    {
        return $this->doBoostPayant;
    }

    public function setDoBoostPayant(?bool $doBoostPayant): self
    {
        $this->doBoostPayant = $doBoostPayant;

        return $this;
    }

    public function getLinkLocalServer(): ?string
    {
        return $this->linkLocalServer;
    }

    public function setLinkLocalServer(?string $linkLocalServer): self
    {
        $this->linkLocalServer = $linkLocalServer;

        return $this;
    }

    public function getVersionDressurBot(): ?string
    {
        return $this->versionDressurBot;
    }

    public function setVersionDressurBot(?string $versionDressurBot): static
    {
        $this->versionDressurBot = $versionDressurBot;

        return $this;
    }

    public function getUsersParrainer(): ?array
    {
        return $this->usersParrainer;
    }

    public function setUsersParrainer(?array $usersParrainer): static
    {
        $this->usersParrainer = $usersParrainer;

        return $this;
    }

    public function getUserBanned(): ?array
    {
        return $this->userBanned;
    }

    public function setUserBanned(?array $userBanned): static
    {
        $this->userBanned = $userBanned;

        return $this;
    }
}
