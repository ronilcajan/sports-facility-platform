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
            self::Navy => 'Gold & Onyx',
            self::Fairway => 'Warm Amber',
            self::Electric => 'Obsidian Platinum',
        };
    }

    /**
     * Short mood description shown on the theme card.
     */
    public function description(): string
    {
        return match ($this) {
            self::Navy => 'Deep onyx black with rich gold — official brand palette.',
            self::Fairway => 'Light cream with deep amber accents — warm and inviting.',
            self::Electric => 'Obsidian dark with metallic gold and platinum silver.',
        };
    }
}
