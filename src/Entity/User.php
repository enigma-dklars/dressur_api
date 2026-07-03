<?php

namespace App\Entity;

use App\Repository\UserRepository;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $uid;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $pseudo;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $nom;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $mail;

    #[ORM\Column(type: 'integer', nullable: true)]
    private $pays;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $tel;

    #[ORM\Column(type: 'text', nullable: true)]
    private $apropos;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $password;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $createdAt;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $mailIsVerified;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $telIsVerified;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Preference::class, cascade: ['persist', 'remove'])]
    private $preference;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Boost::class)]
    private $boosts;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $admin;

    #[ORM\Column(type: 'boolean')]
    private $blocked;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Promotion::class, orphanRemoval: true)]
    private Collection $promotions;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $tiktok = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $instagram = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $facebook = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $youtube = null;

    #[ORM\OneToOne(mappedBy: 'user', cascade: ['persist', 'remove'])]
    private ?Contact $contact = null;

    #[ORM\Column(length: 2)]
    private ?string $lang = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastLoginTo = null;


    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PromoReseau::class, orphanRemoval: true)]
    private Collection $promoReseaus;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Suggestion::class, orphanRemoval: true)]
    private Collection $suggestions;

    #[ORM\Column]
    private ?bool $isInscritProgrammeRecompense = null;

    #[ORM\Column]
    private ?int $soldeProgrammeRecompense = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $reseauRetrait = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $numeroRetrait = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lid = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $registerSource = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $lastLoginSource = null;

    public function __construct()
    {
        $this->admin = false;
        $this->createdAt = new DateTime();
        $this->lastLoginTo = new DateTime();
        $this->uid = \App\Utilities\UuidGenerator::v4();
        $this->boosts = new ArrayCollection();
        $this->telIsVerified = false;
        $this->mailIsVerified = false;
        $this->blocked = false;
        $this->isInscritProgrammeRecompense = false;
        $this->soldeProgrammeRecompense = 0;
        $this->lang = "fr";
        
        $this->promotions = new ArrayCollection();
        $this->promoReseaus = new ArrayCollection();
        $this->suggestions = new ArrayCollection();
    }

    public function __toString()
    {
        if($this->nom) {
            return $this->nom." (".$this->pseudo.")";
        } else {
            return $this->pseudo;
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): self
    {
        $this->nom = $nom;

        return $this;
    }
    
    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): self
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getTel(): ?string
    {
        return $this->tel;
    }

    public function setTel(?string $tel): self
    {
        $this->tel = $tel;

        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(string $uid): self
    {
        $this->uid = $uid;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getPays(): ?int
    {
        return $this->pays;
    }

    public function setPays(?int $pays): self
    {
        $this->pays = $pays;

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

    public function getApropos(): ?string
    {
        return $this->apropos;
    }

    public function setApropos(?string $apropos): self
    {
        $this->apropos = $apropos;

        return $this;
    }

    public function getMailIsVerified(): ?bool
    {
        return $this->mailIsVerified;
    }

    public function setMailIsVerified(?bool $mailIsVerified): self
    {
        $this->mailIsVerified = $mailIsVerified;

        return $this;
    }

    public function getTelIsVerified(): ?bool
    {
        return $this->telIsVerified;
    }

    public function setTelIsVerified(?bool $telIsVerified): self
    {
        $this->telIsVerified = $telIsVerified;

        return $this;
    }

    public function getPreference(): ?Preference
    {
        return $this->preference;
    }

    public function setPreference(Preference $preference): self
    {
        // set the owning side of the relation if necessary
        if ($preference->getUser() !== $this) {
            $preference->setUser($this);
        }

        $this->preference = $preference;

        return $this;
    }

    /**
     * @return Collection<int, Boost>
     */
    public function getBoosts(): Collection
    {
        return $this->boosts;
    }

    public function addBoost(Boost $boost): self
    {
        if (!$this->boosts->contains($boost)) {
            $this->boosts[] = $boost;
            $boost->setUser($this);
        }

        return $this;
    }

    public function removeBoost(Boost $boost): self
    {
        if ($this->boosts->removeElement($boost)) {
            // set the owning side to null (unless already changed)
            if ($boost->getUser() === $this) {
                $boost->setUser(null);
            }
        }

        return $this;
    }

    public function getAdmin(): ?bool
    {
        return $this->admin;
    }

    public function setAdmin(?bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getBlocked(): ?bool
    {
        return $this->blocked;
    }

    public function setBlocked(bool $blocked): self
    {
        $this->blocked = $blocked;

        return $this;
    }

    /**
     * @return Collection<int, Promotion>
     */
    public function getPromotions(): Collection
    {
        return $this->promotions;
    }

    public function addPromotion(Promotion $promotion): self
    {
        if (!$this->promotions->contains($promotion)) {
            $this->promotions->add($promotion);
            $promotion->setUser($this);
        }

        return $this;
    }

    public function removePromotion(Promotion $promotion): self
    {
        if ($this->promotions->removeElement($promotion)) {
            // set the owning side to null (unless already changed)
            if ($promotion->getUser() === $this) {
                $promotion->setUser(null);
            }
        }

        return $this;
    }

    public function getTiktok(): ?string
    {
        return $this->tiktok;
    }

    public function setTiktok(?string $tiktok): self
    {
        $this->tiktok = $tiktok;

        return $this;
    }

    public function getInstagram(): ?string
    {
        return $this->instagram;
    }

    public function setInstagram(?string $instagram): self
    {
        $this->instagram = $instagram;

        return $this;
    }

    public function getFacebook(): ?string
    {
        return $this->facebook;
    }

    public function setFacebook(?string $facebook): self
    {
        $this->facebook = $facebook;

        return $this;
    }

    public function getYoutube(): ?string
    {
        return $this->youtube;
    }

    public function setYoutube(?string $youtube): self
    {
        $this->youtube = $youtube;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(Contact $contact): self
    {
        // set the owning side of the relation if necessary
        if ($contact->getUser() !== $this) {
            $contact->setUser($this);
        }

        $this->contact = $contact;

        return $this;
    }

    public function getLang(): ?string
    {
        return $this->lang;
    }

    public function setLang(string $lang): self
    {
        $this->lang = $lang;

        return $this;
    }

    public function getLastLoginTo(): ?\DateTimeInterface
    {
        return $this->lastLoginTo;
    }

    public function setLastLoginTo(?\DateTimeInterface $lastLoginTo): self
    {
        $this->lastLoginTo = $lastLoginTo;

        return $this;
    }


    /**
     * @return Collection<int, PromoReseau>
     */
    public function getPromoReseaus(): Collection
    {
        return $this->promoReseaus;
    }

    public function addPromoReseau(PromoReseau $promoReseau): self
    {
        if (!$this->promoReseaus->contains($promoReseau)) {
            $this->promoReseaus->add($promoReseau);
            $promoReseau->setUser($this);
        }

        return $this;
    }

    public function removePromoReseau(PromoReseau $promoReseau): self
    {
        if ($this->promoReseaus->removeElement($promoReseau)) {
            // set the owning side to null (unless already changed)
            if ($promoReseau->getUser() === $this) {
                $promoReseau->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Suggestion>
     */
    public function getSuggestions(): Collection
    {
        return $this->suggestions;
    }

    public function addSuggestion(Suggestion $suggestion): self
    {
        if (!$this->suggestions->contains($suggestion)) {
            $this->suggestions->add($suggestion);
            $suggestion->setUser($this);
        }

        return $this;
    }

    public function removeSuggestion(Suggestion $suggestion): self
    {
        if ($this->suggestions->removeElement($suggestion)) {
            // set the owning side to null (unless already changed)
            if ($suggestion->getUser() === $this) {
                $suggestion->setUser(null);
            }
        }

        return $this;
    }

    public function getIsInscritProgrammeRecompense(): ?bool
    {
        return $this->isInscritProgrammeRecompense ?? false;
    }

    public function setIsInscritProgrammeRecompense(bool $isInscritProgrammeRecompense): static
    {
        $this->isInscritProgrammeRecompense = $isInscritProgrammeRecompense;

        return $this;
    }

    public function getSoldeProgrammeRecompense(): ?int
    {
        return $this->soldeProgrammeRecompense;
    }

    public function setSoldeProgrammeRecompense(int $soldeProgrammeRecompense): static
    {
        $this->soldeProgrammeRecompense = $soldeProgrammeRecompense;

        return $this;
    }

    public function addSoldeProgrammeRecompense(int $montant): self
    {
        if ($this->soldeProgrammeRecompense === null) {
            $this->soldeProgrammeRecompense = 0;
        }

        $this->soldeProgrammeRecompense += $montant;

        return $this;
    }

    public function getReseauRetrait(): ?string
    {
        return $this->reseauRetrait;
    }

    public function setReseauRetrait(?string $reseauRetrait): static
    {
        $this->reseauRetrait = $reseauRetrait;

        return $this;
    }

    public function getNumeroRetrait(): ?string
    {
        return $this->numeroRetrait;
    }

    public function setNumeroRetrait(?string $numeroRetrait): static
    {
        $this->numeroRetrait = $numeroRetrait;

        return $this;
    }

    public function getLid(): ?string
    {
        return $this->lid;
    }

    public function setLid(?string $lid): static
    {
        $this->lid = $lid;

        return $this;
    }

    public function getRegisterSource(): ?string
    {
        return $this->registerSource;
    }

    public function setRegisterSource(?string $registerSource): static
    {
        $this->registerSource = $registerSource;

        return $this;
    }

    public function getLastLoginSource(): ?string
    {
        return $this->lastLoginSource;
    }

    public function setLastLoginSource(?string $lastLoginSource): static
    {
        $this->lastLoginSource = $lastLoginSource;

        return $this;
    }
}
