<?php

namespace App\Enums;

enum SportType: string
{
    case Pickleball = 'pickleball';
    case Tennis = 'tennis';
    case Badminton = 'badminton';
    case Padel = 'padel';
    case Basketball = 'basketball';
    case Volleyball = 'volleyball';
    case Squash = 'squash';
    case Futsal = 'futsal';
    case MeetingRoom = 'meeting-room';

    /**
     * All sport type values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $type): string => $type->value, self::cases());
    }

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pickleball => 'Pickleball',
            self::Tennis => 'Tennis',
            self::Badminton => 'Badminton',
            self::Padel => 'Padel',
            self::Basketball => 'Basketball',
            self::Volleyball => 'Volleyball',
            self::Squash => 'Squash',
            self::Futsal => 'Futsal',
            self::MeetingRoom => 'Meeting Room',
        };
    }
}
