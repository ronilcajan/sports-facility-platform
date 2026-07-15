<script setup lang="ts">
import { Head, Link, setLayoutProps } from '@inertiajs/vue3';
import { ArrowLeft, User, CalendarDays } from '@lucide/vue';

interface UserDetail {
    id: number;
    name: string;
    email: string;
    roles: string[];
    created_at: string;
}

interface Booking {
    id: number;
    date: string;
    time_slots: string[];
    total_price: string;
    status: string;
    court?: { id: number; name: string };
}

interface PaginatedBookings {
    data: Booking[];
}

const props = defineProps<{
    user: UserDetail;
    bookings: PaginatedBookings;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Super Admin Overview', href: '/admin/dashboard' },
        { title: 'User Accounts', href: '/admin/users' },
        { title: props.user.name, href: `/admin/users/${props.user.id}` },
    ],
});
</script>

<template>
    <Head :title="`${user.name} - User Profile & History`" />

    <div class="p-6 space-y-6 max-w-5xl mx-auto">
            <Link href="/admin/users" class="text-xs text-neutral-500 hover:text-neutral-900 dark:hover:text-white flex items-center gap-1">
                <ArrowLeft class="w-4 h-4" /> Back to Users
            </Link>

            <!-- Profile Summary Card -->
            <div class="p-6 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span v-for="r in user.roles" :key="r" class="px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 text-xs font-bold capitalize">
                            {{ r }}
                        </span>
                    </div>
                    <h1 class="text-xl font-bold text-neutral-900 dark:text-white">{{ user.name }}</h1>
                    <p class="text-xs text-neutral-500">{{ user.email }} &bull; Registered {{ user.created_at }}</p>
                </div>
            </div>

            <!-- Booking History Section -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-4">
                <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                    <h3 class="font-bold text-sm text-neutral-900 dark:text-white flex items-center gap-2">
                        <CalendarDays class="w-4 h-4 text-emerald-600" /> Customer Booking History
                    </h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-semibold uppercase">
                                <th class="py-2.5 px-3">Booking ID</th>
                                <th class="py-2.5 px-3">Court</th>
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Time Slots</th>
                                <th class="py-2.5 px-3">Total Amount</th>
                                <th class="py-2.5 px-3">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                            <tr v-for="b in bookings.data" :key="b.id" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40">
                                <td class="py-3 px-3 font-mono font-bold">#{{ b.id }}</td>
                                <td class="py-3 px-3 font-medium text-neutral-900 dark:text-white">{{ b.court?.name || 'N/A' }}</td>
                                <td class="py-3 px-3 text-neutral-600 dark:text-neutral-300">{{ b.date }}</td>
                                <td class="py-3 px-3 font-mono text-[11px] text-neutral-500">{{ b.time_slots ? b.time_slots.join(', ') : '' }}</td>
                                <td class="py-3 px-3 font-bold text-emerald-600">₱{{ b.total_price }}</td>
                                <td class="py-3 px-3 font-semibold capitalize">{{ b.status }}</td>
                            </tr>

                            <tr v-if="bookings.data.length === 0">
                                <td colSpan="6" class="py-8 text-center text-xs text-neutral-400">No booking history recorded for this user.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</template>
