<?php

namespace App\Entity;

use App\Repository\RestaurantRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RestaurantRepository::class)]
class Restaurant
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name = '';

    #[ORM\Column(length: 100)]
    private string $city = '';

    #[ORM\Column(length: 80)]
    private string $cuisine = '';

    #[ORM\Column(length: 10)]
    private string $emoji = '🍽️';

    #[ORM\Column(nullable: true)]
    private ?float $rating = null;

    #[ORM\Column]
    private bool $isOpen = false;

    #[ORM\Column]
    private bool $isWheelchairAccessible = false;

    #[ORM\Column]
    private bool $hasAccessibleToilet = false;

    #[ORM\Column]
    private bool $allowsAssistanceDogs = false;

    #[ORM\Column]
    private bool $hasBrightLighting = false;

    #[ORM\Column]
    private bool $acceptsCash = false;

    #[ORM\Column]
    private bool $acceptsCard = false;

    #[ORM\Column]
    private bool $acceptsPayconiq = false;

    /** @var list<string> */
    #[ORM\Column(type: 'json')]
    private array $accessibilityNotes = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCuisine(): string
    {
        return $this->cuisine;
    }

    public function setCuisine(string $cuisine): static
    {
        $this->cuisine = $cuisine;

        return $this;
    }

    public function getEmoji(): string
    {
        return $this->emoji;
    }

    public function setEmoji(string $emoji): static
    {
        $this->emoji = $emoji;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function isOpen(): bool
    {
        return $this->isOpen;
    }

    public function setIsOpen(bool $isOpen): static
    {
        $this->isOpen = $isOpen;

        return $this;
    }

    public function isWheelchairAccessible(): bool
    {
        return $this->isWheelchairAccessible;
    }

    public function setIsWheelchairAccessible(bool $isWheelchairAccessible): static
    {
        $this->isWheelchairAccessible = $isWheelchairAccessible;

        return $this;
    }

    public function hasAccessibleToilet(): bool
    {
        return $this->hasAccessibleToilet;
    }

    public function setHasAccessibleToilet(bool $hasAccessibleToilet): static
    {
        $this->hasAccessibleToilet = $hasAccessibleToilet;

        return $this;
    }

    public function allowsAssistanceDogs(): bool
    {
        return $this->allowsAssistanceDogs;
    }

    public function setAllowsAssistanceDogs(bool $allowsAssistanceDogs): static
    {
        $this->allowsAssistanceDogs = $allowsAssistanceDogs;

        return $this;
    }

    public function hasBrightLighting(): bool
    {
        return $this->hasBrightLighting;
    }

    public function setHasBrightLighting(bool $hasBrightLighting): static
    {
        $this->hasBrightLighting = $hasBrightLighting;

        return $this;
    }

    public function acceptsCash(): bool
    {
        return $this->acceptsCash;
    }

    public function setAcceptsCash(bool $acceptsCash): static
    {
        $this->acceptsCash = $acceptsCash;

        return $this;
    }

    public function acceptsCard(): bool
    {
        return $this->acceptsCard;
    }

    public function setAcceptsCard(bool $acceptsCard): static
    {
        $this->acceptsCard = $acceptsCard;

        return $this;
    }

    public function acceptsPayconiq(): bool
    {
        return $this->acceptsPayconiq;
    }

    public function setAcceptsPayconiq(bool $acceptsPayconiq): static
    {
        $this->acceptsPayconiq = $acceptsPayconiq;

        return $this;
    }

    /** @return list<string> */
    public function getAccessibilityNotes(): array
    {
        return $this->accessibilityNotes;
    }

    /** @param list<string> $accessibilityNotes */
    public function setAccessibilityNotes(array $accessibilityNotes): static
    {
        $this->accessibilityNotes = $accessibilityNotes;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
