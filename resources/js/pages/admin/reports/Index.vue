<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { BarChart3, Calendar, DollarSign, Dumbbell, CheckCircle, XCircle } from '@lucide/vue';

interface CourtBreakdownItem {
    id: number;
    name: string;
    sport_type: string;
    total_bookings: number;
    approved_count: number;
    revenue: number;
}

interface Reports {
    startDate: string;
    endDate: string;
    totalBookings: number;
    totalRevenue: number;
    approvedBookings: number;
    rejectedBookings: number;
    cancelledBookings: number;
    courtBreakdown: CourtBreakdownItem[];
}

const props = defineProps<{
    reports: Reports;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Super Admin Overview', href: '/admin/dashboard' },
            { title: 'System Analytics & Reports', href: '/admin/reports' },
        ],
    },
});

const start_date = ref(props.reports.startDate);
const end_date = ref(props.reports.endDate);

function filterReports() {
    router.get('/admin/reports', { start_date: start_date.value, end_date: end_date.value }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="System Reports - Super Admin" />

    <div class="p-6 space-y-6 max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">System Reports & Financial Analytics</h1>
                    <p class="text-xs text-neutral-500">Overall booking statistics and revenue breakdown across all courts.</p>
                </div>

                <!-- Date Range Filter -->
                <div class="flex items-center gap-2 bg-white dark:bg-neutral-900 p-2 rounded-xl border border-neutral-200 dark:border-neutral-800 text-xs">
                    <input v-model="start_date" type="date" class="p-1.5 rounded-lg border border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-900 dark:text-white" />
                    <span class="text-neutral-400">to</span>
                    <input v-model="end_date" type="date" class="p-1.5 rounded-lg border border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-900 dark:text-white" />
                    <button @click="filterReports" class="px-3 py-1.5 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700">Apply</button>
                </div>
            </div>

            <!-- Metric Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-1">
                    <span class="text-xs text-neutral-500 font-medium">Period Revenue</span>
                    <div class="text-2xl font-bold text-emerald-600">₱{{ reports.totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</div>
                    <span class="text-[11px] text-neutral-400">Total generated earnings</span>
                </div>

                <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-1">
                    <span class="text-xs text-neutral-500 font-medium">Total Reservations</span>
                    <div class="text-2xl font-bold text-neutral-900 dark:text-white">{{ reports.totalBookings }}</div>
                    <span class="text-[11px] text-neutral-400">Bookings placed in date range</span>
                </div>

                <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-1">
                    <span class="text-xs text-neutral-500 font-medium">Approved / Confirmed</span>
                    <div class="text-2xl font-bold text-emerald-600">{{ reports.approvedBookings }}</div>
                    <span class="text-[11px] text-emerald-600 font-medium">Successful bookings</span>
                </div>

                <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-1">
                    <span class="text-xs text-neutral-500 font-medium">Rejected / Cancelled</span>
                    <div class="text-2xl font-bold text-rose-600">{{ reports.rejectedBookings + reports.cancelledBookings }}</div>
                    <span class="text-[11px] text-rose-600 font-medium">Rejected or cancelled</span>
                </div>
            </div>

            <!-- Court Breakdown Table -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-4">
                <h3 class="font-bold text-sm text-neutral-900 dark:text-white">Revenue & Volume Breakdown By Court</h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-semibold uppercase">
                                <th class="py-3 px-3">Court Name</th>
                                <th class="py-3 px-3">Sport Category</th>
                                <th class="py-3 px-3">Total Reservations</th>
                                <th class="py-3 px-3">Approved Count</th>
                                <th class="py-3 px-3 text-right">Revenue Generated</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                            <tr v-for="court in reports.courtBreakdown" :key="court.id" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40">
                                <td class="py-3 px-3 font-bold text-neutral-900 dark:text-white">{{ court.name }}</td>
                                <td class="py-3 px-3 text-neutral-600 dark:text-neutral-300">{{ court.sport_type }}</td>
                                <td class="py-3 px-3 text-neutral-800 dark:text-neutral-200">{{ court.total_bookings }}</td>
                                <td class="py-3 px-3 font-semibold text-emerald-600">{{ court.approved_count }}</td>
                                <td class="py-3 px-3 text-right font-bold text-neutral-900 dark:text-white">₱{{ court.revenue.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</template>
