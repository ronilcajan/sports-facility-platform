<script setup lang="ts">
defineProps<{
    eyebrow?: string;
    title: string;
    lede?: string;
}>();
</script>

<template>
    <section class="relative overflow-hidden bg-ink text-chalk">
        <div
            class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-court/40 blur-3xl"
            aria-hidden="true"
        />
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-28">
            <p
                v-if="eyebrow"
                class="text-xs font-semibold tracking-[0.2em] text-volt uppercase"
            >
                {{ eyebrow }}
            </p>
            <h1
                class="mt-4 max-w-3xl text-4xl font-extrabold tracking-tight text-balance sm:text-5xl"
            >
                {{ title }}
            </h1>
            <p
                v-if="lede"
                class="mt-6 max-w-2xl text-lg text-chalk/70 text-pretty"
            >
                {{ lede }}
            </p>
        </div>
        <div class="kitchen-line text-volt/60" aria-hidden="true" />
    </section>
</template>
