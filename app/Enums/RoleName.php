<?php

namespace App\Enums;

enum RoleName: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Staff = 'staff';
    case Customer = 'customer';

    /**
     * All role names as their string values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $role): string => $role->value, self::cases());
    }

    /**
     * Human-readable label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Staff => 'Staff',
            self::Customer => 'Customer',
        };
    }
}
