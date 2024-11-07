<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use App\Entity\Traits\StatisticsPropertiesTrait;

use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\HasLifecycleCallbacks]

class Contact
{

    use StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['client'])]
    private ?int $id = null;

    #[ORM\Column(length: 15)]

    #[Groups(['client', 'contact'])]

    private ?string $name = null;

    /**
     * @var Collection<int, ContactLink>
     */
    #[ORM\OneToMany(targetEntity: ContactLink::class, mappedBy: 'contact')]
    #[Groups(['contact'])]

    private Collection $link;
    /**    
     * @var Collection<int, Fonction>
     */
    #[ORM\OneToMany(targetEntity: Fonction::class, mappedBy: 'contact')]
    #[Groups(['contact'])]

    private Collection $fonctions;

    #[ORM\ManyToOne(inversedBy: 'contacts')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;


    public function __construct()
    {
        $this->link = new ArrayCollection();
        $this->fonctions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, ContactLink>
     */
    public function getLink(): Collection
    {
        return $this->link;
    }

    public function addLink(ContactLink $link): static
    {
        if (!$this->link->contains($link)) {
            $this->link->add($link);
            $link->setContact($this);
        }
        return $this;

    }

    public function removeLink(ContactLink $link): static
    {
        if ($this->link->removeElement($link)) {
            // set the owning side to null (unless already changed)
            if ($link->getContact() === $this) {
                $link->setContact(null);
            }
        }
        return $this;

    }

    public function getFonctions(): Collection
    {
        return $this->fonctions;
    }

    public function addFonction(Fonction $fonction): static
    {
        if (!$this->fonctions->contains($fonction)) {
            $this->fonctions->add($fonction);
            $fonction->setContact($this);
        }

        return $this;
    }
    public function removeFonction(Fonction $fonction): static
    {
        if ($this->fonctions->removeElement($fonction)) {
            // set the owning side to null (unless already changed)
            if ($fonction->getContact() === $this) {
                $fonction->setContact(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
}
