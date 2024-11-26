<?php

namespace App\Entity;

use App\Repository\FacturationModelRepository;
use App\Entity\Traits\StatisticsPropertiesTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: FacturationModelRepository::class)]
#[ORM\HasLifecycleCallbacks]

class FacturationModel
{
    use StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['facturationModel', 'client','facturation'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['facturationModel', 'client','facturation'])]
    private ?string $name = null;

    #[ORM\OneToOne(mappedBy: 'facturationModel', cascade: ['persist', 'remove'])]
    private ?Client $client = null;

    /**
     * @var Collection<int, Facturation>
     */
    #[ORM\OneToMany(targetEntity: Facturation::class, mappedBy: 'model')]
    private Collection $facturations;

    public function __construct()
    {
        $this->facturations = new ArrayCollection();
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

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        if ($client === null && $this->client !== null) {
            $this->client->setFacturationModel(null);
        }

        if ($client !== null && $client->getFacturationModel() !== $this) {
            $client->setFacturationModel($this);
        }

        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection<int, Facturation>
     */
    public function getFacturations(): Collection
    {
        return $this->facturations;
    }

    public function addFacturation(Facturation $facturation): static
    {
        if (!$this->facturations->contains($facturation)) {
            $this->facturations->add($facturation);
            $facturation->setModel($this);
        }

        return $this;
    }

    public function removeFacturation(Facturation $facturation): static
    {
        if ($this->facturations->removeElement($facturation)) {
            // set the owning side to null (unless already changed)
            if ($facturation->getModel() === $this) {
                $facturation->setModel(null);
            }
        }

        return $this;
    }
}
