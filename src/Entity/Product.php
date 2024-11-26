<?php

namespace App\Entity;

use App\Entity\Traits\StatisticsPropertiesTrait;
use App\Repository\ProductRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[ORM\HasLifecycleCallbacks]

class Product
{

    use StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['product', 'base'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['product', 'base'])]
    private ?int $quantity = null;

    #[ORM\Column]
    #[Groups(['product', 'base'])]

    private ?float $price = null;

    #[ORM\Column]
    #[Groups(['product', 'base'])]
    private ?float $priceUnit = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product', 'contrat'])]
    private ?ProductType $type = null;

    #[ORM\ManyToOne(inversedBy: 'products')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['product'])]
    private ?QuantityType $quantityType = null;
    /**
     * @var Collection<int, Contrat>
     */
    #[ORM\ManyToMany(targetEntity: Contrat::class, mappedBy: 'products')]
    private Collection $contrats;

    #[ORM\Column]
    #[Groups(['product'])]
    private ?float $fees = null;

    public function __construct()
    {
        $this->contrats = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getPriceUnit(): ?float
    {
        return $this->priceUnit;
    }

    public function setPriceUnit(float $priceUnit): static
    {
        $this->priceUnit = $priceUnit;

        return $this;
    }

    public function getType(): ?ProductType
    {
        return $this->type;
    }

    public function setType(?ProductType $type): static
    {
        $this->type = $type;

        return $this;
    }
    public function getQuantityType(): ?QuantityType
    {
        return $this->quantityType;
    }

    public function setQuantityType(?QuantityType $quantityType): static
    {
        $this->quantityType = $quantityType;
        return $this;
    }
    /**
     * @return Collection<int, Contrat>
     */
    public function getContrats(): Collection
    {
        return $this->contrats;
    }

    public function addContrat(Contrat $contrat): static
    {
        if (!$this->contrats->contains($contrat)) {
            $this->contrats->add($contrat);
            $contrat->addProduct($this);
        }

        return $this;
    }

    public function removeContrat(Contrat $contrat): static
    {
        if ($this->contrats->removeElement($contrat)) {
            $contrat->removeProduct($this);
        }

        return $this;
    }

    public function getFees(): ?float
    {
        return $this->fees;
    }

    public function setFees(float $fees): static
    {
        $this->fees = $fees;

        return $this;
    }
}
