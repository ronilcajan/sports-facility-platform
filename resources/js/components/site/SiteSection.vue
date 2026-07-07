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

// Tone names are kept for backwards compatibility across the public pages, but
// now resolve to theme-relative tokens: `chalk` = the page surface, `ink` = a
// dramatic dark band, `court` = a brand-tinted dark band.
const toneClass: Record<string, string> = {
    chalk: 'bg-surface text-content',
    ink: 'bg-surface-inverse text-content-inverse',
    court: 'bg-gradient-to-br from-brand/25 to-surface-inverse text-content-inverse',
};

const eyebrowClass: Record<string, string> = {
    chalk: 'text-brand',
    ink: 'text-highlight',
    court: 'text-highlight',
};

const ledeClass: Record<string, string> = {
    chalk: 'text-content-muted',
    ink: 'text-content-inverse/70',
    court: 'text-content-inverse/70',
};
</script>

<template>
    <section :class="toneClass[tone]">
        <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24">
            <header v-if="eyebrow || title || lede" class="max-w-2xl">
                <p
                    v-if="eyebrow"
                    class="text-xs font-semibold tracking-[0.2em] uppercase"
                    :class="eyebrowClass[tone]"
                >
                    {{ eyebrow }}
                </p>
                <h2
                    v-if="title"
                    class="mt-3 font-display text-3xl font-extrabold tracking-tight text-balance sm:text-4xl"
                >
                    {{ title }}
                </h2>
                <p
                    v-if="lede"
                    class="mt-4 text-lg text-pretty"
                    :class="ledeClass[tone]"
                >
                    {{ lede }}
                </p>
            </header>
            <slot />
        </div>
    </section>
</template>
