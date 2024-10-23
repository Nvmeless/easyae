<?php

namespace App\Entity;

use App\Entity\Traits\StatisticsPropertiesTrait;
use App\Repository\InfoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: InfoRepository::class)]
class Info
{

    use StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $isAnonymous = null;

    #[ORM\Column(length: 255)]
    private ?string $info = null;

    #[ORM\ManyToOne(inversedBy: 'infos')]
    #[ORM\JoinColumn(nullable: false)]
    private ?InfoType $type = null;

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
}
