<?php

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\FileAttenteProspectMailRepository;

#[ORM\Entity(repositoryClass: FileAttenteProspectMailRepository::class)]
class FileAttenteProspectMail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $sendto = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $sujet = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $replyto = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contentmail = null;

    #[ORM\Column(length: 50)]
    private string $statut = 'en_attente';

    #[ORM\Column(type: 'datetime')]
    private $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSendto(): ?string
    {
        return $this->sendto;
    }

    public function setSendto(string $sendto): static
    {
        $this->sendto = $sendto;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function setSujet(string $sujet): static
    {
        $this->sujet = $sujet;

        return $this;
    }

    public function getReplyto(): ?string
    {
        return $this->replyto;
    }

    public function setReplyto(string $replyto): static
    {
        $this->replyto = $replyto;

        return $this;
    }

    public function getContentmail(): ?string
    {
        return $this->contentmail;
    }

    public function setContentmail(string $contentmail): static
    {
        $this->contentmail = $contentmail;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
