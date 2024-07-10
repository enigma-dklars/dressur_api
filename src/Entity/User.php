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

    #[ORM\Column(type: 'float', nullable: true)]
    private $soldeBonus;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $codeBonus;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'filleuls')]
    private $parrain;

    #[ORM\OneToMany(mappedBy: 'parrain', targetEntity: self::class)]
    private $filleuls;

    #[ORM\OneToOne(mappedBy: 'user', targetEntity: Preference::class, cascade: ['persist', 'remove'])]
    private $preference;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Boost::class)]
    private $boosts;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private $themeDark;

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

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CampagneMail::class, orphanRemoval: true)]
    private Collection $campagneMails;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: PromoReseau::class, orphanRemoval: true)]
    private Collection $promoReseaus;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Suggestion::class, orphanRemoval: true)]
    private Collection $suggestions;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $banniere = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $hasReceived = null;

    public function __construct()
    {
        $this->admin = false;
        $this->avatar = "avatar_".rand(1, 10).".png";
        $this->banniere = "banniere_dressur.jpg";
        $this->createdAt = new DateTime();
        $this->uid = uniqid();
        $this->codeBonus = $this->codeBonus();
        $this->filleuls = new ArrayCollection();
        $this->boosts = new ArrayCollection();
        $this->soldeBonus = 1000;
        $this->telIsVerified = false;
        $this->mailIsVerified = false;
        $this->themeDark = false;
        $this->blocked = false;
        $this->promotions = new ArrayCollection();
        $this->campagneMails = new ArrayCollection();
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

    public function codeBonus(int $length = 5): ?string
    {
        // allowed characters
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890123456789";
        // make sure we have enough length
        while (strlen($chars) < $length) {
            $chars .= $chars;
        }
        return "DS".substr(str_shuffle($chars), 0, $length);
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

    public function getSoldeBonus(): ?int
    {
        return $this->soldeBonus;
    }

    public function setSoldeBonus(?int $soldeBonus): self
    {
        $this->soldeBonus = $soldeBonus;

        return $this;
    }

    public function addSoldeBonus(?int $soldeBonus): self
    {
        $this->soldeBonus += $soldeBonus;

        return $this;
    }

    public function debitSoldeBonus(?int $soldeBonus): self
    {
        $this->soldeBonus -= $soldeBonus;

        return $this;
    }

    public function getCodeBonus(): ?string
    {
        return $this->codeBonus;
    }

    public function setCodeBonus(?string $codeBonus): self
    {
        $this->codeBonus = $codeBonus;

        return $this;
    }

    public function getParrain(): ?self
    {
        return $this->parrain;
    }

    public function setParrain(?self $parrain): self
    {
        $this->parrain = $parrain;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getFilleuls(): Collection
    {
        return $this->filleuls;
    }

    public function addFilleul(self $filleul): self
    {
        if (!$this->filleuls->contains($filleul)) {
            $this->filleuls[] = $filleul;
            $filleul->setParrain($this);
        }

        return $this;
    }

    public function removeFilleul(self $filleul): self
    {
        if ($this->filleuls->removeElement($filleul)) {
            // set the owning side to null (unless already changed)
            if ($filleul->getParrain() === $this) {
                $filleul->setParrain(null);
            }
        }

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

    public function getThemeDark(): ?bool
    {
        return $this->themeDark;
    }

    public function setThemeDark(?bool $themeDark): self
    {
        $this->themeDark = $themeDark;

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
     * @return Collection<int, CampagneMail>
     */
    public function getCampagneMails(): Collection
    {
        return $this->campagneMails;
    }

    public function addCampagneMail(CampagneMail $campagneMail): self
    {
        if (!$this->campagneMails->contains($campagneMail)) {
            $this->campagneMails->add($campagneMail);
            $campagneMail->setUser($this);
        }

        return $this;
    }

    public function removeCampagneMail(CampagneMail $campagneMail): self
    {
        if ($this->campagneMails->removeElement($campagneMail)) {
            // set the owning side to null (unless already changed)
            if ($campagneMail->getUser() === $this) {
                $campagneMail->setUser(null);
            }
        }

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

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getBanniere(): ?string
    {
        return $this->banniere;
    }

    public function setBanniere(?string $banniere): static
    {
        $this->banniere = $banniere;

        return $this;
    }

    public function getHasReceived(): ?string
    {
        return $this->hasReceived;
    }

    public function setHasReceived(?string $hasReceived): static
    {
        $this->hasReceived = $hasReceived;

        return $this;
    }
}
