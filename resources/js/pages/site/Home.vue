<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Check, Sparkles } from '@lucide/vue';
import { ref } from 'vue';
import BookingModal from '@/components/site/BookingModal.vue';
import SiteCourtCard from '@/components/site/SiteCourtCard.vue';
import SiteVenueCard from '@/components/site/SiteVenueCard.vue';
import type { CatalogVenue } from '@/components/site/SiteVenueCard.vue';
import { useSite } from '@/composables/useSite';
import { about as aboutRoute, courts as courtsRoute } from '@/routes/site';
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
    venues?: CatalogVenue[];
    featuredCourts: PublicCourt[];
    courtsCount: number;
}>();

const site = useSite();
const activeCourt = ref<PublicCourt | null>(null);
const activeVenue = ref<CatalogVenue | null>(null);
const isBookingOpen = ref(false);

function openBooking(
    court: PublicCourt | null = null,
    venue: CatalogVenue | null = null,
): void {
    activeCourt.value = court;
    activeVenue.value = venue;
    isBookingOpen.value = true;
}

function viewLocation(location: CatalogVenue): void {
    router.get('/courts', { venue: location.id });
}
</script>

<template>
    <Head :title="site.tagline"
        ><meta name="description" :content="site.description"
    /></Head>

    <main class="min-h-screen overflow-hidden bg-surface text-content">
        <section class="relative border-b border-line">
            <img
                src="/images/hero_pickleball.png"
                alt=""
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 size-full object-cover object-center opacity-45"
            />
            <div
                class="pointer-events-none absolute inset-0 bg-gradient-to-b from-surface/88 via-surface/76 to-surface/92"
            />
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_25%_0%,rgba(47,197,127,0.2),transparent_28%),radial-gradient(circle_at_75%_20%,rgba(183,216,63,0.14),transparent_26%)]"
            />
            <div
                class="relative mx-auto max-w-5xl px-4 py-20 text-center sm:px-6 sm:py-28"
            >
                <div
                    class="inline-flex items-center gap-2 rounded-md border border-brand/25 bg-brand/10 px-3 py-1 text-[10px] font-bold tracking-[0.16em] text-brand uppercase"
                >
                    <Sparkles class="size-3" />{{ content.hero.eyebrow }}
                </div>
                <h1
                    class="mx-auto mt-6 max-w-3xl font-display text-5xl leading-[0.98] font-black tracking-[-0.055em] sm:text-6xl lg:text-7xl"
                >
                    Book better.<br /><span
                        class="bg-[linear-gradient(90deg,#2fc57f,#b7d83f,#35b978)] bg-clip-text text-transparent"
                        >Play more.</span
                    >
                </h1>
                <p
                    class="mx-auto mt-5 max-w-2xl text-sm leading-6 text-content-muted sm:text-base"
                >
                    {{ content.hero.subtitle }}
                </p>
                <button
                    type="button"
                    class="mt-7 inline-flex w-full max-w-sm items-center gap-2 rounded-md border border-line bg-surface-elevated px-4 py-3 text-left font-mono text-xs text-content shadow-lg shadow-black/20 hover:border-brand/50"
                    @click="openBooking()"
                >
                    <span class="text-brand">›</span> Find an available
                    court<span class="ml-auto text-content-muted">⌘ B</span>
                </button>
                <div class="mt-5 flex justify-center gap-3">
                    <Link
                        :href="courtsRoute()"
                        class="inline-flex items-center gap-2 rounded-md bg-brand px-4 py-2.5 text-sm font-extrabold text-brand-foreground transition-transform hover:-translate-y-0.5"
                        >{{ content.hero.primary_cta
                        }}<ArrowRight class="size-4" /></Link
                    ><Link
                        :href="aboutRoute()"
                        class="rounded-md border border-line bg-surface-elevated px-4 py-2.5 text-sm font-bold hover:border-brand/50"
                        >{{ content.hero.secondary_cta }}</Link
                    >
                </div>
                <p class="mt-5 text-xs text-content-muted">
                    <span class="text-brand">✓</span> No membership required
                    <span class="mx-2">•</span
                    ><span class="text-brand">✓</span> Live availability
                </p>
            </div>
        </section>

        <section class="mx-auto max-w-5xl px-4 pt-14 pb-20 sm:px-6 sm:pt-16">
            <div class="flex flex-col gap-3 text-center">
                <p
                    class="text-[11px] font-bold tracking-[0.16em] text-brand uppercase"
                >
                    Our Venues
                </p>
                <h2 class="font-display text-3xl font-black sm:text-4xl">
                    Choose where you play.
                </h2>
                <p class="text-sm text-content-muted">
                    Explore courts, schedules, and live availability by
                    location.
                </p>
            </div>
            <div
                v-if="venues?.length"
                class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-3"
            >
                <SiteVenueCard
                    v-for="location in venues"
                    :key="location.id"
                    :venue="location"
                    @book-now="openBooking(null, location)"
                    @view-courts="viewLocation"
                />
            </div>
            <div v-else class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <SiteCourtCard
                    v-for="court in featuredCourts"
                    :key="court.id"
                    :court="court"
                    @book="openBooking(court)"
                />
            </div>
        </section>

        <section
            class="border-y border-line bg-surface-inverse py-20 text-content-inverse"
        >
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div class="text-center">
                    <p
                        class="text-[11px] font-bold tracking-[0.16em] text-brand uppercase"
                    >
                        Everything you need
                    </p>
                    <h2
                        class="mt-2 font-display text-3xl font-black sm:text-4xl"
                    >
                        A smoother way to get on court.
                    </h2>
                    <p
                        class="mx-auto mt-3 max-w-xl text-sm text-content-inverse/70"
                    >
                        Every part of the experience is designed around finding
                        a court and playing without friction.
                    </p>
                </div>
                <div class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="item in content.facilities.items"
                        :key="item.title"
                        class="rounded-lg border border-line bg-surface-elevated p-5 text-content transition-colors hover:border-brand/50"
                    >
                        <span
                            class="flex size-8 items-center justify-center rounded-md bg-brand/10 text-brand"
                            ><Check class="size-4"
                        /></span>
                        <h3 class="mt-4 text-sm font-black">
                            {{ item.title }}
                        </h3>
                        <p class="mt-2 text-xs leading-5 text-content-muted">
                            {{ item.body }}
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section
            class="border-y border-line bg-surface-inverse py-20 text-content-inverse"
        >
            <div class="mx-auto max-w-3xl px-4 sm:px-6">
                <div class="text-center">
                    <p
                        class="text-[11px] font-bold tracking-[0.16em] text-brand uppercase"
                    >
                        How it works
                    </p>
                    <h2 class="mt-2 font-display text-3xl font-black">
                        From search to serve.
                    </h2>
                </div>
                <ol class="mt-10 space-y-3">
                    <li
                        v-for="(step, index) in [
                            'Choose a location and a court.',
                            'Pick an open date and time slot.',
                            'Confirm your booking and play.',
                        ]"
                        :key="step"
                        class="rounded-lg border border-line bg-surface-elevated p-5 text-content"
                    >
                        <div class="flex items-start gap-4">
                            <span
                                class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand text-xs font-black text-brand-foreground"
                                >{{ index + 1 }}</span
                            >
                            <div>
                                <p class="text-sm font-bold">{{ step }}</p>
                                <p class="mt-1 text-xs text-content-muted">
                                    Simple, clear choices with real-time
                                    availability at every step.
                                </p>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>
        </section>

        <section
            class="border-t border-line bg-[radial-gradient(circle_at_30%_100%,rgba(47,197,127,0.2),transparent_35%),radial-gradient(circle_at_70%_100%,rgba(183,216,63,0.14),transparent_35%)] py-24"
        >
            <div class="mx-auto max-w-3xl px-4 text-center sm:px-6">
                <p
                    class="text-[11px] font-bold tracking-[0.16em] text-brand uppercase"
                >
                    Ready when you are
                </p>
                <h2 class="mt-3 font-display text-4xl font-black sm:text-5xl">
                    {{ content.cta.title }}
                </h2>
                <p
                    class="mx-auto mt-4 max-w-xl text-sm leading-6 text-content-muted"
                >
                    {{ content.cta.body }}
                </p>
                <Link
                    :href="courtsRoute()"
                    class="mt-7 inline-flex items-center gap-2 rounded-md bg-brand px-5 py-3 text-sm font-extrabold text-brand-foreground"
                    >{{ content.cta.button }}<ArrowRight class="size-4"
                /></Link>
            </div>
        </section>
    </main>

    <BookingModal
        :court="activeCourt"
        :venue="activeVenue"
        :venues="venues"
        :is-open="isBookingOpen"
        @close="isBookingOpen = false"
    />
</template>
