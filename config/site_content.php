<?php

/*
|--------------------------------------------------------------------------
| Public Site Page Content
|--------------------------------------------------------------------------
|
| Per-page marketing copy for the static-first build. Kept separate from
| config/site.php (identity/chrome) so the eventual CMS can replace page
| bodies independently. Every string here is end-user facing.
|
*/

return [
    'home' => [
        'hero' => [
            'eyebrow' => 'Oroquieta City · Open play daily',
            'title' => 'Find your court. Play your game.',
            'subtitle' => 'Welcoming pickleball courts, easy real-time booking, and open play from morning to midnight. Whether it\'s your first game or your five-hundredth, come have fun — no membership needed.',
            'primary_cta' => 'Book a court',
            'secondary_cta' => 'See open play',
            'stats' => [
                ['value' => '12', 'label' => 'Courts'],
                ['value' => '19h', 'label' => 'Open every day'],
                ['value' => '60s', 'label' => 'Average booking time'],
            ],
        ],
        'facilities' => [
            'title' => 'Everything you need for a great, fun game',
            'items' => [
                ['title' => 'Book in seconds', 'body' => 'Pick a court and time online — no calls, no waiting, no membership.'],
                ['title' => 'Evening play', 'body' => 'Well-lit courts so you can play after work, too.'],
                ['title' => 'Bring your crew', 'body' => 'Courts fit up to four players — same easy price whether it\'s two or four.'],
                ['title' => 'All levels welcome', 'body' => 'First-timers and long-time players share the same friendly courts.'],
                ['title' => 'Relax between games', 'body' => 'A shaded spot and water to catch your breath between matches.'],
                ['title' => 'Easy parking', 'body' => 'On-site parking just steps from the courts.'],
            ],
        ],
        'testimonials' => [
            'title' => 'Loved by players of every level',
            'items' => [
                ['quote' => 'Such a fun, welcoming place. I book my Tuesday games here every week and always leave smiling.', 'name' => 'Marisol T.', 'role' => 'Plays for fun'],
                ['quote' => 'Booked my first court in about a minute, showed up, and the QR check-in just worked. Effortless.', 'name' => 'Devin K.', 'role' => 'Weekend player'],
                ['quote' => 'We started coming as a family and now it\'s our favorite weekend spot. Everyone\'s so friendly.', 'name' => 'The Alvarez family', 'role' => 'Open play regulars'],
            ],
        ],
        'cta' => [
            'title' => 'Your court is waiting.',
            'body' => 'Pick a time, grab your paddle, and play. Booking takes less than a minute.',
            'button' => 'Book a court',
        ],
    ],

    'about' => [
        'title' => 'A happy place to play, for everyone.',
        'lede' => 'It started with a few friends, a couple of paddles, and a long drive to the nearest court. So we built our own — a fun, welcoming place to play, open to everyone.',
        'body' => [
            'What began as a weekend hobby became a simple goal: give Oroquieta City a friendly pickleball home. No worn-out gym floors, no hour-long waits for a court, no membership gatekeeping — just good courts and good vibes.',
            'Every surface, light, and net here was picked by people who love the game. We keep things easygoing and welcoming so you can just enjoy your time on court.',
            'Whether you picked up a paddle last week or you have been playing for years, there is a court and a friendly community here for you.',
        ],
        'values' => [
            ['title' => 'Open to everyone', 'body' => 'Guest booking, no membership required, and clinics for every age and level.'],
            ['title' => 'Well-kept courts', 'body' => 'Great surfaces and lighting, cared for daily.'],
            ['title' => 'Good vibes only', 'body' => 'We keep courts fair, on-time, and welcoming so play stays fun for all.'],
        ],
    ],

    'pricing' => [
        'title' => 'Simple pricing. No membership required.',
        'lede' => 'Pay per court, per hour. Bring up to four players — the price is the same.',
        'tiers' => [
            [
                'name' => 'Off-Peak',
                'price' => '$18',
                'unit' => 'per court · hour',
                'note' => 'Weekdays before 4 PM',
                'features' => ['Any open court', 'Up to 4 players', 'Free parking', 'QR check-in'],
                'featured' => false,
            ],
            [
                'name' => 'Prime Time',
                'price' => '$28',
                'unit' => 'per court · hour',
                'note' => 'Evenings & weekends',
                'features' => ['Any open court', 'Up to 4 players', 'Priority lighting', 'QR check-in', 'Free reschedule'],
                'featured' => true,
            ],
            [
                'name' => 'Open Play',
                'price' => '$12',
                'unit' => 'per player · session',
                'note' => 'Drop-in rotation',
                'features' => ['2-hour rotation', 'Meet new players', 'All levels welcome', 'Paddles available'],
                'featured' => false,
            ],
        ],
    ],

    'gallery' => [
        'title' => 'A look around our courts',
        'lede' => 'Snapshots of our venues and courts — come see where you\'ll play.',
        'items' => [
            ['label' => 'Center court', 'tone' => 'court'],
            ['label' => 'Evening lights', 'tone' => 'ink'],
            ['label' => 'Open play', 'tone' => 'volt'],
            ['label' => 'The lounge', 'tone' => 'court'],
            ['label' => 'Paddle shop', 'tone' => 'ink'],
            ['label' => 'Clinic in session', 'tone' => 'volt'],
        ],
    ],

    'privacy' => [
        'title' => 'Privacy Policy',
        'updated' => 'Last updated July 6, 2026',
        'sections' => [
            ['heading' => 'What we collect', 'body' => 'We collect the information you provide when you book a court or create an account — your name, email, and phone number — plus basic booking and payment records needed to run your reservation.'],
            ['heading' => 'How we use it', 'body' => 'We use your information to confirm bookings, send reminders and receipts, and improve the facility experience. We do not sell your personal information.'],
            ['heading' => 'Cookies', 'body' => 'We use essential cookies to keep you signed in and remember your preferences. You can control cookies through your browser settings.'],
            ['heading' => 'Your choices', 'body' => 'You can request a copy of your data or ask us to delete your account at any time by contacting us at the address on our contact page.'],
        ],
    ],

    'terms' => [
        'title' => 'Terms of Service',
        'updated' => 'Last updated July 6, 2026',
        'sections' => [
            ['heading' => 'Bookings', 'body' => 'A confirmed booking reserves the specified court for the specified time. Please arrive on time; courts are released to the next player after the grace period shown at checkout.'],
            ['heading' => 'Conduct', 'body' => 'Play safely and respectfully. We may end a session or decline service for behavior that endangers players or damages the facility.'],
            ['heading' => 'Payments & refunds', 'body' => 'Payment is due per the option selected at checkout. Refunds and reschedules follow the cancellation policy shown when you book.'],
            ['heading' => 'Liability', 'body' => 'Pickleball is a physical activity with inherent risks. By playing at our facility you accept responsibility for your own participation to the extent permitted by law.'],
        ],
    ],
];
