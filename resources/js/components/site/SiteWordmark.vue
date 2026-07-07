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
        <!-- Signature: a brand disc split by court net + center lines. -->
        <span
            class="relative inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-brand"
            aria-hidden="true"
        >
            <span class="absolute h-px w-full bg-brand-foreground/70" />
            <span class="absolute h-full w-px bg-brand-foreground/40" />
        </span>
        <span class="text-xl">{{ site.name }}</span>
    </span>
</template>
