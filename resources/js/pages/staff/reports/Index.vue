<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BarChart3 } from '@lucide/vue';

interface CourtItem {
    id: number;
    name: string;
}

interface Reports {
    startDate: string;
    endDate: string;
    totalBookings: number;
    totalRevenue: number;
    approvedBookings: number;
    pendingBookings: number;
    rejectedBookings: number;
    cancelledBookings: number;
}

const props = defineProps<{
    assignedCourts: CourtItem[];
    selectedCourt: CourtItem | null;
    reports: Reports | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Court Staff Dashboard', href: '/staff/dashboard' },
            { title: 'Court Performance Reports', href: '/staff/reports' },
        ],
    },
});

const start_date = ref(props.reports?.startDate || '');
const end_date = ref(props.reports?.endDate || '');
const court_id = ref(props.selectedCourt?.id || (props.assignedCourts[0]?.id ?? ''));

function filterReports() {
    router.get('/staff/reports', {
        court_id: court_id.value,
        start_date: start_date.value,
        end_date: end_date.value,
    }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Assigned Court Performance Reports - Court Staff" />

    <div class="p-6 space-y-6 max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Assigned Court Performance Report</h1>
                    <p class="text-xs text-neutral-500">Revenue, reservation volume, and occupancy metrics for {{ selectedCourt?.name ?? 'Assigned Court' }}.</p>
                </div>

                <!-- Date & Court Filter -->
                <div class="flex flex-wrap items-center gap-2 bg-white dark:bg-neutral-900 p-2 rounded-xl border border-neutral-200 dark:border-neutral-800 text-xs">
                    <select v-if="assignedCourts.length > 1" v-model="court_id" @change="filterReports" class="p-1.5 rounded-lg border border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-900 dark:text-white">
                        <option v-for="c in assignedCourts" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>

                    <input v-model="start_date" type="date" class="p-1.5 rounded-lg border border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-900 dark:text-white" />
                    <span class="text-neutral-400">to</span>
                    <input v-model="end_date" type="date" class="p-1.5 rounded-lg border border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-900 dark:text-white" />
                    <button @click="filterReports" class="px-3 py-1.5 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700">Apply</button>
                </div>
            </div>

            <!-- Reports Metrics Grid -->
            <div v-if="reports" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-1">
                    <span class="text-xs text-neutral-500 font-medium">Assigned Court Revenue</span>
                    <div class="text-2xl font-bold text-emerald-600">₱{{ reports.totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</div>
                    <span class="text-[11px] text-neutral-400">Confirmed booking earnings</span>
                </div>

                <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-1">
                    <span class="text-xs text-neutral-500 font-medium">Total Bookings Count</span>
                    <div class="text-2xl font-bold text-neutral-900 dark:text-white">{{ reports.totalBookings }}</div>
                    <span class="text-[11px] text-neutral-400">Bookings placed in date range</span>
                </div>

                <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-1">
                    <span class="text-xs text-neutral-500 font-medium">Approved / Confirmed</span>
                    <div class="text-2xl font-bold text-emerald-600">{{ reports.approvedBookings }}</div>
                    <span class="text-[11px] text-emerald-600 font-medium">Successfully processed</span>
                </div>

                <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-1">
                    <span class="text-xs text-neutral-500 font-medium">Rejected / Cancelled</span>
                    <div class="text-2xl font-bold text-rose-600">{{ reports.rejectedBookings + reports.cancelledBookings }}</div>
                    <span class="text-[11px] text-rose-600 font-medium">Not processed</span>
                </div>
            </div>

            <div v-else class="p-8 text-center text-xs text-neutral-400 border border-dashed rounded-2xl">
                No report data available for assigned court.
            </div>
        </div>
</template>
