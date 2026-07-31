<script setup lang="ts">
defineProps<{
    eyebrow?: string;
    title: string;
    lede?: string;
}>();
</script>

<template>
    <section
        class="relative overflow-hidden bg-surface-inverse text-content-inverse"
    >
        <div
            class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-brand/40 blur-3xl"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute -bottom-32 -left-24 size-72 rounded-full bg-highlight/25 blur-3xl"
            aria-hidden="true"
        />
        <div class="relative mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-20 lg:py-28">
            <p
                v-if="eyebrow"
                class="text-xs font-semibold tracking-[0.2em] text-highlight uppercase"
            >
                {{ eyebrow }}
            </p>
            <h1
                class="mt-4 max-w-3xl font-display text-3xl font-extrabold tracking-tight text-balance sm:text-4xl lg:text-5xl"
            >
                {{ title }}
            </h1>
            <p
                v-if="lede"
                class="mt-4 max-w-2xl text-base text-pretty text-content-inverse/70 sm:mt-6 sm:text-lg"
            >
                {{ lede }}
            </p>
        </div>
        <div class="kitchen-line text-highlight" aria-hidden="true" />
    </section>
</template>
