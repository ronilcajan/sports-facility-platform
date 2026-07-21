<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Shield,
    Dumbbell,
    CalendarDays,
    DollarSign,
    Users,
    UserCheck,
    Clock,
    CheckCircle,
    XCircle,
    ArrowUpRight,
    TrendingUp,
    FileText,
} from '@lucide/vue';

import BookingDetailModal, { type BookingDetail } from '@/components/admin/BookingDetailModal.vue';
import InteractiveAreaChart, { type DailyTrendItem } from '@/components/admin/InteractiveAreaChart.vue';
import DistributionPieChart, { type SportBreakdownItem, type StatusBreakdownItem } from '@/components/admin/DistributionPieChart.vue';
import { ref } from 'vue';

interface Stats {
    totalCourts: number;
    activeCourts: number;
    totalBookings: number;
    pendingBookings: number;
    totalRevenue: number;
    totalCustomers: number;
}

interface CourtSummary {
    id: number;
    name: string;
    sport_type: string;
    status: string;
    is_active: boolean;
    staff_count: number;
    total_bookings: number;
    total_revenue: number;
}

interface RecentBooking {
    id: number;
    reference_code?: string;
    customer_name: string;
    email: string;
    phone: string;
    court_name: string;
    sport_type?: string;
    venue_name?: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_url?: string | null;
    status: string;
    notes?: string | null;
    created_at: string;
}

const props = defineProps<{
    stats: Stats;
    dailyTrend: DailyTrendItem[];
    sportTypesBreakdown: SportBreakdownItem[];
    statusBreakdown: StatusBreakdownItem[];
    courtsSummary: CourtSummary[];
    recentBookings: RecentBooking[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Super Admin Overview', href: '/admin/dashboard' },
        ],
    },
});

const isDetailModalOpen = ref(false);
const selectedBookingForModal = ref<BookingDetail | null>(null);

function openBookingDetails(booking: RecentBooking) {
    selectedBookingForModal.value = booking as any;
    isDetailModalOpen.value = true;
}

const statusForm = useForm({
    status: '',
});

