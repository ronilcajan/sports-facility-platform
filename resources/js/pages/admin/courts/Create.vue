<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CourtForm from '@/components/CourtForm.vue';
import Heading from '@/components/Heading.vue';

interface Option {
    value: string;
    label: string;
}

defineProps<{
    sportTypes: Option[];
    statuses: Option[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Courts', href: '/admin/courts' },
            { title: 'Add court', href: '/admin/courts/create' },
        ],
    },
});
</script>

<template>
    <Head title="Add court" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            variant="small"
            title="Add court"
            description="Create a new court and configure its booking rules."
        />

        <CourtForm :sport-types="sportTypes" :statuses="statuses" />
    </div>
</template>
