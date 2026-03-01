<?php

namespace App\Entity;

use App\Repository\RestaurantSuggestionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RestaurantSuggestionRepository::class)]
class RestaurantSuggestion
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $suggestedBy = null;

    #[ORM\Column(length: 150)]
    private string $name = '';

    #[ORM\Column(length: 100)]
    private string $city = '';

    #[ORM\Column(length: 80)]
    private string $cuisine = '';

    #[ORM\Column(length: 10)]
    private string $emoji = '🍽️';

    #[ORM\Column]
    private bool $isWheelchairAccessible = false;

    #[ORM\Column]
    private bool $hasAccessibleToilet = false;

    #[ORM\Column]
    private bool $allowsAssistanceDogs = false;

    #[ORM\Column]
    private bool $hasBrightLighting = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $adminNote = null;

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

    public function getSuggestedBy(): ?User
    {
        return $this->suggestedBy;
    }

    public function setSuggestedBy(?User $suggestedBy): static
    {
        $this->suggestedBy = $suggestedBy;

        return $this;
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

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getAdminNote(): ?string
    {
        return $this->adminNote;
    }

    public function setAdminNote(?string $adminNote): static
    {
        $this->adminNote = $adminNote;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
