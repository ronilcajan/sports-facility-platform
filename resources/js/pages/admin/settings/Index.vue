<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
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

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            variant="small"
            :title="`${venue.name} Setting`"
            description="Manage your venue's details, contact info, and payment methods."
        />

        <VenueForm
            :venue="props.venue"
            action="/admin/settings"
            cancel-url="/admin/dashboard"
        />
    </div>
</template>
