<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import PageHero from '@/components/site/PageHero.vue';
import { courts as courtsRoute } from '@/routes/site';

interface Tier {
    name: string;
    price: string;
    unit: string;
    note: string;
    features: string[];
    featured: boolean;
}

interface PricingContent {
    title: string;
    lede: string;
    tiers: Tier[];
}

defineProps<{ content: PricingContent }>();
</script>

<template>
    <Head title="Pricing" />

    <PageHero eyebrow="Pricing" :title="content.title" :lede="content.lede" />

    <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24">
        <div class="grid gap-6 lg:grid-cols-3">
            <div
                v-for="tier in content.tiers"
                :key="tier.name"
                class="flex flex-col rounded-2xl border p-8"
                :class="
                    tier.featured
                        ? 'border-brand bg-brand text-brand-foreground shadow-xl'
                        : 'border-line bg-surface-elevated text-content'
                "
            >
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">{{ tier.name }}</h2>
                    <span
                        v-if="tier.featured"
                        class="rounded-full bg-highlight px-3 py-1 text-xs font-semibold text-brand-foreground"
                    >
                        Most popular
                    </span>
                </div>
                <p
                    class="mt-1 text-sm"
                    :class="
                        tier.featured
                            ? 'text-brand-foreground/75'
                            : 'text-content-muted'
                    "
                >
                    {{ tier.note }}
                </p>
                <div class="mt-6 flex items-baseline gap-2">
                    <span class="text-4xl font-extrabold">{{
                        tier.price
                    }}</span>
                    <span
                        class="text-sm"
                        :class="
                            tier.featured
                                ? 'text-brand-foreground/75'
                                : 'text-content-muted'
                        "
                    >
                        {{ tier.unit }}
                    </span>
                </div>
                <ul class="mt-8 flex-1 space-y-3 text-sm">
                    <li
                        v-for="feature in tier.features"
                        :key="feature"
                        class="flex items-center gap-3"
                    >
                        <span
                            class="inline-block size-1.5 shrink-0 rounded-full"
                            :class="tier.featured ? 'bg-highlight' : 'bg-brand'"
                        />
                        {{ feature }}
                    </li>
                </ul>
                <Link
                    :href="courtsRoute()"
                    class="mt-8 rounded-full px-5 py-3 text-center text-sm font-semibold transition-all duration-200 hover:-translate-y-0.5"
                    :class="
                        tier.featured
                            ? 'bg-surface text-content hover:bg-surface-elevated'
                            : 'bg-brand text-brand-foreground hover:bg-brand/90'
                    "
                >
                    Book a court
                </Link>
            </div>
        </div>
    </div>
</template>
