<?php

namespace App\Enums;

enum DeviceType: string
{
    case Desktop = 'desktop';
    case Mobile = 'mobile';
    case Tablet = 'tablet';

    /**
     * Classify a raw user-agent string.
     *
     * Tablets are checked before phones because most tablet user-agents also
     * contain "Mobile" (iPad reports "Mobile/15E148" for example).
     */
    public static function fromUserAgent(?string $userAgent): self
    {
        $agent = strtolower($userAgent ?? '');

        if ($agent === '') {
            return self::Desktop;
        }

        if (preg_match('/ipad|tablet|playbook|silk|kindle|(android(?!.*mobile))/i', $agent) === 1) {
            return self::Tablet;
        }

        if (preg_match('/mobile|iphone|ipod|android|blackberry|opera mini|iemobile|windows phone/i', $agent) === 1) {
            return self::Mobile;
        }

        return self::Desktop;
    }

    /**
     * Human-readable name for the analytics panel.
     */
    public function label(): string
    {
        return match ($this) {
            self::Desktop => 'Desktop / PC',
            self::Mobile => 'Mobile Phones',
            self::Tablet => 'Tablets & Other',
        };
    }

    /**
     * Tailwind background class used by the breakdown bars.
     */
    public function color(): string
    {
        return match ($this) {
            self::Desktop => 'bg-emerald-500',
            self::Mobile => 'bg-teal-500',
            self::Tablet => 'bg-amber-500',
        };
    }
}
