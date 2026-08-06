<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import { MapPin, Phone, Dumbbell, CalendarCheck, ChevronRight, ZoomIn, Building } from '@lucide/vue';
import VenueImageViewer from '@/components/site/VenueImageViewer.vue';
import type { PublicCourt, PublicVenue } from '@/types';

export interface CatalogVenue extends PublicVenue {
    image_url?: string | null;
    cover_image_url?: string | null;
    courts_count: number;
    courts: PublicCourt[];
}

const props = defineProps<{
    venue: CatalogVenue;
}>();

const emit = defineEmits<{
    (e: 'book-now', venue: CatalogVenue): void;
    (e: 'view-courts', venue: CatalogVenue): void;
}>();

const isViewerOpen = ref(false);

const imageList = computed(() => {
    const list: string[] = [];
    if (props.venue.cover_image_url) {
        list.push(props.venue.cover_image_url);
    }
    if (props.venue.courts) {
        props.venue.courts.forEach(c => {
            if (c.primary_image_url && !list.includes(c.primary_image_url)) {
                list.push(c.primary_image_url);
            }
        });
    }
    return list;
});
</script>

<template>
    <article
        class="group flex flex-col overflow-hidden rounded-[var(--site-radius,1.25rem)] border border-line bg-surface-elevated shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-brand/50 hover:shadow-2xl"
    >
        <!-- Venue Cover Header Image (Clickable to Venue Details) -->
        <Link
            :href="`/venues/${venue.slug}`"
            class="relative aspect-[16/9] overflow-hidden bg-surface-inverse block group/cover cursor-pointer"
        >
            <img
                v-if="venue.cover_image_url || venue.image_url"
                :src="venue.cover_image_url || venue.image_url || ''"
                :alt="venue.name"
                class="h-full w-full object-cover transition-transform duration-500 group-hover/cover:scale-105"
                loading="lazy"
            />
            <div v-else class="flex h-full w-full items-center justify-center bg-slate-900 text-slate-500">
                <Building class="size-14 text-emerald-500/30" />
            </div>
            <div
                class="absolute inset-0 bg-gradient-to-t from-surface-inverse/90 via-surface-inverse/30 to-transparent"
            />

            <!-- Badges -->
            <span
                class="absolute top-4 left-4 flex items-center gap-1.5 rounded-full bg-brand/90 px-3.5 py-1 text-xs font-bold text-brand-foreground shadow backdrop-blur-md"
            >
                <Dumbbell class="size-3.5" />
                <span>{{ venue.courts_count }} {{ venue.courts_count === 1 ? 'Court' : 'Courts' }}</span>
            </span>

            <!-- Zoom Preview Button -->
            <button
                type="button"
                @click.prevent.stop="isViewerOpen = true"
                class="absolute top-4 right-4 flex size-9 items-center justify-center rounded-full bg-black/60 text-white backdrop-blur-md border border-white/20 transition-all hover:scale-110 hover:bg-black/80 cursor-pointer shadow-lg z-10"
                title="Preview Venue Photo"
                aria-label="Preview Venue Photo"
            >
                <ZoomIn class="size-4 text-brand" />
            </button>

            <!-- Floating Name on Cover Image -->
            <div class="absolute right-4 bottom-4 left-4">
                <p class="text-xs font-bold tracking-wider text-brand uppercase truncate" v-if="venue.address">
                    {{ venue.address }}
                </p>
                <h3 class="mt-0.5 font-display text-2xl font-black tracking-tight text-white group-hover/cover:text-brand transition-colors">
                    {{ venue.name }}
                </h3>
            </div>
        </Link>

        <!-- Venue Card Content -->
        <div class="flex flex-1 flex-col justify-between p-6">
            <div class="space-y-3">
                <p v-if="venue.description" class="line-clamp-2 text-sm leading-relaxed text-content-muted">
                    {{ venue.description }}
                </p>
                <p v-else class="text-sm leading-relaxed text-content-muted italic">
                    A friendly pickleball venue with welcoming courts for every level.
                </p>

                <!-- Contact & Address Snippets -->
                <div class="space-y-1.5 pt-2 text-xs text-content-muted">
                    <div v-if="venue.address" class="flex items-center gap-2">
                        <MapPin class="size-3.5 text-brand shrink-0" />
                        <span class="truncate">{{ venue.address }}</span>
                    </div>
                    <div v-if="venue.phone" class="flex items-center gap-2">
                        <Phone class="size-3.5 text-brand shrink-0" />
                        <span>{{ venue.phone }}</span>
                    </div>
                </div>
            </div>

            <!-- Card Action Footer -->
            <div class="mt-6 border-t border-line pt-4 flex items-center justify-between">
                <Link
                    :href="`/venues/${venue.slug}`"
                    class="inline-flex items-center gap-1 text-xs font-bold text-content-muted hover:text-brand transition-colors cursor-pointer"
                >
                    <span>View Venue Details</span>
                    <ChevronRight class="size-3.5" />
                </Link>

                <button
                    type="button"
                    @click="emit('book-now', venue)"
                    class="inline-flex items-center gap-2 rounded-full bg-brand px-6 py-2.5 text-xs font-bold text-brand-foreground shadow-md shadow-brand/10 transition-all duration-300 hover:scale-102 hover:bg-brand/95 hover:shadow-brand/25 cursor-pointer"
                >
                    <CalendarCheck class="size-4" />
                    <span>Book Now</span>
                </button>
            </div>
        </div>

        <!-- Image Viewer Modal Overlay -->
        <VenueImageViewer
            :is-open="isViewerOpen"
            :images="imageList"
            :title="venue.name"
            @close="isViewerOpen = false"
        />
    </article>
</template>
