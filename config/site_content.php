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
            'eyebrow' => 'Austin · Open play daily',
            'title' => 'Find your court. Play your game.',
            'subtitle' => 'Twelve pro-grade pickleball courts, real-time booking, and open play from dawn to midnight. Reserve in under a minute — no membership required.',
            'primary_cta' => 'Book a court',
            'secondary_cta' => 'See open play',
            'stats' => [
                ['value' => '12', 'label' => 'Championship courts'],
                ['value' => '17h', 'label' => 'Open every day'],
                ['value' => '60s', 'label' => 'Average booking time'],
            ],
        ],
        'facilities' => [
            'title' => 'Built for players who care about the details',
            'items' => [
                ['title' => 'Cushioned pro courts', 'body' => 'Post-tensioned concrete with a cushioned acrylic surface — true bounce, easy on the knees.'],
                ['title' => 'Nightlight play', 'body' => 'Glare-free LED lighting rated for tournament play, so your evening game looks like noon.'],
                ['title' => 'Paddle & ball shop', 'body' => 'Demo the latest paddles, grab fresh Franklin X-40s, and restring on site.'],
                ['title' => 'Coaching & clinics', 'body' => 'Certified pros run beginner clinics, drill sessions, and private lessons every week.'],
                ['title' => 'Cold towels & hydration', 'body' => 'Filtered water stations, electrolytes, and a shaded lounge between games.'],
                ['title' => 'Easy parking', 'body' => 'Free on-site parking and covered bike racks steps from the courts.'],
            ],
        ],
        'testimonials' => [
            'title' => 'Loved by the local pickleball scene',
            'items' => [
                ['quote' => 'The surface and lighting are a step above anywhere else in town. I book my Tuesday games here every week.', 'name' => 'Marisol T.', 'role' => '4.0 competitive'],
                ['quote' => 'Booked my first court in about a minute, showed up, and the QR check-in just worked. Effortless.', 'name' => 'Devin K.', 'role' => 'Weekend player'],
                ['quote' => 'The beginner clinic got my whole family playing. Now we come three times a week.', 'name' => 'The Alvarez family', 'role' => 'Open play regulars'],
            ],
        ],
        'cta' => [
            'title' => 'Your court is waiting.',
            'body' => 'Pick a time, grab your paddle, and play. Booking takes less than a minute.',
            'button' => 'Book a court',
        ],
    ],

    'about' => [
        'title' => 'We built the court we always wanted to play on.',
        'lede' => 'Dinkyard started with four friends, two paddles, and a long drive to the nearest decent court. So we built our own — and opened it to everyone.',
        'body' => [
            'What began as a weekend obsession became a mission: give Austin a pickleball home that takes the game as seriously as its players do. No worn-out gym floors, no waiting an hour for an open court, no membership gatekeeping.',
            'Every surface, light, and net at Dinkyard was chosen by people who actually play. We obsess over bounce consistency and glare so you can obsess over your third-shot drop.',
            'Whether you picked up a paddle last week or you are chasing a 5.0 rating, there is a court and a community here for you.',
        ],
        'values' => [
            ['title' => 'Open to everyone', 'body' => 'Guest booking, no membership required, and clinics for every level.'],
            ['title' => 'Pro-grade or nothing', 'body' => 'Tournament surfaces and lighting, maintained daily.'],
            ['title' => 'Respect the game', 'body' => 'We keep courts fair, on-time, and in shape so play stays great.'],
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
        'title' => 'A look around the yard',
        'lede' => 'Twelve courts, one very good place to spend an evening.',
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
