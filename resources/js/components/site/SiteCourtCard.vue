<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PublicCourt } from '@/types';

defineProps<{ court: PublicCourt }>();
</script>

<template>
    <article
        class="group flex flex-col overflow-hidden rounded-2xl border border-line bg-surface-elevated shadow-sm transition-all hover:-translate-y-1 hover:shadow-xl"
    >
        <!-- Court-surface artwork: a brand-toned field with chalk boundary lines. -->
        <div
            class="relative aspect-[4/3] bg-gradient-to-br from-brand to-surface-inverse"
            aria-hidden="true"
        >
            <div class="absolute inset-4 rounded-sm border-2 border-white/30" />
            <div class="absolute inset-x-4 top-1/2 h-0.5 -translate-y-1/2 bg-white/30" />
            <div class="absolute inset-y-4 left-1/2 w-0.5 -translate-x-1/2 bg-white/20" />
            <div
                class="absolute right-6 bottom-6 size-8 rounded-full bg-highlight shadow transition-transform group-hover:scale-110"
            />
        </div>

        <div class="flex flex-1 flex-col p-5">
            <div class="flex items-center justify-between gap-3">
                <h3 class="font-display font-bold text-content">{{ court.name }}</h3>
                <span
                    class="rounded-full bg-brand/10 px-2.5 py-0.5 text-xs font-semibold text-brand"
                >
                    {{ court.sport_type }}
                </span>
            </div>
            <p
                v-if="court.description"
                class="mt-2 line-clamp-2 text-sm text-content-muted"
            >
                {{ court.description }}
            </p>
            <div class="mt-4 flex items-end justify-between pt-4">
                <div>
                    <span class="text-2xl font-extrabold text-content"
                        >${{ court.base_price }}</span
                    >
                    <span class="text-sm text-content-muted">
                        / {{ court.slot_duration_minutes }} min
                    </span>
                </div>
                <Link
                    :href="'/courts'"
                    class="rounded-full bg-brand px-4 py-2 text-sm font-semibold text-brand-foreground transition-transform group-hover:-translate-y-0.5"
                >
                    Book
                </Link>
            </div>
        </div>
    </article>
</template>
