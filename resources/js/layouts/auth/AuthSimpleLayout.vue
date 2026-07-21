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
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-[#090d16] p-6 text-slate-100 md:p-10"
    >
        <!-- Decorative court lines (matches logo and hero court motif) -->
        <div
            class="pointer-events-none absolute inset-0 opacity-15"
            aria-hidden="true"
        >
            <div class="absolute inset-8 rounded-[2.5rem] border border-amber-500/20 sm:inset-16"></div>
            <div class="absolute inset-x-8 top-1/2 h-px -translate-y-1/2 bg-amber-500/20 sm:inset-x-16"></div>
            <div class="absolute inset-y-8 left-1/2 w-px -translate-x-1/2 bg-amber-500/20 sm:inset-y-16"></div>
        </div>

        <div class="relative z-10 w-full max-w-sm">
            <div
                class="rounded-2xl border border-amber-500/20 bg-[#131b2e]/90 backdrop-blur-md p-8 shadow-2xl shadow-amber-500/5"
            >
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-3 font-medium transition-transform duration-200 hover:scale-105"
                    >
                        <img
                            :src="site?.logo ?? '/logo.jpg'"
                            :alt="site?.name ?? 'Logo'"
                            class="size-14 rounded-full object-cover ring-2 ring-amber-500/40 shadow-md shadow-amber-500/20"
                        />
                        <div class="flex flex-col items-center text-center">
                            <span class="font-display text-xl font-extrabold tracking-tight text-white">
                                {{ site?.name ?? 'PickleHub' }}
                            </span>
                            <div class="mt-0.5 flex items-center justify-center gap-1.5 text-[10px] font-extrabold uppercase leading-none">
                                <span class="font-serif tracking-widest text-amber-400">RAMBOY</span>
                                <span class="font-sans tracking-[0.2em] text-slate-300 opacity-80">ENTERPRISE</span>
                            </div>
                        </div>
                    </Link>
                    <div class="space-y-1.5 text-center">
                        <h1 class="font-display text-xl font-bold text-slate-100">{{ title }}</h1>
                        <p class="text-center text-sm text-slate-400">
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
