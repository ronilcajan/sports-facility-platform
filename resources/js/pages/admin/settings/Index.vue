<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import VenueForm, { type VenueData } from '@/components/admin/VenueForm.vue';

const props = defineProps<{
    venue: VenueData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Setting', href: '/admin/settings' }],
    },
});
</script>

<template>
    <Head title="Venue Setting" />

    <div class="p-6 space-y-6 w-full">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">{{ venue.name }} Settings</h1>
            <p class="text-xs text-neutral-500">Manage your venue's details, contact info, and payment methods.</p>
        </div>

        <div class="max-w-2xl rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm">
            <VenueForm
                :venue="props.venue"
                action="/admin/settings"
                cancel-url="/admin/dashboard"
            />
        </div>
    </div>
</template>
