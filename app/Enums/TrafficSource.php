<?php

namespace App\Enums;

enum TrafficSource: string
{
    case Direct = 'direct';
    case Search = 'search';
    case Social = 'social';
    case Referral = 'referral';

    /**
     * Classify a referrer URL relative to the site's own host.
     *
     * A missing referrer, or one pointing back at this site, counts as direct
     * traffic — internal navigation should not inflate the referral bucket.
     */
    public static function fromReferrer(?string $referrer, ?string $selfHost = null): self
    {
        $host = strtolower((string) parse_url((string) $referrer, PHP_URL_HOST));

        if ($host === '' || ($selfHost !== null && $host === strtolower($selfHost))) {
            return self::Direct;
        }

        if (preg_match('/google|bing|yahoo|duckduckgo|baidu|yandex|ecosia|brave/i', $host) === 1) {
            return self::Search;
        }

        if (preg_match('/facebook|fb\.|instagram|twitter|x\.com|t\.co|tiktok|linkedin|pinterest|reddit|youtube|whatsapp|messenger|threads/i', $host) === 1) {
            return self::Social;
        }

        return self::Referral;
    }

    /**
     * Human-readable name for the analytics panel.
     */
    public function label(): string
    {
        return match ($this) {
            self::Direct => 'Direct Traffic',
            self::Search => 'Google / Search',
            self::Social => 'Social Media',
            self::Referral => 'Referral Links',
        };
    }

    /**
     * Colour key consumed by the sources breakdown list.
     */
    public function color(): string
    {
        return match ($this) {
            self::Direct => 'emerald',
            self::Search => 'teal',
            self::Social => 'violet',
            self::Referral => 'amber',
        };
    }
}
