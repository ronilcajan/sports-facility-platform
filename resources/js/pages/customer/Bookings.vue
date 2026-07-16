<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Plus } from '@lucide/vue';
import BookingsTable, { type BookingItem } from '@/components/customer/BookingsTable.vue';

defineProps<{
    bookings: BookingItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Bookings', href: '/my-bookings' },
        ],
    },
});
</script>

<template>
    <Head title="My Bookings" />

    <div class="p-6 space-y-6 w-full pb-16">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-gradient-to-r from-emerald-600/10 via-teal-600/5 to-transparent p-6 rounded-2xl border border-emerald-500/10 dark:border-emerald-500/5">
            <div>
                <h1 class="text-2xl font-black text-neutral-900 dark:text-white tracking-tight">My Bookings</h1>
                <p class="text-xs text-neutral-500 mt-1">Track status, update details, or cancel your pending court reservations.</p>
            </div>
            <Link
                href="/courts"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-emerald-600/20 transition-all hover:translate-y-[-1px] flex items-center gap-1.5"
            >
                <Plus class="w-4 h-4" /> Book a New Court
            </Link>
        </div>

        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm space-y-4">
            <div>
                <h3 class="font-black text-neutral-900 dark:text-white text-lg tracking-tight">Your Booking History</h3>
                <p class="text-xs text-neutral-500">All your reservations in one place.</p>
            </div>

            <BookingsTable :bookings="bookings" />
        </div>
    </div>
</template>
