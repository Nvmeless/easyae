<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\HistoryRepository;
use App\Entity\Traits\StatisticsPropertiesTrait;

#[ORM\Entity(repositoryClass: HistoryRepository::class)]
#[ORM\HasLifecycleCallbacks]

class History
{
    use StatisticsPropertiesTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'histories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\ManyToOne(inversedBy: 'histories')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Action $action = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getService(): ?Service
    {
        return $this->service;
    }

    public function setService(?Service $service): static
    {
        $this->service = $service;

        return $this;
    }

    public function getAction(): ?Action
    {
        return $this->action;
    }

    public function setAction(?Action $action): static
    {
        $this->action = $action;

        return $this;
    }
}
