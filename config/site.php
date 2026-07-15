<?php

/*
|--------------------------------------------------------------------------
| Public Site Identity & Chrome
|--------------------------------------------------------------------------
|
| Static source of truth for the marketing site's business identity, contact
| details, navigation, and social links. This is intentionally centralized so
| a later CMS sub-project can swap this array out for database-backed content
| without touching the Vue layer.
|
*/

return [
    'name' => 'Dinkyard',
    'tagline' => 'Premium pickleball, open to everyone.',
    'description' => 'A premium pickleball facility with pro-grade courts, coaching, and open play — book a court in under a minute.',

    'contact' => [
        'email' => 'play@dinkyard.test',
        'phone' => '(512) 555-0142',
        'address_line' => '2200 Baseline Ave, Austin, TX 78704',
        'maps_query' => '2200 Baseline Ave, Austin, TX 78704',
    ],

    'hours' => [
        ['day' => 'Monday – Friday', 'value' => '6:00 AM – 11:00 PM'],
        ['day' => 'Saturday', 'value' => '7:00 AM – 11:00 PM'],
        ['day' => 'Sunday', 'value' => '7:00 AM – 9:00 PM'],
    ],

    'social' => [
        ['label' => 'Instagram', 'url' => 'https://instagram.com'],
        ['label' => 'Facebook', 'url' => 'https://facebook.com'],
        ['label' => 'YouTube', 'url' => 'https://youtube.com'],
    ],

    /*
     | Primary navigation for the public header + footer. `name` maps to a
     | Laravel route name so links stay correct if paths change.
     */
    'nav' => [
        ['label' => 'Venue', 'route' => 'site.courts'],
        ['label' => 'Pricing', 'route' => 'site.pricing'],
        ['label' => 'Gallery', 'route' => 'site.gallery'],
        ['label' => 'About', 'route' => 'site.about'],
    ],

    'legal' => [
        ['label' => 'Privacy Policy', 'route' => 'site.privacy'],
        ['label' => 'Terms of Service', 'route' => 'site.terms'],
    ],
];
