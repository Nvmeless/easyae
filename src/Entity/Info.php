<?php

namespace App\Entity;

use App\Entity\Traits\StatisticsPropertiesTrait;
use App\Repository\InfoRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: InfoRepository::class)]
#[ORM\HasLifecycleCallbacks]

class Info
{

    use StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['info','client','account'])]
    private ?int $id = null;

    #[ORM\Column]
    #[Groups(['info','client','account'])]
    private ?bool $isAnonymous = null;

    #[ORM\Column(length: 255)]
    #[Groups(['info','client','account'])]
    private ?string $info = null;

    #[ORM\ManyToOne(inversedBy: 'infos')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['info'])]
    private ?InfoType $type = null;

    /**
     * @var Collection<int, Client>
     */
    #[ORM\ManyToMany(targetEntity: Client::class, inversedBy: 'info')]
    #[Groups(['info','client'])]
    private Collection $client;

    /**
     * @var Collection<int, Account>
     */
    #[ORM\ManyToMany(targetEntity: Account::class, inversedBy: 'info')]
    #[Groups(['info','account'])]
    private Collection $account;

    public function __construct()
    {
        $this->client = new ArrayCollection();
        $this->account = new ArrayCollection();
    }

    #[ORM\ManyToOne(inversedBy: 'info')]
    private ?User $user = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isAnonymous(): ?bool
    {
        return $this->isAnonymous;
    }

    public function setAnonymous(bool $isAnonymous): static
    {
        $this->isAnonymous = $isAnonymous;

        return $this;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(string $info): static
    {
        $this->info = $info;

        return $this;
    }

    public function getType(): ?InfoType
    {
        return $this->type;
    }

    public function setType(?InfoType $type): static
    {
        $this->type = $type;

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

    /**
     * @return Collection<int, Client>
     */
    public function getClient(): Collection
    {
        return $this->client;
    }

    public function addClient(Client $client): static
    {
        if (!$this->client->contains($client)) {
            $this->client->add($client);
        }

        return $this;
    }

    public function removeClient(Client $client): static
    {
        $this->client->removeElement($client);

        return $this;
    }

    /**
     * @return Collection<int, Account>
     */
    public function getAccount(): Collection
    {
        return $this->account;
    }

    public function addAccount(Account $account): static
    {
        if (!$this->account->contains($account)) {
            $this->account->add($account);
        }

        return $this;
    }

    public function removeAccount(Account $account): static
    {
        $this->account->removeElement($account);

        return $this;
    }
}
