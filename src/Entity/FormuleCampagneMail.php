<?php

namespace App\Entity;

use App\Repository\FormuleCampagneMailRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FormuleCampagneMailRepository::class)]
class FormuleCampagneMail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $titre = null;

    #[ORM\Column]
    private ?int $prix = null;

    #[ORM\Column]
    private ?int $nombre_mail = null;

    #[ORM\OneToMany(mappedBy: 'formuleCampagneMail', targetEntity: CampagneMail::class, orphanRemoval: true)]
    private Collection $campagneMails;

    public function __construct()
    {
        $this->campagneMails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPrix(): ?int
    {
        return $this->prix;
    }

    public function setPrix(int $prix): self
    {
        $this->prix = $prix;

        return $this;
    }

    public function getNombreMail(): ?int
    {
        return $this->nombre_mail;
    }

    public function setNombreMail(int $nombre_mail): self
    {
        $this->nombre_mail = $nombre_mail;

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
            $campagneMail->setFormuleCampagneMail($this);
        }

        return $this;
    }

    public function removeCampagneMail(CampagneMail $campagneMail): self
    {
        if ($this->campagneMails->removeElement($campagneMail)) {
            // set the owning side to null (unless already changed)
            if ($campagneMail->getFormuleCampagneMail() === $this) {
                $campagneMail->setFormuleCampagneMail(null);
            }
        }

        return $this;
    }
}
