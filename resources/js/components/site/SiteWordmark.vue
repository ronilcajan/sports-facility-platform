<script setup lang="ts">
import { useSite } from '@/composables/useSite';

withDefaults(defineProps<{ tone?: 'ink' | 'chalk' }>(), { tone: 'ink' });

const site = useSite();
</script>

<template>
    <span
        class="inline-flex items-center gap-2 font-extrabold tracking-tight"
        :class="tone === 'chalk' ? 'text-chalk' : 'text-ink'"
    >
        <span
            class="ball-mark inline-block size-6 shrink-0"
            aria-hidden="true"
        />
        <span class="text-xl">{{ site.name }}</span>
    </span>
</template>
