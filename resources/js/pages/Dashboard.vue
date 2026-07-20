<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Plus, ChevronRight } from '@lucide/vue';
import { dashboard } from '@/routes';
import BookingsTable, { type BookingItem } from '@/components/customer/BookingsTable.vue';
import StatCardsSlideshow from '@/components/customer/StatCardsSlideshow.vue';

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

        <!-- Stat Cards Slideshow (Mobile Slideshow / Desktop Grid) -->
        <StatCardsSlideshow :stats="stats" />

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
