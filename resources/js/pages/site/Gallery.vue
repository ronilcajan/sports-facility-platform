<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHero from '@/components/site/PageHero.vue';

interface GalleryItem {
    label: string;
    tone: 'court' | 'ink' | 'volt';
}

interface GalleryContent {
    title: string;
    lede: string;
    items: GalleryItem[];
}

defineProps<{ content: GalleryContent }>();

const toneClass: Record<GalleryItem['tone'], string> = {
    court: 'bg-court text-chalk',
    ink: 'bg-ink text-chalk',
    volt: 'bg-volt text-ink',
};
</script>

<template>
    <Head title="Gallery" />

    <PageHero
        eyebrow="Gallery"
        :title="content.title"
        :lede="content.lede"
    />

    <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <figure
                v-for="(item, i) in content.items"
                :key="item.label"
                class="group relative flex aspect-[4/3] items-end overflow-hidden rounded-2xl p-6"
                :class="toneClass[item.tone]"
            >
                <!-- Abstract court-surface motif in place of a photo. -->
                <div
                    class="pointer-events-none absolute inset-5 rounded-md border-2 opacity-40"
                    :class="item.tone === 'volt' ? 'border-ink' : 'border-chalk'"
                    aria-hidden="true"
                />
                <div
                    class="pointer-events-none absolute inset-x-5 top-1/2 h-0.5 opacity-40"
                    :class="item.tone === 'volt' ? 'bg-ink' : 'bg-chalk'"
                    aria-hidden="true"
                />
                <figcaption
                    class="relative text-lg font-bold transition-transform group-hover:-translate-y-0.5"
                >
                    {{ item.label }}
                    <span class="sr-only"> — placeholder image {{ i + 1 }}</span>
                </figcaption>
            </figure>
        </div>
        <p class="mt-8 text-sm text-fog">
            Photography coming soon — these tiles stand in for real facility
            shots.
        </p>
    </div>
</template>
