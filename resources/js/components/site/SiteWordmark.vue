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
        <span class="flex flex-col text-left">
            <span class="text-lg leading-tight font-extrabold">{{ site.name }}</span>
            <span class="mt-0.5 flex items-center gap-1 text-[9px] font-extrabold uppercase leading-none">
                <span class="font-serif tracking-widest text-brand">RAMBOY</span>
                <span class="font-sans tracking-[0.2em] opacity-75">ENTERPRISE</span>
            </span>
        </span>
    </span>
</template>
