<?php

namespace App\Entity;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CampagneMailRepository;

#[ORM\Entity(repositoryClass: CampagneMailRepository::class)]
class CampagneMail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'campagneMails')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

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

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column]
    private ?int $status = null;

    #[ORM\ManyToOne(inversedBy: 'campagneMails')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FormuleCampagneMail $formuleCampagneMail = null;

    public function __construct()
    {
        $this->status = 1;
        $this->createdAt = new DateTime();
        /**
         * status values description
         * 0 : rejeter
         * 1 : en attente
         * 2 : accepter et en attente de paiement
         * 3 : payer et en cours de traitement
         * 4 : terminer
         */
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getSujet(): ?string
    {
        return $this->sujet;
    }

    public function setSujet(string $sujet): self
    {
        $this->sujet = $sujet;

        return $this;
    }

    public function getReplyto(): ?string
    {
        return $this->replyto;
    }

    public function setReplyto(string $replyto): self
    {
        $this->replyto = $replyto;

        return $this;
    }

    public function getSendto(): ?string
    {
        return $this->sendto;
    }

    public function setSendto(string $sendto): self
    {
        $this->sendto = $sendto;

        return $this;
    }

    public function getContentmail(): ?string
    {
        return $this->contentmail;
    }

    public function setContentmail(string $contentmail): self
    {
        $this->contentmail = $contentmail;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getFormuleCampagneMail(): ?FormuleCampagneMail
    {
        return $this->formuleCampagneMail;
    }

    public function setFormuleCampagneMail(?FormuleCampagneMail $formuleCampagneMail): self
    {
        $this->formuleCampagneMail = $formuleCampagneMail;

        return $this;
    }
}
