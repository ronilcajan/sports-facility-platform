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

    <PageHero
        eyebrow="Pricing"
        :title="content.title"
        :lede="content.lede"
    />

    <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24">
        <div class="grid gap-6 lg:grid-cols-3">
            <div
                v-for="tier in content.tiers"
                :key="tier.name"
                class="flex flex-col rounded-2xl border p-8"
                :class="
                    tier.featured
                        ? 'border-ink bg-ink text-chalk shadow-xl'
                        : 'border-ink/10 bg-white text-ink'
                "
            >
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold">{{ tier.name }}</h2>
                    <span
                        v-if="tier.featured"
                        class="rounded-full bg-volt px-3 py-1 text-xs font-semibold text-ink"
                    >
                        Most popular
                    </span>
                </div>
                <p
                    class="mt-1 text-sm"
                    :class="tier.featured ? 'text-chalk/60' : 'text-fog'"
                >
                    {{ tier.note }}
                </p>
                <div class="mt-6 flex items-baseline gap-2">
                    <span class="text-4xl font-extrabold">{{ tier.price }}</span>
                    <span
                        class="text-sm"
                        :class="tier.featured ? 'text-chalk/60' : 'text-fog'"
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
                            :class="tier.featured ? 'bg-volt' : 'bg-court'"
                        />
                        {{ feature }}
                    </li>
                </ul>
                <Link
                    :href="courtsRoute()"
                    class="mt-8 rounded-full px-5 py-3 text-center text-sm font-semibold transition-transform hover:-translate-y-0.5"
                    :class="
                        tier.featured
                            ? 'bg-volt text-ink'
                            : 'bg-ink text-chalk'
                    "
                >
                    Book a court
                </Link>
            </div>
        </div>
    </div>
</template>
