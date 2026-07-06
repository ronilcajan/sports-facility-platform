<script setup lang="ts">
withDefaults(
    defineProps<{
        eyebrow?: string;
        title?: string;
        lede?: string;
        tone?: 'chalk' | 'ink' | 'court';
    }>(),
    { tone: 'chalk' },
);

const toneClass: Record<string, string> = {
    chalk: 'bg-chalk text-ink',
    ink: 'bg-ink text-chalk',
    court: 'bg-court text-chalk',
};
</script>

<template>
    <section :class="toneClass[tone]">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24">
            <header v-if="eyebrow || title || lede" class="max-w-2xl">
                <p
                    v-if="eyebrow"
                    class="text-xs font-semibold tracking-[0.2em] uppercase"
                    :class="tone === 'chalk' ? 'text-court' : 'text-volt'"
                >
                    {{ eyebrow }}
                </p>
                <h2
                    v-if="title"
                    class="mt-3 text-3xl font-extrabold tracking-tight text-balance sm:text-4xl"
                >
                    {{ title }}
                </h2>
                <p
                    v-if="lede"
                    class="mt-4 text-lg text-pretty"
                    :class="tone === 'chalk' ? 'text-fog' : 'text-chalk/70'"
                >
                    {{ lede }}
                </p>
            </header>
            <slot />
        </div>
    </section>
</template>
