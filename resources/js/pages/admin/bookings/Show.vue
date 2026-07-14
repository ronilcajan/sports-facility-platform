<script setup lang="ts">
import { Head, Link, useForm, setLayoutProps } from '@inertiajs/vue3';
import { CalendarDays, ArrowLeft, CheckCircle, XCircle, User, Phone, Mail, FileText, DollarSign } from '@lucide/vue';

interface Booking {
    id: number;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    notes?: string;
    total_price: string;
    receipt_path?: string | null;
    receipt_url?: string | null;
    status: string;
    created_at: string;
    court?: { id: number; name: string; sport_type: string };
    user?: { id: number; name: string; email: string };
}

const props = defineProps<{
    booking: Booking;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Super Admin Overview', href: '/admin/dashboard' },
        { title: 'All Bookings', href: '/admin/bookings' },
        { title: `Booking #${props.booking.id}`, href: `/admin/bookings/${props.booking.id}` },
    ],
});

const statusForm = useForm({
    status: props.booking.status,
    notes: props.booking.notes || '',
});

function updateStatus(newStatus: string) {
    statusForm.status = newStatus;
    statusForm.patch(`/admin/bookings/${props.booking.id}/status`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`Booking #${booking.id} Details`" />

    <div class="p-6 space-y-6 max-w-4xl mx-auto">
            <div class="flex items-center justify-between">
                <Link href="/admin/bookings" class="text-xs text-neutral-500 hover:text-neutral-900 dark:hover:text-white flex items-center gap-1">
                    <ArrowLeft class="w-4 h-4" /> Back to Bookings
                </Link>
            </div>

            <div class="p-6 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-4 gap-4">
                    <div>
                        <span class="text-xs font-mono font-bold text-emerald-600">Booking Ref #{{ booking.id }}</span>
                        <h1 class="text-xl font-bold text-neutral-900 dark:text-white">Reservation Details</h1>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            @click="updateStatus('approved')"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow transition-colors flex items-center gap-1.5"
                        >
                            <CheckCircle class="w-4 h-4" /> Approve
                        </button>
                        <button
                            @click="updateStatus('rejected')"
                            class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-xl text-xs font-semibold shadow transition-colors flex items-center gap-1.5"
                        >
                            <XCircle class="w-4 h-4" /> Reject
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs">
                    <!-- Customer Details -->
                    <div class="space-y-3 p-4 rounded-xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/40">
                        <h3 class="font-bold text-sm text-neutral-900 dark:text-white flex items-center gap-2">
                            <User class="w-4 h-4 text-emerald-600" /> Customer Information
                        </h3>
                        <div class="space-y-1.5 text-neutral-700 dark:text-neutral-300">
                            <p><strong>Name:</strong> {{ booking.name }}</p>
                            <p><strong>Email:</strong> {{ booking.email }}</p>
                            <p><strong>Phone:</strong> {{ booking.phone }}</p>
                            <p v-if="booking.user"><strong>Registered User:</strong> {{ booking.user.name }} (ID #{{ booking.user.id }})</p>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="space-y-3 p-4 rounded-xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/40">
                        <h3 class="font-bold text-sm text-neutral-900 dark:text-white flex items-center gap-2">
                            <CalendarDays class="w-4 h-4 text-emerald-600" /> Reservation Info
                        </h3>
                        <div class="space-y-1.5 text-neutral-700 dark:text-neutral-300">
                            <p><strong>Court:</strong> {{ booking.court?.name }}</p>
                            <p><strong>Date:</strong> {{ booking.date }}</p>
                            <p><strong>Time Slots:</strong> <span class="font-mono">{{ booking.time_slots ? booking.time_slots.join(', ') : '' }}</span></p>
                            <p><strong>Total Price:</strong> <span class="font-bold text-emerald-600">${{ booking.total_price }}</span></p>
                            <p><strong>Current Status:</strong> <span class="font-bold capitalize">{{ booking.status }}</span></p>
                        </div>
                    </div>
                </div>

                <div v-if="booking.receipt_path || booking.receipt_url" class="p-4 rounded-xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/40 text-xs space-y-3">
                    <div class="flex items-center justify-between">
                        <h3 class="font-bold text-sm text-neutral-900 dark:text-white flex items-center gap-2">
                            <FileText class="w-4 h-4 text-emerald-600" /> Customer Payment Receipt Document
                        </h3>
                        <a
                            :href="booking.receipt_url || '/storage/' + booking.receipt_path"
                            target="_blank"
                            class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-semibold flex items-center gap-1"
                        >
                            Open Original File &rarr;
                        </a>
                    </div>

                    <!-- Inline image preview if image format -->
                    <div class="mt-2 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700 bg-black/5 max-w-md">
                        <img
                            :src="booking.receipt_url || '/storage/' + booking.receipt_path"
                            alt="Uploaded Customer Receipt"
                            class="max-h-72 w-auto object-contain rounded-lg mx-auto"
                        />
                    </div>
                </div>
            </div>
        </div>
</template>
