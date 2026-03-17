<?php

namespace App\Enum;

enum Language: string
{
    case LU = 'lu';
    case DE = 'de';
    case FR = 'fr';
    case EN = 'en';
    case PT = 'pt';
    case OTHER = 'other';

    public function transKey(): string
    {
        return 'language.' . $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::LU => 'Lëtzebuergesch',
            self::DE => 'Deutsch',
            self::FR => 'Français',
            self::EN => 'English',
            self::PT => 'Português',
            self::OTHER => 'Andere',
        };
    }

    public function flag(): string
    {
        return match ($this) {
            self::LU => '🇱🇺',
            self::DE => '🇩🇪',
            self::FR => '🇫🇷',
            self::EN => '🇬🇧',
            self::PT => '🇵🇹',
            self::OTHER => '🌐',
        };
    }

    public function badgeLabel(): string
    {
        return $this->flag().' '.$this->label();
    }
}
