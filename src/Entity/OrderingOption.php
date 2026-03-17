<?php

namespace App\Entity;

use App\Enum\OrderingPlatform;
use App\Repository\OrderingOptionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderingOptionRepository::class)]
class OrderingOption
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 20)]
    private string $platform = '';

    #[ORM\Column(length: 500)]
    private string $url = '';

    #[ORM\ManyToOne(inversedBy: 'orderingOptions')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Restaurant $restaurant = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlatform(): string
    {
        return $this->platform;
    }

    public function getPlatformEnum(): ?OrderingPlatform
    {
        return OrderingPlatform::tryFrom($this->platform);
    }

    public function setPlatform(OrderingPlatform|string $platform): static
    {
        $this->platform = $platform instanceof OrderingPlatform ? $platform->value : $platform;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getRestaurant(): ?Restaurant
    {
        return $this->restaurant;
    }

    public function setRestaurant(?Restaurant $restaurant): static
    {
        $this->restaurant = $restaurant;

        return $this;
    }
}
