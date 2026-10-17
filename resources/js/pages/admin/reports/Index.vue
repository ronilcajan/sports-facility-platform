<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BarChart3,
    Calendar,
    DollarSign,
    Dumbbell,
    CheckCircle,
    XCircle,
    Receipt,
    TrendingUp,
    TrendingDown,
    ArrowUpRight,
    ArrowDownRight,
    PieChart,
} from '@lucide/vue';

interface CourtBreakdownItem {
    id: number;
    name: string;
    sport_type: string;
    total_bookings: number;
    approved_count: number;
    revenue: number;
}

interface ExpenseCategoryItem {
    category: string;
    label: string;
    total_amount: number;
    count: number;
}

interface Reports {
    startDate: string;
    endDate: string;
    totalBookings: number;
    totalRevenue: number;
    totalExpenses: number;
    netProfit: number;
    approvedBookings: number;
    rejectedBookings: number;
    cancelledBookings: number;
    courtBreakdown: CourtBreakdownItem[];
    expenseCategories?: ExpenseCategoryItem[];
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
    router.get(
        '/admin/reports',
        { start_date: start_date.value, end_date: end_date.value },
        { preserveState: true, replace: true }
    );
}

function calculateCategoryPercent(amount: number): string {
    const total = props.reports.totalExpenses || 0;
    if (total <= 0) return '0.0%';
    return ((amount / total) * 100).toFixed(1) + '%';
}
</script>

