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
    'name' => 'PickleBall',
    'tagline' => 'Fun pickleball for everyone.',
    'description' => 'A friendly pickleball spot with welcoming courts and open play — book a court in under a minute.',

    'contact' => [
        'email' => 'play@pickleball.test',
        'phone' => '(088) 555-0142',
        'address_line' => 'Poblacion, Oroquieta City, Misamis Occidental',
        'maps_query' => 'Oroquieta City, Misamis Occidental',
    ],

    'hours' => [
        ['day' => 'Every day', 'value' => '7:00 AM – 2:00 AM'],
    ],

    'social' => [
        ['label' => 'Facebook', 'url' => 'https://facebook.com'],
    ],

    /*
     | Primary navigation for the public header + footer. `name` maps to a
     | Laravel route name so links stay correct if paths change.
     */
    'nav' => [
        ['label' => 'About', 'route' => 'site.about'],
        ['label' => 'Venue', 'route' => 'site.courts'],
        ['label' => 'Gallery', 'route' => 'site.gallery'],
    ],

    'legal' => [
        ['label' => 'Privacy Policy', 'route' => 'site.privacy'],
        ['label' => 'Terms of Service', 'route' => 'site.terms'],
    ],
];
