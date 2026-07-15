<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import PageHero from '@/components/site/PageHero.vue';

interface GalleryImage {
    url: string;
    court: string | null;
    venue: string | null;
}

interface GalleryContent {
    title: string;
    lede: string;
}

defineProps<{ content: GalleryContent; images: GalleryImage[] }>();

// Lightbox
const lightboxUrl = ref<string | null>(null);
</script>

<template>
    <Head title="Gallery" />

    <PageHero eyebrow="Gallery" :title="content.title" :lede="content.lede" />

    <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24">
        <!-- Masonry layout via CSS columns -->
        <div
            v-if="images.length"
            class="columns-2 gap-3 sm:columns-3 lg:columns-4 [column-fill:_balance]"
        >
            <figure
                v-for="(image, i) in images"
                :key="i"
                class="group relative mb-3 block cursor-zoom-in overflow-hidden rounded-xl border border-line bg-surface-inverse break-inside-avoid"
                @click="lightboxUrl = image.url"
            >
                <img
                    :src="image.url"
                    :alt="`${image.court ?? 'Court'}${image.venue ? ' at ' + image.venue : ''}`"
                    loading="lazy"
                    class="w-full object-cover transition-transform duration-500 group-hover:scale-105"
                />
                <div
                    class="pointer-events-none absolute inset-0 bg-gradient-to-t from-surface-inverse/85 via-transparent to-transparent opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                />
                <figcaption
                    v-if="image.court || image.venue"
                    class="absolute inset-x-0 bottom-0 translate-y-2 p-3 text-content-inverse opacity-0 transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100"
                >
                    <span class="block text-sm font-bold">{{ image.court }}</span>
                    <span v-if="image.venue" class="block text-xs text-content-inverse/70">{{ image.venue }}</span>
                </figcaption>
            </figure>
        </div>

        <!-- Empty state -->
        <div
            v-else
            class="rounded-2xl border border-dashed border-line bg-surface-elevated/30 p-16 text-center"
        >
            <p class="text-lg font-bold text-content">No photos yet</p>
            <p class="mt-1 text-sm text-content-muted">
                Court photos will appear here as venues add them.
            </p>
        </div>
    </div>

    <!-- Lightbox overlay -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="lightboxUrl"
            class="fixed inset-0 z-50 flex items-center justify-center bg-surface-inverse/85 p-6 backdrop-blur-sm"
            @click="lightboxUrl = null"
        >
            <img
                :src="lightboxUrl"
                alt="Court photo"
                class="max-h-[85vh] max-w-full rounded-xl object-contain shadow-2xl"
            />
        </div>
    </Transition>
</template>
