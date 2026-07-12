<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHero from '@/components/site/PageHero.vue';
import SiteSection from '@/components/site/SiteSection.vue';

interface AboutContent {
    title: string;
    lede: string;
    body: string[];
    values: { title: string; body: string }[];
}

defineProps<{ content: AboutContent }>();
</script>

<template>
    <Head title="About" />

    <PageHero eyebrow="Our story" :title="content.title" :lede="content.lede" />

    <SiteSection>
        <div class="grid gap-12 lg:grid-cols-[1.4fr_1fr]">
            <div class="space-y-6 text-lg text-pretty text-content-muted">
                <p v-for="(para, i) in content.body" :key="i">{{ para }}</p>
            </div>
            <div class="space-y-4">
                <div
                    v-for="value in content.values"
                    :key="value.title"
                    class="rounded-2xl border border-line bg-surface-elevated p-6"
                >
                    <div class="ball-mark size-7" aria-hidden="true" />
                    <h3 class="mt-4 font-bold text-content">
                        {{ value.title }}
                    </h3>
                    <p class="mt-1 text-sm text-content-muted">
                        {{ value.body }}
                    </p>
                </div>
            </div>
        </div>
    </SiteSection>
</template>
