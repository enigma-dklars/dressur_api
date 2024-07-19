<?php

namespace App\Entity;

use App\Repository\FileAttenteCampagneMailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FileAttenteCampagneMailRepository::class)]
class FileAttenteCampagneMail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fileAttenteCampagneMails')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CampagneMail $campagneMail = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $titre = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $sujet = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $replyto = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $sendto = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $contentmail = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCampagneMail(): ?CampagneMail
    {
        return $this->campagneMail;
    }

    public function setCampagneMail(?CampagneMail $campagneMail): static
    {
        $this->campagneMail = $campagneMail;

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

    public function getSendto(): ?string
    {
        return $this->sendto;
    }

    public function setSendto(string $sendto): static
    {
        $this->sendto = $sendto;

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
}
