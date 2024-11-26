<?php

namespace App\Entity;

use App\Repository\AccountRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\Traits\StatisticsPropertiesTrait;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
#[ORM\Entity(repositoryClass: AccountRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Account
{
    use StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['account','info'])]
    private ?int $id = null;

    #[Assert\NotBlank(message: "Votre Compte doit avoir un Nom")]
    #[Assert\Length(min: 4, max: 255, minMessage: "Votre Compte doit avoir un Nom comportantau moins {{limit}} caractères")]
    #[Assert\NotNull(message: "Votre Compte doit avoir un Nom non null")]
    #[ORM\Column(length: 255)]
    #[Groups(['account', 'base'])]
    private ?string $name = null;

    #[ORM\Column(length: 24)]
    #[Groups(['account','info'])]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'accounts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['account'])]
    private ?Client $client = null;

    /**
     * @var Collection<int, Info>
     */
    #[ORM\ManyToMany(targetEntity: Info::class, mappedBy: 'account')]
    #[Groups(['account','info'])]
    private Collection $info;

    public function __construct()
    {
        $this->info = new ArrayCollection();
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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
     * @return Collection<int, Info>
     */
    public function getInfo(): Collection
    {
        return $this->info;
    }

    public function addInfo(Info $info): static
    {
        if (!$this->info->contains($info)) {
            $this->info->add($info);
            $info->addAccount($this);
        }

        return $this;
    }

    public function removeInfo(Info $info): static
    {
        if ($this->info->removeElement($info)) {
            $info->removeAccount($this);
        }

        return $this;
    }
}
