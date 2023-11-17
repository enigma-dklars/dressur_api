<?php

namespace App\Entity;

use App\Repository\EnvRepository;
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
}
