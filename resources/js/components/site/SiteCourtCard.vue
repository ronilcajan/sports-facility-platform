<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { show as showCourt } from '@/routes/site/courts';
import type { PublicCourt } from '@/types';
import { formatDuration } from '@/utils/timeSlots';

const props = defineProps<{ court: PublicCourt }>();
defineEmits<{
    (e: 'book', court: PublicCourt): void;
}>();

function openCourt(): void {
    router.visit(showCourt.url(props.court.slug));
}
</script>

<template>
    <article
        class="group flex cursor-pointer flex-col overflow-hidden rounded-[var(--site-radius,1rem)] border border-line bg-surface-elevated shadow-md hover:border-brand/40 hover:shadow-xl"
        role="link"
        tabindex="0"
        @click="openCourt"
        @keydown.enter="openCourt"
        @keydown.space.prevent="openCourt"
    >
        <!-- Court Visual Container -->
        <div class="relative aspect-[16/10] overflow-hidden bg-surface-inverse">
            <!-- Court Image -->
            <img
                :src="court.primary_image_url || '/images/court_pickleball.png'"
                :alt="court.name"
                class="h-full w-full object-cover"
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
                <span class="size-2 rounded-full bg-white"></span>
                <span>Active</span>
            </span>

            <!-- Bottom Floating Title -->
            <div class="absolute right-4 bottom-4 left-4">
                <p
                    class="truncate text-xs font-bold tracking-wider text-brand uppercase"
                >
                    {{ court.venue ? court.venue.name : 'Main Facility' }}
                </p>
                <h3
                    class="mt-0.5 font-display text-xl font-extrabold tracking-tight text-content-inverse"
                >
                    <Link
                        :href="showCourt.url(court.slug)"
                        class="hover:text-brand"
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
                A friendly, well-kept court — book your time and enjoy the game.
            </p>

            <div
                class="mt-6 flex items-center justify-between border-t border-line pt-4"
            >
                <div>
                    <div class="flex items-baseline gap-1">
                        <span class="text-2xl font-black text-content"
                            >₱{{ court.base_price }}</span
                        >
                        <span class="text-xs text-content-muted"
                            >/
                            {{
                                formatDuration(court.slot_duration_minutes)
                            }}</span
                        >
                    </div>
                </div>
                <button
                    type="button"
                    @click.stop="$emit('book', court)"
                    class="inline-flex items-center justify-center rounded-md bg-brand px-4 py-2.5 text-sm font-bold text-brand-foreground shadow-md shadow-brand/10 hover:bg-brand/95 hover:shadow-brand/20"
                >
                    Book Now
                </button>
            </div>
        </div>
    </article>
</template>
