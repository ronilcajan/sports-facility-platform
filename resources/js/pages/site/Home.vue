<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import SiteCourtCard from '@/components/site/SiteCourtCard.vue';
import SiteSection from '@/components/site/SiteSection.vue';
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
</script>

<template>
    <Head :title="site.tagline">
        <meta name="description" :content="site.description" />
    </Head>

    <!-- Hero: the court is the canvas, tinted to the active theme. -->
    <section class="relative overflow-hidden bg-surface text-content">
        <!-- Court-line motif, adapts to the surface. -->
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.08]"
            aria-hidden="true"
        >
            <div class="absolute inset-8 rounded-xl border-2 border-content sm:inset-16" />
            <div
                class="absolute inset-x-8 top-1/2 h-0.5 -translate-y-1/2 bg-content sm:inset-x-16"
            />
        </div>
        <!-- Ambient brand + highlight glow. -->
        <div
            class="pointer-events-none absolute -top-32 -left-24 size-96 rounded-full bg-brand/25 blur-3xl"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute -right-24 bottom-0 size-80 rounded-full bg-highlight/20 blur-3xl"
            aria-hidden="true"
        />

        <div
            class="relative mx-auto grid max-w-6xl gap-12 px-4 pt-20 pb-24 sm:px-6 lg:grid-cols-[1.15fr_0.85fr] lg:items-center"
        >
            <div class="reveal">
                <p
                    class="text-xs font-semibold tracking-[0.2em] text-brand uppercase"
                >
                    {{ content.hero.eyebrow }}
                </p>
                <h1
                    class="mt-5 font-display text-5xl font-extrabold tracking-tight text-balance sm:text-6xl lg:text-7xl"
                >
                    {{ content.hero.title }}
                </h1>
                <p class="mt-6 max-w-xl text-lg text-content-muted text-pretty">
                    {{ content.hero.subtitle }}
                </p>
                <div class="mt-9 flex flex-wrap items-center gap-4">
                    <Link
                        :href="courtsRoute()"
                        class="rounded-full bg-brand px-7 py-3 text-base font-semibold text-brand-foreground transition-transform hover:-translate-y-0.5"
                    >
                        {{ content.hero.primary_cta }}
                    </Link>
                    <Link
                        :href="pricingRoute()"
                        class="rounded-full border border-line px-7 py-3 text-base font-semibold text-content transition-colors hover:bg-content/5"
                    >
                        {{ content.hero.secondary_cta }}
                    </Link>
                </div>
            </div>

            <!-- Signature: a brand→highlight orb serving in above the stat trio. -->
            <div class="relative">
                <div
                    class="ball-serve mx-auto size-24 rounded-full bg-gradient-to-br from-brand to-highlight shadow-lg sm:size-28"
                    aria-hidden="true"
                />
                <dl
                    class="mt-10 grid grid-cols-3 gap-4 rounded-2xl border border-line bg-surface-elevated/70 p-6 backdrop-blur"
                >
                    <div
                        v-for="stat in content.hero.stats"
                        :key="stat.label"
                        class="text-center"
                    >
                        <dt class="sr-only">{{ stat.label }}</dt>
                        <dd class="font-display text-3xl font-extrabold text-brand">
                            {{ stat.value }}
                        </dd>
                        <p class="mt-1 text-xs text-content-muted">{{ stat.label }}</p>
                    </div>
                </dl>
            </div>
        </div>
        <div class="kitchen-line text-brand/50" aria-hidden="true" />
    </section>

    <!-- Featured courts, real data. -->
    <SiteSection
        eyebrow="The courts"
        title="Pick a court, grab your paddle"
        lede="Every court is tournament-grade and lit for night play. Here are a few that are open right now."
    >
        <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <SiteCourtCard
                v-for="court in featuredCourts"
                :key="court.id"
                :court="court"
            />
            <p
                v-if="featuredCourts.length === 0"
                class="text-content-muted"
            >
                Courts are being prepped — check back shortly.
            </p>
        </div>
        <div class="mt-10">
            <Link
                :href="courtsRoute()"
                class="inline-flex items-center gap-2 font-semibold text-brand hover:text-content"
            >
                See all courts →
            </Link>
        </div>
    </SiteSection>

    <!-- Facilities: a brand-tinted band. -->
    <SiteSection tone="court" :title="content.facilities.title">
        <div class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="item in content.facilities.items"
                :key="item.title"
                class="rounded-2xl border border-content-inverse/10 bg-content-inverse/5 p-7"
            >
                <div class="size-9 rounded-full bg-brand" aria-hidden="true" />
                <h3 class="mt-4 font-display text-lg font-bold text-content-inverse">
                    {{ item.title }}
                </h3>
                <p class="mt-2 text-sm text-content-inverse/70">{{ item.body }}</p>
            </div>
        </div>
    </SiteSection>

    <!-- Testimonials. -->
    <SiteSection :title="content.testimonials.title">
        <div class="mt-12 grid gap-6 md:grid-cols-3">
            <figure
                v-for="item in content.testimonials.items"
                :key="item.name"
                class="flex flex-col rounded-2xl border border-line bg-surface-elevated p-7 shadow-sm"
            >
                <div class="kitchen-line text-brand" aria-hidden="true" />
                <blockquote class="mt-5 flex-1 text-content">
                    "{{ item.quote }}"
                </blockquote>
                <figcaption class="mt-6">
                    <p class="font-semibold text-content">{{ item.name }}</p>
                    <p class="text-sm text-content-muted">{{ item.role }}</p>
                </figcaption>
            </figure>
        </div>
    </SiteSection>

    <!-- Closing CTA: a dramatic band on every theme. -->
    <section class="relative overflow-hidden bg-surface-inverse">
        <div
            class="pointer-events-none absolute -top-24 left-1/2 size-96 -translate-x-1/2 rounded-full bg-brand/25 blur-3xl"
            aria-hidden="true"
        />
        <div class="relative mx-auto max-w-4xl px-4 py-24 text-center sm:px-6">
            <h2
                class="font-display text-4xl font-extrabold tracking-tight text-balance text-content-inverse sm:text-5xl"
            >
                {{ content.cta.title }}
            </h2>
            <p class="mx-auto mt-5 max-w-xl text-lg text-content-inverse/70">
                {{ content.cta.body }}
            </p>
            <Link
                :href="courtsRoute()"
                class="mt-9 inline-flex rounded-full bg-brand px-8 py-3.5 text-base font-semibold text-brand-foreground transition-transform hover:-translate-y-0.5"
            >
                {{ content.cta.button }}
            </Link>
        </div>
    </section>
</template>
