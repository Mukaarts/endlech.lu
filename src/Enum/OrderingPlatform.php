<?php

namespace App\Enum;

enum OrderingPlatform: string
{
    case UBER_EATS = 'uber_eats';
    case DELIVEROO = 'deliveroo';
    case JUST_EAT = 'just_eat';
    case WOLT = 'wolt';
    case WEDELY = 'wedely';
    case GOOSTY = 'goosty';
    case PHONE = 'phone';
    case WEBSITE = 'website';
    case OTHER = 'other';

    public function transKey(): string
    {
        return 'ordering_platform.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::UBER_EATS => 'Uber Eats',
            self::DELIVEROO => 'Deliveroo',
            self::JUST_EAT => 'Just Eat',
            self::WOLT => 'Wolt',
            self::WEDELY => 'Wedely',
            self::GOOSTY => 'Goosty',
            self::PHONE => 'Telefon',
            self::WEBSITE => 'Webseite',
            self::OTHER => 'Andere',
        };
    }

    public function emoji(): string
    {
        return match ($this) {
            self::UBER_EATS => '🚗',
            self::DELIVEROO => '🦘',
            self::JUST_EAT => '🍽️',
            self::WOLT => '🐺',
            self::WEDELY => '🛵',
            self::GOOSTY => '🪿',
            self::PHONE => '📞',
            self::WEBSITE => '🌐',
            self::OTHER => '📦',
        };
    }

    public function actionTransKey(): string
    {
        return 'ordering_action.' . $this->value;
    }

    public function actionLabel(): string
    {
        return match ($this) {
            self::PHONE => 'Anrufen',
            self::WEBSITE => 'Zur Webseite',
            default => 'Bestellen',
        };
    }

    public function logoPath(): ?string
    {
        return match ($this) {
            self::UBER_EATS => 'images/platforms/uber-eats.svg',
            self::DELIVEROO => 'images/platforms/deliveroo.svg',
            self::JUST_EAT => 'images/platforms/just-eat.svg',
            self::WOLT => 'images/platforms/wolt.svg',
            self::WEDELY => 'images/platforms/wedely.svg',
            self::GOOSTY => 'images/platforms/goosty.svg',
            default => null,
        };
    }
}
