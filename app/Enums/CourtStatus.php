<?php

namespace App\Enums;

enum CourtStatus: string
{
    case Available = 'available';
    case Maintenance = 'maintenance';
    case Closed = 'closed';

    /**
     * All court status values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $status): string => $status->value, self::cases());
    }

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Available => 'Available',
            self::Maintenance => 'Maintenance',
            self::Closed => 'Closed',
        };
    }

    /**
     * Whether courts in this status accept new bookings.
     */
    public function isBookable(): bool
    {
        return $this === self::Available;
    }
}
