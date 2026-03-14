<?php

namespace App\Enum;

enum OrderingPlatform: string
{
    case UBER_EATS = 'uber_eats';
    case DELIVEROO = 'deliveroo';
    case JUST_EAT = 'just_eat';
    case PHONE = 'phone';
    case WEBSITE = 'website';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::UBER_EATS => 'Uber Eats',
            self::DELIVEROO => 'Deliveroo',
            self::JUST_EAT => 'Just Eat',
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
            self::PHONE => '📞',
            self::WEBSITE => '🌐',
            self::OTHER => '📦',
        };
    }

    public function actionLabel(): string
    {
        return match ($this) {
            self::PHONE => 'Anrufen',
            self::WEBSITE => 'Zur Webseite',
            default => 'Bestellen',
        };
    }
}
