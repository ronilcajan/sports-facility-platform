<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';
import SiteCourtCard from '@/components/site/SiteCourtCard.vue';
import BookingModal from '@/components/site/BookingModal.vue';
import { useSite } from '@/composables/useSite';
import { courts as courtsRoute, pricing as pricingRoute } from '@/routes/site';
import type { PublicCourt } from '@/types';

interface HomeContent {
    hero: {
        eyebrow: string;
        title: string;
        subtitle: string;
        primary_cta: string;
        secondary_cta: string;
        stats: { value: string; label: string }[];
    };
    facilities: { title: string; items: { title: string; body: string }[] };
    testimonials: {
        title: string;
        items: { quote: string; name: string; role: string }[];
    };
    cta: { title: string; body: string; button: string };
}

defineProps<{
    content: HomeContent;
    featuredCourts: PublicCourt[];
}>();

const site = useSite();

const activeCourt = ref<PublicCourt | null>(null);
const isBookingOpen = ref(false);

function handleBook(court: PublicCourt) {
    activeCourt.value = court;
    isBookingOpen.value = true;
}
</script>

<template>
    <Head :title="site.tagline">
        <meta name="description" :content="site.description" />
    </Head>

    <!-- 1. Hero Section: Sleek dark-mode sports booking header -->
    <section
        class="relative overflow-hidden bg-surface-inverse pt-16 pb-24 text-content-inverse lg:pt-24 lg:pb-32"
    >
        <!-- Ambience and court line graphics -->
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.05]"
            aria-hidden="true"
        >
            <div
                class="absolute inset-8 rounded-[2rem] border border-content-inverse sm:inset-16"
            ></div>
            <div
                class="absolute inset-x-8 top-1/2 h-px -translate-y-1/2 bg-content-inverse sm:inset-x-16"
            ></div>
            <div
                class="absolute inset-y-8 left-1/2 w-px -translate-x-1/2 bg-content-inverse sm:inset-y-16"
            ></div>
        </div>
        <div
            class="pointer-events-none absolute -top-40 -left-40 size-[500px] rounded-full bg-brand/15 blur-[120px]"
            aria-hidden="true"
        ></div>
        <div
            class="pointer-events-none absolute -right-40 bottom-10 size-[450px] rounded-full bg-highlight/15 blur-[120px]"
            aria-hidden="true"
        ></div>

        <div class="relative mx-auto max-w-6xl px-4 sm:px-6">
            <div class="grid gap-12 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                <!-- Hero Left: Headlines & CTA -->
                <div class="reveal flex flex-col justify-center">
                    <div
                        class="inline-flex max-w-fit items-center gap-2 rounded-full border border-line bg-surface-elevated/40 px-3.5 py-1.5 text-xs font-bold tracking-widest text-brand uppercase"
                    >
                        <span
                            class="size-2 animate-pulse rounded-full bg-brand"
                        ></span>
                        {{ content.hero.eyebrow }}
                    </div>
                    <h1
                        class="xl:text-7.5xl mt-6 font-display text-5xl leading-[1.05] font-black tracking-tight text-balance text-content-inverse sm:text-6xl"
                    >
                        Play Better.<br />
                        <span class="text-brand">Dink Stronger.</span>
                    </h1>
                    <p
                        class="mt-6 max-w-xl text-lg leading-relaxed text-pretty text-content-muted"
                    >
                        {{ content.hero.subtitle }}
                    </p>
                    <div class="mt-8 flex flex-wrap items-center gap-4">
                        <Link
                            :href="courtsRoute()"
                            class="rounded-full bg-brand px-8 py-4 text-base font-bold text-brand-foreground shadow-lg shadow-brand/25 transition-all duration-300 hover:-translate-y-0.5 hover:scale-[1.03] hover:shadow-brand/35"
                        >
                            Book a Court Now
                        </Link>
                        <Link
                            :href="pricingRoute()"
                            class="rounded-full border border-line bg-surface-elevated/35 px-8 py-4 text-base font-semibold text-content-inverse transition-all duration-300 hover:-translate-y-0.5 hover:bg-surface-elevated/60"
                        >
                            See Pricing
                        </Link>
                    </div>
                </div>

                <!-- Hero Right: Dynamic Player Action Visual with Overlay Stats -->
                <div class="relative flex justify-center lg:ml-4">
                    <div
                        class="relative aspect-[4/5] w-full max-w-[420px] overflow-hidden rounded-[var(--site-radius,1.5rem)] border border-line shadow-2xl"
                    >
                        <img
                            src="/images/hero_pickleball.png"
                            alt="Pickleball player swing action"
                            class="h-full w-full object-cover"
                        />
                        <!-- Soft gradient overlay -->
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-surface-inverse/80 via-transparent to-transparent"
                        ></div>
                    </div>

                    <!-- Floating Glassmorphic Stats Card -->
                    <div
                        class="reveal absolute -right-4 -bottom-6 max-w-[200px] rounded-2xl border border-line bg-surface-elevated/85 p-6 shadow-xl backdrop-blur-md transition-all duration-300 hover:scale-[1.02] md:right-4"
                    >
                        <div
                            class="text-3xl leading-none font-black text-brand"
                        >
                            12+
                        </div>
                        <div
                            class="mt-1.5 text-xs font-extrabold tracking-wider text-content-inverse uppercase"
                        >
                            Championship Courts
                        </div>
                        <div class="mt-1 text-xs text-content-muted">
                            Tournament-grade cushioned surfaces
                        </div>
                    </div>
                </div>
            </div>

            <!-- Accent Features Row directly under the Hero content -->
            <div
                class="mt-20 grid gap-4 rounded-2xl border border-line bg-surface-elevated/40 p-2 backdrop-blur-sm sm:grid-cols-3"
            >
                <div
                    class="flex items-center gap-4 rounded-xl p-5 transition-colors hover:bg-surface-elevated/60"
                >
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand"
                    >
                        <!-- Certified Pros Icon -->
                        <svg
                            class="size-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-content-inverse">
                            Certified Instructors
                        </h4>
                        <p class="mt-0.5 text-xs text-content-muted">
                            Learn from pro-level IPTPA trainers.
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-center gap-4 rounded-xl p-5 transition-colors hover:bg-surface-elevated/60"
                >
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand"
                    >
                        <!-- Real-time availability check icon -->
                        <svg
                            class="size-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-content-inverse">
                            Real-Time Booking
                        </h4>
                        <p class="mt-0.5 text-xs text-content-muted">
                            Reserve courts instantly under 60 seconds.
                        </p>
                    </div>
                </div>
                <div
                    class="flex items-center gap-4 rounded-xl p-5 transition-colors hover:bg-surface-elevated/60"
                >
                    <div
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-brand/10 text-brand"
                    >
                        <!-- Skill Levels Icon -->
                        <svg
                            class="size-6"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M13 10V3L4 14h7v7l9-11h-7z"
                            />
                        </svg>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-content-inverse">
                            All Skill Levels
                        </h4>
                        <p class="mt-0.5 text-xs text-content-muted">
                            From daily beginner clinics to 5.0 ladder play.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. "Where Passion Meets Performance" / Features Section -->
    <section
        class="border-b border-line bg-surface py-20 text-content sm:py-24"
    >
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <!-- Headline block -->
            <div
                class="grid items-start gap-6 border-b border-line pb-12 md:grid-cols-[0.9fr_1.1fr]"
            >
                <div>
                    <span
                        class="text-xs font-bold tracking-[0.2em] text-brand uppercase"
                        >OUR VISION</span
                    >
                    <h2
                        class="mt-3 font-display text-4xl leading-tight font-black tracking-tight text-content sm:text-5xl"
                    >
                        Where Passion Meets Performance
                    </h2>
                </div>
                <div>
                    <p
                        class="text-lg leading-relaxed text-pretty text-content-muted"
                    >
                        At Dinkyard, we believe pickleball is more than a
                        game—it's a vibrant lifestyle and community. Our mission
                        is to build confidence, foster friendly competition, and
                        provide Austin with a state-of-the-art facility where
                        players of all capabilities can connect and thrive.
                    </p>
                </div>
            </div>

            <!-- Features Card Grid -->
            <div class="mt-16 grid gap-8 sm:grid-cols-3">
                <!-- Card 1: Booking -->
                <div
                    class="group flex flex-col rounded-2xl border border-line bg-surface-elevated p-8 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lg"
                >
                    <span
                        class="inline-block w-max rounded-full bg-brand/10 px-3 py-1 text-xs font-bold text-brand"
                        >Book Online</span
                    >
                    <h3
                        class="mt-6 font-display text-xl font-extrabold text-content"
                    >
                        Train Smarter
                    </h3>
                    <p class="mt-3 text-sm leading-relaxed text-content-muted">
                        Reserve a cushioned court instantly. Check real-time
                        daily availability charts, select dates, and receive
                        automated check-in details.
                    </p>
                </div>
                <!-- Card 2: Schedule -->
                <div
                    class="group flex flex-col rounded-2xl border border-line bg-surface-elevated p-8 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lg"
                >
                    <span
                        class="inline-block w-max rounded-full bg-brand/10 px-3 py-1 text-xs font-bold text-brand"
                        >Schedule Play</span
                    >
                    <h3
                        class="mt-6 font-display text-xl font-extrabold text-content"
                    >
                        Play Better
                    </h3>
                    <p class="mt-3 text-sm leading-relaxed text-content-muted">
                        Improve your game in structured sessions. Easily manage
                        court assignments, view calendar updates, and enroll in
                        clinics or round-robins.
                    </p>
                </div>
                <!-- Card 3: Payments -->
                <div
                    class="group flex flex-col rounded-2xl border border-line bg-surface-elevated p-8 shadow-sm transition-all duration-300 hover:-translate-y-1.5 hover:shadow-lg"
                >
                    <span
                        class="inline-block w-max rounded-full bg-brand/10 px-3 py-1 text-xs font-bold text-brand"
                        >Seamless Pay</span
                    >
                    <h3
                        class="mt-6 font-display text-xl font-extrabold text-content"
                    >
                        Live Stronger
                    </h3>
                    <p class="mt-3 text-sm leading-relaxed text-content-muted">
                        Enjoy effortless payment processing. Speed through
                        checkout, view your full historical play ledger, and
                        easily manage reservations.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Showcase Section: Dynamically render real featured courts -->
    <section
        class="border-b border-line bg-surface py-20 text-content sm:py-24"
    >
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div
                class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <span
                        class="text-xs font-bold tracking-[0.2em] text-brand uppercase"
                        >THE COURTS</span
                    >
                    <h2
                        class="mt-3 font-display text-3xl font-black tracking-tight text-content sm:text-4xl"
                    >
                        Pick a Court, Grab Your Paddle
                    </h2>
                    <p class="mt-3 max-w-xl text-base text-content-muted">
                        Every court is tournament-grade, cushioned, and floodlit
                        for night play. Select from our available slots below.
                    </p>
                </div>
                <Link
                    :href="courtsRoute()"
                    class="group inline-flex shrink-0 items-center gap-2 text-sm font-bold text-brand transition-colors hover:text-content"
                >
                    See all courts
                    <svg
                        class="size-4 transition-transform group-hover:translate-x-1"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </Link>
            </div>

            <!-- Court Card Showcase Grid -->
            <div class="mt-12 grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                <SiteCourtCard
                    v-for="court in featuredCourts"
                    :key="court.id"
                    :court="court"
                    @book="handleBook"
                />
                <div
                    v-if="featuredCourts.length === 0"
                    class="col-span-full rounded-2xl border border-dashed border-line p-12 text-center text-content-muted"
                >
                    Courts are currently being updated — check back shortly!
                </div>
            </div>
        </div>
    </section>

    <!-- 4. "Programs Designed for You" Section -->
    <section
        class="border-b border-line bg-surface-inverse py-20 text-content-inverse sm:py-24"
    >
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="flex items-end justify-between pb-12">
                <div>
                    <span
                        class="text-xs font-bold tracking-[0.2em] text-brand uppercase"
                        >LEARN & COMPETE</span
                    >
                    <h2
                        class="mt-3 font-display text-3xl font-black tracking-tight text-content-inverse sm:text-4xl"
                    >
                        Programs Designed for You
                    </h2>
                </div>
                <!-- Nav Arrows (Reference Visual) -->
                <div class="hidden gap-2 sm:flex">
                    <button
                        class="flex size-10 items-center justify-center rounded-full border border-line bg-surface-elevated/20 text-content-inverse transition-colors hover:bg-surface-elevated/40"
                        aria-label="Previous"
                    >
                        <svg
                            class="size-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M15 19l-7-7 7-7"
                            />
                        </svg>
                    </button>
                    <button
                        class="flex size-10 items-center justify-center rounded-full border border-line bg-surface-elevated/20 text-content-inverse transition-colors hover:bg-surface-elevated/40"
                        aria-label="Next"
                    >
                        <svg
                            class="size-5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="2"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M9 5l7 7-7 7"
                            />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Programs Card Row -->
            <div class="grid gap-6 sm:grid-cols-3">
                <!-- Program 1 -->
                <div
                    class="group flex flex-col overflow-hidden rounded-[var(--site-radius,1.25rem)] border border-line bg-surface-elevated/40 shadow-md backdrop-blur-sm transition-all duration-300 hover:border-brand/40 hover:shadow-lg"
                >
                    <div class="relative aspect-[16/10] overflow-hidden">
                        <img
                            src="/images/court_pickleball.png"
                            alt="Open play session"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        />
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-surface-inverse/80 via-transparent to-transparent"
                        ></div>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <h3
                            class="font-display text-lg font-extrabold text-content-inverse"
                        >
                            Open Play Rotations
                        </h3>
                        <p
                            class="mt-2 text-xs font-semibold tracking-wider text-content-muted uppercase"
                        >
                            Drop-in • All Skill Levels
                        </p>
                        <p
                            class="mt-3 flex-1 text-sm leading-relaxed text-content-muted"
                        >
                            Join our open play rotates. Meet local players in
                            Austin, test your skills, and rank on our community
                            board.
                        </p>
                        <div
                            class="mt-6 flex items-center justify-between border-t border-line/50 pt-4"
                        >
                            <span class="text-xs font-bold text-brand"
                                >Learn More →</span
                            >
                            <span
                                class="flex size-7 items-center justify-center rounded-full bg-brand text-xs font-black text-brand-foreground shadow transition-transform group-hover:translate-x-1"
                                >→</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Program 2 -->
                <div
                    class="group flex flex-col overflow-hidden rounded-[var(--site-radius,1.25rem)] border border-line bg-surface-elevated/40 shadow-md backdrop-blur-sm transition-all duration-300 hover:border-brand/40 hover:shadow-lg"
                >
                    <div class="relative aspect-[16/10] overflow-hidden">
                        <img
                            src="/images/hero_pickleball.png"
                            alt="Clinics & Lessons"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        />
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-surface-inverse/80 via-transparent to-transparent"
                        ></div>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <h3
                            class="font-display text-lg font-extrabold text-content-inverse"
                        >
                            Clinics & Lessons
                        </h3>
                        <p
                            class="mt-2 text-xs font-semibold tracking-wider text-content-muted uppercase"
                        >
                            Coaching • Certified Pros
                        </p>
                        <p
                            class="mt-3 flex-1 text-sm leading-relaxed text-content-muted"
                        >
                            Work on your third-shot drops, speed ups, and
                            kitchen control drills with private or small-group
                            clinics.
                        </p>
                        <div
                            class="mt-6 flex items-center justify-between border-t border-line/50 pt-4"
                        >
                            <span class="text-xs font-bold text-brand"
                                >Learn More →</span
                            >
                            <span
                                class="flex size-7 items-center justify-center rounded-full bg-brand text-xs font-black text-brand-foreground shadow transition-transform group-hover:translate-x-1"
                                >→</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Program 3 -->
                <div
                    class="group flex flex-col overflow-hidden rounded-[var(--site-radius,1.25rem)] border border-line bg-surface-elevated/40 shadow-md backdrop-blur-sm transition-all duration-300 hover:border-brand/40 hover:shadow-lg"
                >
                    <div class="relative aspect-[16/10] overflow-hidden">
                        <img
                            src="/images/cta_pickleball.png"
                            alt="Leagues & Tournaments"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        />
                        <div
                            class="absolute inset-0 bg-gradient-to-t from-surface-inverse/80 via-transparent to-transparent"
                        ></div>
                    </div>
                    <div class="flex flex-1 flex-col p-6">
                        <h3
                            class="font-display text-lg font-extrabold text-content-inverse"
                        >
                            Tournament play
                        </h3>
                        <p
                            class="mt-2 text-xs font-semibold tracking-wider text-content-muted uppercase"
                        >
                            Leagues • Competitive
                        </p>
                        <p
                            class="mt-3 flex-1 text-sm leading-relaxed text-content-muted"
                        >
                            Register with a partner for monthly tournament
                            events, local challenge ladders, and end-of-season
                            championships.
                        </p>
                        <div
                            class="mt-6 flex items-center justify-between border-t border-line/50 pt-4"
                        >
                            <span class="text-xs font-bold text-brand"
                                >Learn More →</span
                            >
                            <span
                                class="flex size-7 items-center justify-center rounded-full bg-brand text-xs font-black text-brand-foreground shadow transition-transform group-hover:translate-x-1"
                                >→</span
                            >
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 5. "How It Works" Section -->
    <section
        class="border-b border-line bg-surface py-20 text-content sm:py-24"
    >
        <div class="mx-auto max-w-6xl px-4 sm:px-6">
            <div class="mx-auto max-w-2xl text-center">
                <span
                    class="text-xs font-bold tracking-[0.2em] text-brand uppercase"
                    >EASY BOOKING PROCESS</span
                >
                <h2
                    class="mt-3 font-display text-3xl font-black tracking-tight text-content sm:text-4xl"
                >
                    How It Works
                </h2>
                <p class="mt-3 text-base text-content-muted">
                    Getting on the court is quick and effortless. Follow these
                    simple steps.
                </p>
            </div>

            <!-- Stepper Grid Layout -->
            <div class="relative mt-16 grid gap-8 md:grid-cols-3">
                <!-- Step 1 -->
                <div
                    class="relative flex flex-col items-center rounded-2xl border border-line bg-surface-elevated p-6 text-center"
                >
                    <div
                        class="flex size-14 items-center justify-center rounded-full bg-brand text-xl font-black text-brand-foreground shadow-md shadow-brand/20"
                    >
                        1
                    </div>
                    <h3
                        class="mt-6 font-display text-lg font-bold text-content"
                    >
                        Choose a Court
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-content-muted">
                        Browse our premium cushioned outdoor courts, review
                        location details, and choose your favorite.
                    </p>
                </div>
                <!-- Step 2 -->
                <div
                    class="relative flex flex-col items-center rounded-2xl border border-line bg-surface-elevated p-6 text-center"
                >
                    <div
                        class="flex size-14 items-center justify-center rounded-full bg-brand text-xl font-black text-brand-foreground shadow-md shadow-brand/20"
                    >
                        2
                    </div>
                    <h3
                        class="mt-6 font-display text-lg font-bold text-content"
                    >
                        Select Date & Time
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-content-muted">
                        Check real-time availability on our interactive calendar
                        charts, and select an open time slot.
                    </p>
                </div>
                <!-- Step 3 -->
                <div
                    class="relative flex flex-col items-center rounded-2xl border border-line bg-surface-elevated p-6 text-center"
                >
                    <div
                        class="flex size-14 items-center justify-center rounded-full bg-brand text-xl font-black text-brand-foreground shadow-md shadow-brand/20"
                    >
                        3
                    </div>
                    <h3
                        class="mt-6 font-display text-lg font-bold text-content"
                    >
                        Confirm Booking
                    </h3>
                    <p class="mt-2 text-sm leading-relaxed text-content-muted">
                        Complete check out, receive instant confirmation, and
                        access courts with a simple check-in QR code.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 6. Call to Action: Dramatic background overlay banner -->
    <section
        class="relative overflow-hidden bg-surface-inverse py-28 text-content-inverse sm:py-32"
    >
        <!-- Visual Background Image -->
        <div class="absolute inset-0 z-0">
            <img
                src="/images/cta_pickleball.png"
                alt="Pickleball courts at sunset"
                class="h-full w-full object-cover opacity-35"
            />
            <!-- Dark Gradient overlay -->
            <div
                class="absolute inset-0 bg-gradient-to-r from-surface-inverse via-surface-inverse/85 to-transparent"
            ></div>
        </div>

        <div class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6">
            <div class="max-w-2xl">
                <span
                    class="text-xs font-bold tracking-[0.2em] text-brand uppercase"
                    >GET ON THE COURT</span
                >
                <h2
                    class="mt-4 font-display text-4xl leading-tight font-black tracking-tight text-content-inverse sm:text-5xl"
                >
                    {{ content.cta.title }}
                </h2>
                <p class="mt-4 text-lg leading-relaxed text-content-muted">
                    {{ content.cta.body }}
                </p>
                <div class="mt-8">
                    <Link
                        :href="courtsRoute()"
                        class="inline-flex rounded-full bg-brand px-8 py-4 text-base font-bold text-brand-foreground shadow-lg shadow-brand/25 transition-all duration-300 hover:-translate-y-0.5 hover:scale-[1.03] hover:shadow-brand/35"
                    >
                        {{ content.cta.button }}
                    </Link>
                </div>
            </div>
        </div>
    </section>

    <!-- Booking Modal Component overlay -->
    <BookingModal
        :court="activeCourt"
        :is-open="isBookingOpen"
        @close="isBookingOpen = false"
    />
</template>