function quickUpdateStatus(bookingId: number, status: string) {
    statusForm.status = status;
    statusForm.patch(`/admin/bookings/${bookingId}/status`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Super Admin Dashboard" />

    <div class="p-6 space-y-8 w-full">
            <!-- Header Banner -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-gradient-to-r from-emerald-950 via-neutral-900 to-neutral-900 text-white p-6 rounded-2xl border border-emerald-900/50 shadow-xl">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-bold border border-emerald-500/30">
                            Super Admin Console
                        </span>
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight">System Administrative Control Hub</h1>
                    <p class="text-xs text-neutral-400">Manage all facility courts, global bookings, user history, staff assignments, and promotional content.</p>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        href="/admin/courts/create"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-xl text-xs font-semibold shadow transition-all flex items-center gap-2"
                    >
                        <Dumbbell class="w-4 h-4" />
                        <span>Add New Court</span>
                    </Link>
                </div>
            </div>

            <!-- Metrics Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Total System Revenue</span>
                        <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                            <DollarSign class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                        ₱{{ stats.totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </div>
                    <span class="text-[11px] text-emerald-600 dark:text-emerald-400 font-medium flex items-center gap-1">
                        <TrendingUp class="w-3.5 h-3.5" /> All confirmed & completed bookings
                    </span>
                </div>

                <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Courts Managed</span>
                        <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                            <Dumbbell class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                        {{ stats.totalCourts }}
                    </div>
                    <span class="text-[11px] text-neutral-500">
                        {{ stats.activeCourts }} Active Courts operational
                    </span>
                </div>

                <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Total Bookings</span>
                        <div class="p-2 rounded-xl bg-sky-50 text-sky-600 dark:bg-sky-950/60 dark:text-sky-400">
                            <CalendarDays class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                        {{ stats.totalBookings }}
                    </div>
                    <span class="text-[11px] text-amber-600 dark:text-amber-400 font-medium">
                        {{ stats.pendingBookings }} Pending Approval
                    </span>
                </div>

                <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-medium text-neutral-500 dark:text-neutral-400">Registered Customers</span>
                        <div class="p-2 rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-950/60 dark:text-purple-400">
                            <Users class="w-5 h-5" />
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                        {{ stats.totalCustomers }}
                    </div>
                    <span class="text-[11px] text-neutral-500">
                        Active facility user accounts
                    </span>
                </div>
            </div>

            <!-- Super Admin Interactive Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Interactive Area Chart (2 Cols) -->
                <div class="lg:col-span-2">
                    <InteractiveAreaChart :daily-trend="props.dailyTrend" />
                </div>

                <!-- Distribution Pie / Donut Chart (1 Col) -->
                <div class="lg:col-span-1">
                    <DistributionPieChart
                        :sport-types-breakdown="props.sportTypesBreakdown"
                        :status-breakdown="props.statusBreakdown"
                    />
                </div>
            </div>

            <!-- Court Overview & Quick Matrix -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Courts Matrix -->
                <div class="lg:col-span-2 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                        <div>
                            <h3 class="font-bold text-neutral-900 dark:text-white text-base">Facility Courts Matrix</h3>
                            <p class="text-xs text-neutral-500">Overview of active court status, staff assignments, and earnings.</p>
                        </div>
                        <Link href="/admin/courts" class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold hover:underline flex items-center gap-1">
                            <span>Manage All</span>
                            <ArrowUpRight class="w-3.5 h-3.5" />
                        </Link>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse text-xs">
                            <thead>
                                <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-semibold uppercase">
                                    <th class="py-2.5 px-3">Court Name</th>
                                    <th class="py-2.5 px-3">Sport</th>
                                    <th class="py-2.5 px-3">Status</th>
                                    <th class="py-2.5 px-3">Assigned Staff</th>
                                    <th class="py-2.5 px-3 text-right">Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                                <tr v-for="court in courtsSummary" :key="court.id" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40 transition-colors">
                                    <td class="py-3 px-3 font-semibold text-neutral-900 dark:text-white">
                                        <Link :href="`/admin/courts/${court.id}`" class="hover:text-emerald-600">
                                            {{ court.name }}
                                        </Link>
                                    </td>
                                    <td class="py-3 px-3 text-neutral-600 dark:text-neutral-300">
                                        {{ court.sport_type }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <span
                                            :class="[
                                                'px-2 py-0.5 rounded-full text-[10px] font-bold',
                                                court.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                            ]"
                                        >
                                            {{ court.status }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-neutral-600 dark:text-neutral-300">
                                        <span class="inline-flex items-center gap-1 bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 rounded-md font-mono">
                                            <UserCheck class="w-3 h-3 text-emerald-600" />
                                            {{ court.staff_count }} Staff
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-neutral-900 dark:text-white">
                                        ₱{{ court.total_revenue.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- System Navigation Quick Cards -->
                <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-4">
                    <h3 class="font-bold text-neutral-900 dark:text-white text-base">Admin Controls</h3>

                    <div class="space-y-2.5">
                        <Link
                            href="/admin/courts"
                            class="flex items-center justify-between p-3 rounded-xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/40 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                    <Dumbbell class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-neutral-900 dark:text-white">Courts & Media</p>
                                    <p class="text-[10px] text-neutral-500">Edit courts & hero images</p>
                                </div>
                            </div>
                            <ArrowUpRight class="w-4 h-4 text-neutral-400" />
                        </Link>

                        <Link
                            href="/admin/bookings"
                            class="flex items-center justify-between p-3 rounded-xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/40 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300">
                                    <CalendarDays class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-neutral-900 dark:text-white">Bookings Management</p>
                                    <p class="text-[10px] text-neutral-500">Approve/reject all reservations</p>
                                </div>
                            </div>
                            <ArrowUpRight class="w-4 h-4 text-neutral-400" />
                        </Link>

                        <Link
                            href="/admin/staff"
                            class="flex items-center justify-between p-3 rounded-xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/40 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300">
                                    <UserCheck class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-neutral-900 dark:text-white">Court Staff Accounts</p>
                                    <p class="text-[10px] text-neutral-500">Assign staff to courts</p>
                                </div>
                            </div>
                            <ArrowUpRight class="w-4 h-4 text-neutral-400" />
                        </Link>

                        <Link
                            href="/admin/users"
                            class="flex items-center justify-between p-3 rounded-xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/40 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors"
                        >
                            <div class="flex items-center gap-3">
                                <div class="p-2 rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300">
                                    <Users class="w-4 h-4" />
                                </div>
                                <div>
                                    <p class="text-xs font-semibold text-neutral-900 dark:text-white">User Accounts</p>
                                    <p class="text-[10px] text-neutral-500">View customer histories</p>
                                </div>
                            </div>
                            <ArrowUpRight class="w-4 h-4 text-neutral-400" />
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings Table -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                    <h3 class="font-bold text-neutral-900 dark:text-white text-base">Recent Bookings Across All Courts</h3>
                    <Link href="/admin/bookings" class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold hover:underline">
                        View All Bookings &rarr;
                    </Link>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-semibold uppercase">
                                <th class="py-2.5 px-3">Booking ID</th>
                                <th class="py-2.5 px-3">Customer</th>
                                <th class="py-2.5 px-3">Court</th>
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Time Slots</th>
                                <th class="py-2.5 px-3">Total</th>
                                <th class="py-2.5 px-3">Status</th>
                                <th class="py-2.5 px-3">Receipt</th>
                                <th class="py-2.5 px-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                            <tr v-for="b in recentBookings" :key="b.id" @click="openBookingDetails(b)" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40 transition-colors cursor-pointer">
                                <td class="py-3 px-3 font-mono font-bold text-neutral-900 dark:text-white">
                                    #{{ b.id }}
                                </td>
                                <td class="py-3 px-3 font-medium text-neutral-900 dark:text-white">
                                    {{ b.customer_name }}
                                </td>
                                <td class="py-3 px-3 text-neutral-600 dark:text-neutral-300">
                                    {{ b.court_name }}
                                </td>
                                <td class="py-3 px-3 font-semibold text-emerald-600 dark:text-emerald-400 underline decoration-dotted">
                                    {{ b.date }}
                                </td>
                                <td class="py-3 px-3 font-mono text-[11px] text-neutral-500">
                                    {{ b.time_slots ? b.time_slots.join(', ') : 'N/A' }}
                                </td>
                                <td class="py-3 px-3 font-bold text-neutral-900 dark:text-white">
                                    ₱{{ b.total_price }}
                                </td>
                                <td class="py-3 px-3">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-bold capitalize',
                                            b.status === 'approved' || b.status === 'confirmed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' :
                                            b.status === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                        ]"
                                    >
                                        {{ b.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-3" @click.stop>
                                    <a
                                        v-if="b.receipt_url"
                                        :href="b.receipt_url"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline"
                                    >
                                        <FileText class="w-3.5 h-3.5" /> View
                                    </a>
                                    <span v-else class="text-neutral-400 text-[11px]">N/A</span>
                                </td>
                                <td class="py-3 px-3 text-right" @click.stop>
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            v-if="b.status === 'pending'"
                                            @click="quickUpdateStatus(b.id, 'approved')"
                                            class="p-1 text-emerald-600 hover:text-emerald-700 dark:hover:text-emerald-400 transition-colors"
                                            title="Approve Booking"
                                        >
                                            <CheckCircle class="w-4 h-4" />
                                        </button>
                                        <button
                                            v-if="b.status === 'pending'"
                                            @click="quickUpdateStatus(b.id, 'rejected')"
                                            class="p-1 text-rose-600 hover:text-rose-700 dark:hover:text-rose-400 transition-colors"
                                            title="Reject Booking"
                                        >
                                            <XCircle class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openBookingDetails(b)"
                                            class="p-1 text-neutral-400 hover:text-neutral-900 dark:hover:text-white"
                                            title="View Full Booking Details"
                                        >
                                            <ArrowUpRight class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Booking Details Modal -->
            <BookingDetailModal
                :is-open="isDetailModalOpen"
                :booking="selectedBookingForModal"
                update-route-prefix="/admin/bookings"
                @close="isDetailModalOpen = false"
            />
        </div>
</template>