<template>
    <Head title="System Reports - Super Admin" />

    <div class="p-6 space-y-6 w-full max-w-7xl mx-auto">
        <!-- Header & Date Range Filter -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">
                    System Reports & Financial Analytics
                </h1>
                <p class="text-xs text-neutral-500 mt-1">
                    Platform-wide booking revenue, operational expenses, and court activity overview.
                </p>
            </div>

            <div class="flex items-center gap-2 bg-white dark:bg-neutral-900 p-2 rounded-xl border border-neutral-200 dark:border-neutral-800 text-xs shadow-xs">
                <div class="flex items-center gap-1.5 px-1 text-neutral-400">
                    <Calendar class="w-3.5 h-3.5" />
                    <span>Range:</span>
                </div>
                <input
                    v-model="start_date"
                    type="date"
                    class="p-1.5 rounded-lg border border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-900 dark:text-white"
                />
                <span class="text-neutral-400">to</span>
                <input
                    v-model="end_date"
                    type="date"
                    class="p-1.5 rounded-lg border border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-900 dark:text-white"
                />
                <button
                    @click="filterReports"
                    class="px-3.5 py-1.5 bg-emerald-600 text-white font-semibold rounded-lg hover:bg-emerald-700 transition-colors cursor-pointer"
                >
                    Apply
                </button>
            </div>
        </div>

        <!-- Primary Financial Overview Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <!-- Period Revenue -->
            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Gross Revenue</span>
                    <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                        <TrendingUp class="w-4 h-4" />
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-emerald-600 dark:text-emerald-500">
                    ₱{{ reports.totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
                <p class="text-[11px] text-neutral-400 mt-1">Total revenue from confirmed bookings</p>
            </div>

            <!-- Total Expenses -->
            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Total Expenses</span>
                    <div class="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600">
                        <Receipt class="w-4 h-4" />
                    </div>
                </div>
                <div class="text-3xl font-extrabold text-rose-600 dark:text-rose-500">
                    ₱{{ (reports.totalExpenses ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
                <div class="flex items-center justify-between mt-1">
                    <p class="text-[11px] text-neutral-400">Recorded operational & facility costs</p>
                    <Link
                        href="/admin/expenses"
                        class="text-[11px] font-semibold text-rose-600 dark:text-rose-400 hover:underline inline-flex items-center gap-0.5"
                    >
                        View Expenses &rarr;
                    </Link>
                </div>
            </div>

            <!-- Net Profit / Margin -->
            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Net Operating Balance</span>
                    <div
                        class="p-2 rounded-xl"
                        :class="(reports.netProfit ?? 0) >= 0 ? 'bg-blue-50 dark:bg-blue-950/40 text-blue-600' : 'bg-amber-50 dark:bg-amber-950/40 text-amber-600'"
                    >
                        <ArrowUpRight v-if="(reports.netProfit ?? 0) >= 0" class="w-4 h-4" />
                        <ArrowDownRight v-else class="w-4 h-4" />
                    </div>
                </div>
                <div
                    class="text-3xl font-extrabold"
                    :class="(reports.netProfit ?? 0) >= 0 ? 'text-blue-600 dark:text-blue-400' : 'text-amber-600 dark:text-amber-400'"
                >
                    ₱{{ (reports.netProfit ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
                <p class="text-[11px] text-neutral-400 mt-1">Gross revenue minus total expenses</p>
            </div>
        </div>

        <!-- Booking Activity Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-xs text-neutral-500 font-medium">Total Reservations</span>
                    <div class="text-2xl font-bold text-neutral-900 dark:text-white mt-0.5">{{ reports.totalBookings }}</div>
                    <span class="text-[11px] text-neutral-400">Total bookings scheduled in period</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center text-neutral-600 dark:text-neutral-400">
                    <Calendar class="w-5 h-5" />
                </div>
            </div>

            <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-xs text-neutral-500 font-medium">Approved / Confirmed</span>
                    <div class="text-2xl font-bold text-emerald-600 mt-0.5">{{ reports.approvedBookings }}</div>
                    <span class="text-[11px] text-emerald-600 font-medium">Completed / Successful bookings</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 flex items-center justify-center text-emerald-600">
                    <CheckCircle class="w-5 h-5" />
                </div>
            </div>

            <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs flex items-center justify-between">
                <div>
                    <span class="text-xs text-neutral-500 font-medium">Rejected / Cancelled</span>
                    <div class="text-2xl font-bold text-rose-600 mt-0.5">{{ reports.rejectedBookings + reports.cancelledBookings }}</div>
                    <span class="text-[11px] text-rose-600 font-medium">{{ reports.rejectedBookings }} rejected, {{ reports.cancelledBookings }} cancelled</span>
                </div>
                <div class="w-10 h-10 rounded-xl bg-rose-50 dark:bg-rose-950/40 flex items-center justify-center text-rose-600">
                    <XCircle class="w-5 h-5" />
                </div>
            </div>
        </div>

        <!-- Tables Section: Courts & Expenses -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Court Breakdown Table (2 cols on large screen) -->
            <div class="lg:col-span-2 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                    <div>
                        <h3 class="font-bold text-sm text-neutral-900 dark:text-white">Revenue & Volume Breakdown By Court</h3>
                        <p class="text-xs text-neutral-500">Court performance and booking earnings</p>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-medium bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400">
                        {{ reports.courtBreakdown.length }} Courts
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-semibold uppercase">
                                <th class="py-3 px-3">Court Name</th>
                                <th class="py-3 px-3">Sport Category</th>
                                <th class="py-3 px-3">Reservations</th>
                                <th class="py-3 px-3">Approved</th>
                                <th class="py-3 px-3 text-right">Revenue</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                            <tr v-for="court in reports.courtBreakdown" :key="court.id" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40 transition-colors">
                                <td class="py-3 px-3 font-bold text-neutral-900 dark:text-white">{{ court.name }}</td>
                                <td class="py-3 px-3 text-neutral-600 dark:text-neutral-300">
                                    <span class="px-2 py-0.5 rounded-md bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 font-medium text-[11px]">
                                        {{ court.sport_type }}
                                    </span>
                                </td>
                                <td class="py-3 px-3 text-neutral-800 dark:text-neutral-200 font-medium">{{ court.total_bookings }}</td>
                                <td class="py-3 px-3 font-semibold text-emerald-600">{{ court.approved_count }}</td>
                                <td class="py-3 px-3 text-right font-bold text-neutral-900 dark:text-white">
                                    ₱{{ court.revenue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                </td>
                            </tr>
                            <tr v-if="reports.courtBreakdown.length === 0">
                                <td colspan="5" class="py-8 text-center text-xs text-neutral-400">
                                    No court data found for this period.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Expense Breakdown By Category (1 col on large screen) -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-xs space-y-4">
                <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                    <div>
                        <h3 class="font-bold text-sm text-neutral-900 dark:text-white">Expenses By Category</h3>
                        <p class="text-xs text-neutral-500">Outlay distribution in this date range</p>
                    </div>
                    <Link
                        href="/admin/expenses"
                        class="p-1 text-neutral-400 hover:text-neutral-700 dark:hover:text-white"
                        title="Manage Expenses"
                    >
                        <Receipt class="w-4 h-4" />
                    </Link>
                </div>

                <div v-if="reports.expenseCategories && reports.expenseCategories.length > 0" class="space-y-3">
                    <div
                        v-for="item in reports.expenseCategories"
                        :key="item.category"
                        class="p-3 rounded-xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/30 hover:bg-neutral-100/50 dark:hover:bg-neutral-800/60 transition-colors"
                    >
                        <div class="flex items-center justify-between text-xs">
                            <span class="font-semibold text-neutral-900 dark:text-white">{{ item.label }}</span>
                            <span class="font-bold text-rose-600 dark:text-rose-400">
                                ₱{{ item.total_amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-neutral-400 mt-1">
                            <span>{{ item.count }} {{ item.count === 1 ? 'entry' : 'entries' }}</span>
                            <span class="font-medium text-neutral-600 dark:text-neutral-300">
                                {{ calculateCategoryPercent(item.total_amount) }}
                            </span>
                        </div>
                        <!-- Progress bar -->
                        <div class="w-full bg-neutral-200 dark:bg-neutral-700 h-1.5 rounded-full overflow-hidden mt-2">
                            <div
                                class="bg-rose-500 h-full rounded-full transition-all duration-300"
                                :style="{ width: calculateCategoryPercent(item.total_amount) }"
                            ></div>
                        </div>
                    </div>
                </div>

                <div v-else class="py-12 text-center text-xs text-neutral-400 space-y-2">
                    <Receipt class="w-8 h-8 mx-auto text-neutral-300 dark:text-neutral-700" />
                    <p>No expenses recorded in this period.</p>
                    <Link
                        href="/admin/expenses"
                        class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold hover:underline inline-block mt-1"
                    >
                        + Record an expense
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
