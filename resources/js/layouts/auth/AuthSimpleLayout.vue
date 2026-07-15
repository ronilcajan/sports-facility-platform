<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { home } from '@/routes';
import type { SiteData } from '@/types';

defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
const site = computed(() => page.props.site as SiteData | undefined);
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-surface p-6 text-content md:p-10"
    >
        <!-- Decorative court lines (matches the hero's thin court motif) -->
        <div
            class="pointer-events-none absolute inset-0 opacity-[0.06]"
            aria-hidden="true"
        >
            <div class="absolute inset-8 rounded-[2rem] border border-content sm:inset-16"></div>
            <div class="absolute inset-x-8 top-1/2 h-px -translate-y-1/2 bg-content sm:inset-x-16"></div>
            <div class="absolute inset-y-8 left-1/2 w-px -translate-x-1/2 bg-content sm:inset-y-16"></div>
        </div>

        <div class="relative z-10 w-full max-w-sm">
            <div
                class="rounded-2xl border border-line bg-surface-elevated p-8 shadow-xl"
            >
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-3 font-medium"
                    >
                        <img
                            :src="site?.logo ?? '/logo.jpg'"
                            :alt="site?.name ?? 'Logo'"
                            class="size-14 rounded-full object-cover ring-1 ring-line"
                        />
                        <span class="font-display text-lg font-extrabold tracking-tight text-content">
                            {{ site?.name ?? 'PickleBall' }}
                        </span>
                    </Link>
                    <div class="space-y-1.5 text-center">
                        <h1 class="font-display text-xl font-bold text-content">{{ title }}</h1>
                        <p class="text-center text-sm text-content-muted">
                            {{ description }}
                        </p>
                    </div>
                </div>
                <div class="mt-8">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
