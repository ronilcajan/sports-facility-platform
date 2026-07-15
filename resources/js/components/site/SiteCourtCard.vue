<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { show as showCourt } from '@/routes/site/courts';
import type { PublicCourt } from '@/types';

defineProps<{ court: PublicCourt }>();
defineEmits<{
    (e: 'book', court: PublicCourt): void;
}>();
</script>

<template>
    <article
        class="group flex flex-col overflow-hidden rounded-[var(--site-radius,1rem)] border border-line bg-surface-elevated shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-brand/40 hover:shadow-xl"
    >
        <!-- Court Visual Container -->
        <div class="relative aspect-[16/10] overflow-hidden bg-surface-inverse">
            <!-- Court Image -->
            <img
                :src="court.primary_image_url || '/images/court_pickleball.png'"
                :alt="court.name"
                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                loading="lazy"
            />
            <!-- Dark Overlay for visual hierarchy -->
            <div
                class="absolute inset-0 bg-gradient-to-t from-surface-inverse/85 via-surface-inverse/20 to-transparent"
            ></div>

            <!-- Sport Type Badge -->
            <span
                class="absolute top-4 left-4 rounded-full bg-brand/90 px-3.5 py-1 text-xs font-bold text-brand-foreground shadow backdrop-blur-md"
            >
                {{ court.sport_type }}
            </span>

            <!-- Status Indicator Badge (Always Available for public bookable courts) -->
            <span
                class="absolute top-4 right-4 flex items-center gap-1.5 rounded-full bg-emerald-500/90 px-3 py-1 text-xs font-bold text-white shadow backdrop-blur-md"
            >
                <span class="size-2 animate-ping rounded-full bg-white"></span>
                <span>Active</span>
            </span>

            <!-- Bottom Floating Title -->
            <div class="absolute right-4 bottom-4 left-4">
                <p
                    class="text-xs font-bold tracking-wider text-brand uppercase truncate"
                >
                    {{ court.venue ? court.venue.name : 'Main Facility' }}
                </p>
                <h3
                    class="mt-0.5 font-display text-xl font-extrabold tracking-tight text-content-inverse"
                >
                    <Link
                        :href="showCourt.url(court.slug)"
                        class="transition-colors hover:text-brand"
                    >
                        {{ court.name }}
                    </Link>
                </h3>
            </div>
        </div>

        <!-- Court Details -->
        <div class="flex flex-1 flex-col p-6">
            <p
                v-if="court.description"
                class="line-clamp-2 text-sm leading-relaxed text-content-muted"
            >
                {{ court.description }}
            </p>
            <p v-else class="text-sm leading-relaxed text-content-muted italic">
                Premium professional court featuring tournament-grade netting
                and cushioned surface.
            </p>

            <div
                class="mt-6 flex items-center justify-between border-t border-line pt-4"
            >
                <div>
                    <span
                        class="block text-sm text-[10px] font-semibold tracking-wider text-content-muted uppercase"
                        >Hourly Rate</span
                    >
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl font-black text-content"
                            >${{ court.base_price }}</span
                        >
                        <span class="text-xs text-content-muted"
                            >/ {{ court.slot_duration_minutes }} min</span
                        >
                    </div>
                </div>
                <button
                    type="button"
                    @click="$emit('book', court)"
                    class="inline-flex items-center justify-center rounded-full bg-brand px-6 py-2.5 text-sm font-bold text-brand-foreground shadow-md shadow-brand/10 transition-all duration-300 group-hover:-translate-y-0.5 group-hover:bg-brand/95 group-hover:shadow-brand/20"
                >
                    Book Now
                </button>
            </div>
        </div>
    </article>
</template>
