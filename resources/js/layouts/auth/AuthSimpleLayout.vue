<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import { home } from '@/routes';
import type { SiteData } from '@/types';

defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
const site = computed(() => page.props.site as SiteData | undefined);

// ── Site visitor dark-mode toggle (shared with SiteHeader) ─────────────────
const siteDark = ref(false);

onMounted(() => {
    siteDark.value = document.documentElement.classList.contains('site-dark');
});

function toggleSiteDark() {
    siteDark.value = !siteDark.value;
    document.documentElement.classList.toggle('site-dark', siteDark.value);
    localStorage.setItem('site-dark-mode', String(siteDark.value));
}
</script>

<template>
    <div
        class="relative flex min-h-svh flex-col items-center justify-center gap-6 overflow-hidden bg-surface p-6 text-content transition-colors duration-300 md:p-10"
    >
        <!-- Decorative court lines (matches logo and hero court motif) -->
        <div
            class="pointer-events-none absolute inset-0 opacity-15"
            aria-hidden="true"
        >
            <div class="absolute inset-8 rounded-[2.5rem] border border-brand/30 sm:inset-16 transition-colors duration-300"></div>
            <div class="absolute inset-x-8 top-1/2 h-px -translate-y-1/2 bg-brand/25 sm:inset-x-16 transition-colors duration-300"></div>
            <div class="absolute inset-y-8 left-1/2 w-px -translate-x-1/2 bg-brand/25 sm:inset-y-16 transition-colors duration-300"></div>
        </div>

        <!-- Dark mode toggle — floating top-right -->
        <button
            type="button"
            :title="siteDark ? 'Switch to light mode' : 'Switch to dark mode'"
            :aria-label="siteDark ? 'Switch to light mode' : 'Switch to dark mode'"
            class="absolute right-5 top-5 z-20 inline-flex size-10 items-center justify-center rounded-full border border-line bg-surface-elevated text-content-muted shadow-sm transition-all duration-200 hover:scale-110 hover:text-brand"
            @click="toggleSiteDark"
        >
            <!-- Sun icon — shown in dark mode, click to go light -->
            <svg v-if="siteDark" xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="5"/>
                <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
            </svg>
            <!-- Moon icon — shown in light mode, click to go dark -->
            <svg v-else xmlns="http://www.w3.org/2000/svg" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
        </button>

        <div class="relative z-10 w-full max-w-sm">
            <div
                class="rounded-2xl border border-line bg-surface-elevated p-8 shadow-2xl shadow-brand/5 backdrop-blur-md transition-colors duration-300"
            >
                <div class="flex flex-col items-center gap-4">
                    <Link
                        :href="home()"
                        class="flex flex-col items-center gap-3 font-medium transition-transform duration-200 hover:scale-105"
                    >
                        <img
                            :src="site?.logo ?? '/logo.jpg'"
                            :alt="site?.name ?? 'Logo'"
                            class="size-14 rounded-full object-cover shadow-md ring-2 ring-brand/40 shadow-brand/15 transition-colors duration-300"
                        />
                        <div class="flex flex-col items-center text-center">
                            <span class="font-display text-xl font-extrabold tracking-tight text-content transition-colors duration-300">
                                {{ site?.name ?? 'PickleHub' }}
                            </span>
                            <div class="mt-0.5 flex items-center justify-center gap-1.5 text-[10px] font-extrabold uppercase leading-none">
                                <span class="font-serif tracking-widest text-brand">RAMBOY</span>
                                <span class="font-sans tracking-[0.2em] text-content-muted opacity-70 transition-colors duration-300">ENTERPRISE</span>
                            </div>
                        </div>
                    </Link>
                    <div class="space-y-1.5 text-center">
                        <h1 class="font-display text-xl font-bold text-content transition-colors duration-300">{{ title }}</h1>
                        <p class="text-center text-sm text-content-muted transition-colors duration-300">
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
