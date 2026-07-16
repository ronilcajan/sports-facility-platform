<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Clock,
    DollarSign,
    CheckCircle,
    TrendingUp,
    Plus,
    CalendarRange,
    ChevronRight,
} from '@lucide/vue';
import { dashboard } from '@/routes';
import BookingsTable, { type BookingItem } from '@/components/customer/BookingsTable.vue';

const props = defineProps<{
    bookings: BookingItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

// Stats Calculation
const stats = computed(() => {
    const total = props.bookings.length;
    const pending = props.bookings.filter(b => b.status === 'pending').length;
    const confirmed = props.bookings.filter(b => b.status === 'approved' || b.status === 'confirmed').length;
    const totalSpent = props.bookings
        .filter(b => b.status !== 'rejected' && b.status !== 'cancelled')
        .reduce((sum, b) => sum + parseFloat(b.total_price || '0'), 0);

    return {
        total,
        pending,
        confirmed,
        totalSpent: totalSpent.toFixed(2),
    };
});

// Show only the most recent bookings on the dashboard; the full list lives on the Bookings page.
const recentBookings = computed(() => props.bookings.slice(0, 5));
</script>

<template>
    <Head title="Dashboard" />

    <div class="p-6 space-y-6 w-full pb-16">
        <!-- Hero Header -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-gradient-to-r from-emerald-600/10 via-teal-600/5 to-transparent p-6 rounded-2xl border border-emerald-500/10 dark:border-emerald-500/5">
            <div>
                <h1 class="text-2xl font-black text-neutral-900 dark:text-white tracking-tight">Welcome Back!</h1>
                <p class="text-xs text-neutral-500 mt-1">Manage your active court reservations, upload receipts, and check booking statuses.</p>
            </div>
            <Link
                href="/courts"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-emerald-600/20 transition-all hover:translate-y-[-1px] flex items-center gap-1.5"
            >
                <Plus class="w-4 h-4" /> Book a New Court
            </Link>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Stat card: Total bookings -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute right-3 top-3 opacity-10 group-hover:scale-110 transition-transform">
                    <CalendarRange class="w-12 h-12 text-neutral-600 dark:text-neutral-400" />
                </div>
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Total Bookings</span>
                <span class="text-2xl font-black text-neutral-900 dark:text-white mt-1 block">{{ stats.total }}</span>
                <div class="flex items-center gap-1 mt-2 text-[10px] text-neutral-500 font-medium">
                    <span>Active and completed reservations</span>
                </div>
            </div>

            <!-- Stat card: Pending approval -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute right-3 top-3 opacity-10 group-hover:scale-110 transition-transform">
                    <Clock class="w-12 h-12 text-amber-600" />
                </div>
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Pending Approval</span>
                <span class="text-2xl font-black text-amber-600 mt-1 block">{{ stats.pending }}</span>
                <div class="flex items-center gap-1 mt-2 text-[10px] text-amber-600 font-medium">
                    <span v-if="stats.pending > 0" class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-ping"></span> Awaiting staff confirmation
                    </span>
                    <span v-else>All bookings processed</span>
                </div>
            </div>

            <!-- Stat card: Confirmed -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute right-3 top-3 opacity-10 group-hover:scale-110 transition-transform">
                    <CheckCircle class="w-12 h-12 text-emerald-600" />
                </div>
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Confirmed Bookings</span>
                <span class="text-2xl font-black text-emerald-600 mt-1 block">{{ stats.confirmed }}</span>
                <div class="flex items-center gap-1 mt-2 text-[10px] text-emerald-600 font-medium">
                    <span>Approved reservations ready for play</span>
                </div>
            </div>

            <!-- Stat card: Total spent -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm hover:shadow-md transition-shadow relative overflow-hidden group">
                <div class="absolute right-3 top-3 opacity-10 group-hover:scale-110 transition-transform">
                    <DollarSign class="w-12 h-12 text-emerald-600" />
                </div>
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Total Investment</span>
                <span class="text-2xl font-black text-neutral-900 dark:text-white mt-1 block">₱{{ stats.totalSpent }}</span>
                <div class="flex items-center gap-1 mt-2 text-[10px] text-neutral-500 font-medium">
                    <TrendingUp class="w-3.5 h-3.5 text-emerald-600" />
                    <span>Includes approved & pending payments</span>
                </div>
            </div>
        </div>

        <!-- Recent Bookings Section -->
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h3 class="font-black text-neutral-900 dark:text-white text-lg tracking-tight">Recent Bookings</h3>
                    <p class="text-xs text-neutral-500">Your latest reservations. Manage them all on the Bookings page.</p>
                </div>
                <Link
                    href="/my-bookings"
                    class="shrink-0 inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline"
                >
                    View all <ChevronRight class="w-3.5 h-3.5" />
                </Link>
            </div>

            <BookingsTable :bookings="recentBookings" />
        </div>
    </div>
</template>
