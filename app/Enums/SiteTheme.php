<?php

namespace App\Enums;

enum SiteTheme: string
{
    case Navy = 'navy';
    case Fairway = 'fairway';
    case Electric = 'electric';

    /**
     * All theme values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $theme): string => $theme->value, self::cases());
    }

    /**
     * The theme applied when no preference has been saved.
     */
    public static function default(): self
    {
        return self::Navy;
    }

    /**
     * Human-readable name for the admin picker.
     */
    public function label(): string
    {
        return match ($this) {
            self::Navy => 'Court Navy',
            self::Fairway => 'Fairway',
            self::Electric => 'Electric',
        };
    }

    /**
     * Short mood description shown on the theme card.
     */
    public function description(): string
    {
        return match ($this) {
            self::Navy => 'Deep navy with electric azure — bold and sporty.',
            self::Fairway => 'Light, airy cream and forest green — premium and calm.',
            self::Electric => 'Vibrant indigo and emerald — energetic and modern.',
        };
    }
}
