<?php

namespace App\Entity;

use App\Repository\ContratRepository;
use App\Entity\Traits\StatisticsPropertiesTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ContratRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Contrat
{
    use StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]

    #[Groups(['contrat', 'facturation', 'client'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['contrat', 'client'])]
    private ?string $name = null;

    #[ORM\Column]
    #[Groups(['contrat', 'client'])]
    private ?bool $isDone = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endAt = null;

    #[ORM\OneToOne(mappedBy: 'contrat', targetEntity: Facturation::class)]
    private ?Facturation $facturation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['contrat'])]
    private ?ContratType $type = null;

    #[ORM\ManyToOne(inversedBy: 'contrats')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['contrat', 'client'])]
    private ?Client $client = null;

    /**
     * @var Collection<int, Product>
     */
    #[ORM\ManyToMany(targetEntity: Product::class, inversedBy: 'contrats')]
    #[Groups(['contrat'])]
    private Collection $products;

    public function __construct()
    {
        $this->products = new ArrayCollection();
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

    public function isDone(): ?bool
    {
        return $this->isDone;
    }

    public function setDone(bool $isDone): static
    {
        $this->isDone = $isDone;

        return $this;
    }

    public function getStartAt(): ?\DateTimeInterface
    {
        return $this->startAt;
    }

    public function setStartAt(\DateTimeInterface $startAt): static
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?\DateTimeInterface
    {
        return $this->endAt;
    }

    public function setEndAt(\DateTimeInterface $endAt): static
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getFacturation(): ?Facturation
    {
        return $this->facturation;
    }

    public function setFacturation(Facturation $facturation): static
    {
        if ($facturation->getContrat() !== $this) {
            $facturation->setContrat($this);
        }

        $this->facturation = $facturation;
        return $this;
    }
    public function getType(): ?ContratType
    {
        return $this->type;
    }

    public function setType(?ContratType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Collection<int, Product>
     */
    public function getProducts(): Collection
    {
        return $this->products;
    }

    public function addProduct(Product $product): static
    {
        if (!$this->products->contains($product)) {
            $this->products->add($product);
        }

        return $this;
    }

    public function removeProduct(Product $product): static
    {
        $this->products->removeElement($product);

        return $this;
    }
}
