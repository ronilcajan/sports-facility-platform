<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppearanceController from '@/actions/App/Http/Controllers/Admin/AppearanceController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

interface ThemeOption {
    value: string;
    label: string;
    description: string;
}

const props = defineProps<{
    themes: ThemeOption[];
    activeTheme: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Appearance', href: '/admin/appearance' }],
    },
});

const form = useForm({ theme: props.activeTheme });

function preview(value: string): void {
    form.theme = value;
    document.documentElement.dataset.theme = value;
}

function save(): void {
    form.submit(AppearanceController.update(), { preserveScroll: true });
}
</script>

<template>
    <div class="px-4 py-6">
        <Head title="Appearance" />

        <Heading
            title="Appearance"
            description="Choose the active theme for the public website. Applies site-wide."
        />

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <button
                v-for="theme in themes"
                :key="theme.value"
                type="button"
                class="rounded-xl border p-5 text-left transition"
                :class="
                    form.theme === theme.value
                        ? 'border-primary ring-2 ring-primary'
                        : 'border-border hover:border-primary/50'
                "
                @click="preview(theme.value)"
            >
                <span class="block text-base font-semibold">{{
                    theme.label
                }}</span>
                <span class="mt-1 block text-sm text-muted-foreground">{{
                    theme.description
                }}</span>
            </button>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <Button :disabled="form.processing" @click="save"
                >Save theme</Button
            >
            <span
                v-if="form.recentlySuccessful"
                class="text-sm text-muted-foreground"
                >Saved.</span
            >
        </div>
    </div>
</template>
