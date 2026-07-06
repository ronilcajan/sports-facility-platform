<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHero from '@/components/site/PageHero.vue';
import SiteCourtCard from '@/components/site/SiteCourtCard.vue';
import type { PublicCourt } from '@/types';

defineProps<{ courts: PublicCourt[] }>();
</script>

<template>
    <Head title="Courts" />

    <PageHero
        eyebrow="The courts"
        title="Twelve courts, all yours to book"
        lede="Tournament-grade surfaces, glare-free lighting, and real-time availability. Pick one and play."
    />

    <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24">
        <div
            v-if="courts.length"
            class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
        >
            <SiteCourtCard
                v-for="court in courts"
                :key="court.id"
                :court="court"
            />
        </div>
        <div
            v-else
            class="rounded-2xl border border-dashed border-ink/15 p-16 text-center text-fog"
        >
            No courts are open for booking right now. Please check back soon.
        </div>
    </div>
</template>
