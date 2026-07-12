<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PageHero from '@/components/site/PageHero.vue';

interface LegalContent {
    title: string;
    updated: string;
    sections: { heading: string; body: string }[];
}

defineProps<{ content: LegalContent }>();
</script>

<template>
    <Head title="Privacy Policy" />

    <PageHero :title="content.title" :lede="content.updated" />

    <div class="mx-auto max-w-3xl px-4 py-20 sm:px-6 sm:py-24">
        <div class="space-y-10">
            <section v-for="section in content.sections" :key="section.heading">
                <h2 class="text-xl font-bold text-content">
                    {{ section.heading }}
                </h2>
                <p class="mt-3 text-pretty text-content-muted">
                    {{ section.body }}
                </p>
            </section>
        </div>
    </div>
</template>
