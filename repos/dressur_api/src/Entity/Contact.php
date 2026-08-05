<?php

namespace App\Entity;

use DateTime;
use App\Entity\User;
use App\Repository\ContactRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Proxies\__CG__\App\Entity\User as EntityUser;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\OneToOne(inversedBy: 'contact', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private array $whoIAdd = [];

    #[ORM\Column]
    private array $whoAddMe = [];

    public function __construct()
    {
        $this->whoIAdd = [];
        $this->whoAddMe = [];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getWhoIAdd(): array
    {
        return $this->whoIAdd;
    }

    public function setWhoIAdd(array $whoIAdd): self
    {
        $this->whoIAdd = $whoIAdd;

        return $this;
    }

    public function getWhoAddMe(): array
    {
        return $this->whoAddMe;
    }

    public function setWhoAddMe(array $whoAddMe): self
    {
        $this->whoAddMe = $whoAddMe;

        return $this;
    }

    // une methode pour le user actuel qui ajoute un autre user
    public function setNewIAdd(User $userIAdd): self
    {
        if(!in_array($userIAdd->getId(), $this->whoIAdd)) {
            array_push($this->whoIAdd, $userIAdd->getId());
        }
        return $this;
    }

    // une methode pour un user qui ajoute le présent user
    public function setNewAddMe(User $userAddMe): self
    {
        if(!in_array($userAddMe->getId(), $this->whoAddMe)) {
            array_push($this->whoAddMe, $userAddMe->getId());
        }
        return $this;
    }    

    // une methode pour recupperer les id de tous les contacts du user connecter (whoIAdd and whoAddMe)
    public function getAllIdOfMyContacts(): array
    {
        $allIdOfMyContacts = [];
        foreach ($this->whoIAdd as $key => $idContact) {
            if(!in_array($idContact, $allIdOfMyContacts)){
                array_push($allIdOfMyContacts, $idContact);
            }
        }
        foreach ($this->whoAddMe as $key => $idContact) {
            if(!in_array($idContact, $allIdOfMyContacts)){
                array_push($allIdOfMyContacts, $idContact);
            }
        }
        return $allIdOfMyContacts;
    }

}
