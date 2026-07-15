<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import type { SiteData } from '@/types';

const page = usePage();
const siteName = computed(() => (page.props.site as SiteData | undefined)?.name ?? 'PickleBall');
</script>

<template>
    <div
        class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-white"
    >
        <AppLogoIcon class="size-8 object-cover" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate leading-tight font-semibold">{{ siteName }}</span>
    </div>
</template>
