<script setup lang="ts">
import { useSite } from '@/composables/useSite';

withDefaults(defineProps<{ tone?: 'ink' | 'chalk' }>(), { tone: 'ink' });

const site = useSite();
</script>

<template>
    <span
        class="inline-flex items-center gap-2 font-display font-extrabold tracking-tight"
        :class="tone === 'chalk' ? 'text-content-inverse' : 'text-content'"
    >
        <img
            :src="site.logo"
            :alt="site.name"
            class="size-8 shrink-0 rounded-full object-cover ring-1 ring-line"
        />
        <span class="text-xl">{{ site.name }}</span>
    </span>
</template>
