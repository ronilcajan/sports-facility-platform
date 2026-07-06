<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CourtForm from '@/components/CourtForm.vue';
import Heading from '@/components/Heading.vue';

interface Option {
    value: string;
    label: string;
}

interface Court {
    id: number;
    name: string;
    sport_type: string;
    description: string | null;
    status: string;
    base_price: string;
    slot_duration_minutes: number;
    buffer_minutes: number;
    is_active: boolean;
}

const props = defineProps<{
    court: Court;
    sportTypes: Option[];
    statuses: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Courts', href: '/admin/courts' },
            { title: 'Edit court', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${props.court.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            variant="small"
            :title="`Edit ${props.court.name}`"
            description="Update court details and booking rules."
        />

        <CourtForm
            :court="props.court"
            :sport-types="sportTypes"
            :statuses="statuses"
        />
    </div>
</template>
