<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import VenueForm, { type VenueData } from '@/components/admin/VenueForm.vue';

const props = defineProps<{
    venue: VenueData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Venues', href: '/admin/venues' },
            { title: 'Edit Venue' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${venue.name}`" />

    <div class="p-6 space-y-6 w-full">
        <div>
            <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Edit {{ venue.name }}</h1>
            <p class="text-xs text-neutral-500">Update this venue's information.</p>
        </div>

        <div class="max-w-2xl rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm">
            <VenueForm
                :venue="props.venue"
                :action="`/admin/venues/${props.venue.id}`"
                cancel-url="/admin/venues"
            />
        </div>
    </div>
</template>
