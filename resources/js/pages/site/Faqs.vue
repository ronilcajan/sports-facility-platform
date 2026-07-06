<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHero from '@/components/site/PageHero.vue';

interface FaqContent {
    title: string;
    lede: string;
    items: { q: string; a: string }[];
}

defineProps<{ content: FaqContent }>();
</script>

<template>
    <Head title="FAQs" />

    <PageHero
        eyebrow="FAQs"
        :title="content.title"
        :lede="content.lede"
    />

    <div class="mx-auto max-w-3xl px-4 py-20 sm:px-6 sm:py-24">
        <div class="divide-y divide-ink/10">
            <details
                v-for="(item, i) in content.items"
                :key="i"
                class="group py-5"
            >
                <summary
                    class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-semibold text-ink"
                >
                    {{ item.q }}
                    <span
                        class="grid size-7 shrink-0 place-items-center rounded-full border border-ink/20 text-court transition-transform group-open:rotate-45"
                        aria-hidden="true"
                    >
                        +
                    </span>
                </summary>
                <p class="mt-3 text-pretty text-fog">{{ item.a }}</p>
            </details>
        </div>
    </div>
</template>
