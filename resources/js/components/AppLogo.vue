<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import type { SiteData } from '@/types';

const page = usePage();
const siteName = computed(() => (page.props.site as SiteData | undefined)?.name ?? 'PickleHub');
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-white shrink-0"
    >
        <AppLogoIcon class="size-8 object-cover" />
    </div>
    <div class="ml-1.5 grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
        <span class="truncate leading-tight font-semibold text-sidebar-foreground">{{ siteName }}</span>
        <div class="mt-0.5 flex items-center gap-1 text-[9px] font-extrabold uppercase leading-none">
            <span class="font-serif tracking-widest text-amber-500 dark:text-amber-400">RAMBOY</span>
            <span class="font-sans tracking-[0.2em] text-neutral-400 dark:text-neutral-300">ENTERPRISE</span>
        </div>
    </div>
</template>
