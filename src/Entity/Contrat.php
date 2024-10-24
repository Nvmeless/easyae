<?php

namespace App\Entity;

use App\Repository\ContratRepository;
use App\Entity\Traits\StatisticsPropertiesTrait;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContratRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Contrat
{

    use StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;
    // #[ORM\Column]
    // private ?int $typeID = null;

    // #[ORM\Column]
    // private ?int $productsID = null;

    // #[ORM\Column]
    // private ?int $clientID = null;

    #[ORM\Column]
    private ?bool $isDone = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $endAt = null;

    #[ORM\OneToOne(mappedBy: 'Contrat')]
    private ?Facturation $facturation = null;

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

    // public function getTypeID(): ?int
    // {
    //     return $this->typeID;
    // }

    // public function setTypeID(int $typeID): static
    // {
    //     $this->typeID = $typeID;

    //     return $this;
    // }

    // public function getProductsID(): ?int
    // {
    //     return $this->productsID;
    // }

    // public function setProductsID(int $productsID): static
    // {
    //     $this->productsID = $productsID;

    //     return $this;
    // }

    // public function getClientID(): ?int
    // {
    //     return $this->clientID;
    // }

    // public function setClientID(int $clientID): static
    // {
    //     $this->clientID = $clientID;

    //     return $this;
    // }

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
        // set the owning side of the relation if necessary
        if ($facturation->getContrat() !== $this) {
            $facturation->setContrat($this);
        }

        $this->facturation = $facturation;

        return $this;
    }
}
