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

    #[ORM\Column(type: 'string', length: 255)]
    private $versionApp;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $importantUpdate;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $versionDressurBot = null;

    #[ORM\Column(type: 'boolean', nullable: false, options: ['default' => true])]
    private bool $iaActive = true;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 2000])]
    private int $fraisAdhesionVendeur = 2000;

    #[ORM\Column(type: 'integer', nullable: false, options: ['default' => 0])]
    private int $montantRechargeInitialeDeveloppeur = 0;

    #[ORM\Column(name: 'zefame_api_key', length: 255, nullable: true)]
    private ?string $zefameApiKey = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getVersionDressurBot(): ?string
    {
        return $this->versionDressurBot;
    }

    public function setVersionDressurBot(?string $versionDressurBot): static
    {
        $this->versionDressurBot = $versionDressurBot;

        return $this;
    }

    public function getFraisAdhesionVendeur(): int
    {
        return $this->fraisAdhesionVendeur;
    }

    public function getMontantRechargeInitialeDeveloppeur(): int
    {
        return $this->montantRechargeInitialeDeveloppeur;
    }

    public function setMontantRechargeInitialeDeveloppeur(int $montantRechargeInitialeDeveloppeur): static
    {
        $this->montantRechargeInitialeDeveloppeur = $montantRechargeInitialeDeveloppeur;

        return $this;
    }

    public function getZefameApiKey(): ?string
    {
        return $this->zefameApiKey;
    }

    public function setZefameApiKey(?string $zefameApiKey): static
    {
        $zefameApiKey = $zefameApiKey !== null ? trim($zefameApiKey) : null;
        $this->zefameApiKey = $zefameApiKey !== '' ? $zefameApiKey : null;

        return $this;
    }

    public function setFraisAdhesionVendeur(int $fraisAdhesionVendeur): static
    {
        $this->fraisAdhesionVendeur = $fraisAdhesionVendeur;

        return $this;
    }

    public function isIaActive(): bool
    {
        return $this->iaActive;
    }

    public function setIaActive(bool $iaActive): static
    {
        $this->iaActive = $iaActive;

        return $this;
    }
}
