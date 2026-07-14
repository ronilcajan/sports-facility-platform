<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import NotificationMenu from '@/components/dashboard/NotificationMenu.vue';
import {
    Calendar,
    CalendarDays,
    Clock,
    DollarSign,
    CheckCircle,
    XCircle,
    Building,
    Plus,
    ShieldAlert,
    AlertCircle,
    FileText,
} from '@lucide/vue';

interface CourtItem {
    id: number;
    name: string;
    sport_type: string;
    primary_image?: { url: string };
}

interface Stats {
    todayBookingsCount: number;
    pendingCount: number;
    totalBookings: number;
    totalRevenue: number;
}

interface BookingItem {
    id: number;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_path?: string | null;
    receipt_url?: string | null;
    status: string;
    notes?: string;
}

interface UnavailabilityItem {
    id: number;
    date: string;
    start_time?: string;
    end_time?: string;
    all_day: boolean;
    reason?: string;
}

const props = defineProps<{
    assignedCourts: CourtItem[];
    selectedCourt: CourtItem | null;
    hasNoCourts: boolean;
    stats: Stats;
    todayBookings: BookingItem[];
    pendingBookings: BookingItem[];
    unavailabilities: UnavailabilityItem[];
    unreadNotifications: any[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Court Staff Dashboard', href: '/staff/dashboard' },
        ],
    },
});

function selectCourt(courtId: number) {
    router.get('/staff/dashboard', { court_id: courtId }, { preserveState: true });
}

const page = usePage();
const user = computed(() => page.props.auth?.user as any);
const canUpdate = computed(() => {
    return user.value?.is_super_admin || user.value?.is_admin;
});

const actionForm = useForm({
    status: '',
});

function updateStatus(bookingId: number, status: string) {
    if (!canUpdate.value) return;
    actionForm.status = status;
    actionForm.patch(`/staff/bookings/${bookingId}/status`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Court Staff Dashboard" />

    <div class="p-6 space-y-6 max-w-7xl mx-auto">
            <!-- Header bar with Notification Bell & Court Switcher -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-neutral-900 p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 shadow-sm">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 text-xs font-bold">
                            Court Staff Control
                        </span>
                        <span v-if="selectedCourt" class="text-xs font-semibold text-neutral-500">
                            Assigned: {{ selectedCourt.name }}
                        </span>
                    </div>
                    <h1 class="text-xl font-bold text-neutral-900 dark:text-white">
                        {{ selectedCourt ? selectedCourt.name + ' Dashboard' : 'Staff Dashboard' }}
                    </h1>
                </div>

                <div class="flex items-center gap-3">
                    <!-- Court Switcher Dropdown if multiple courts -->
                    <div v-if="assignedCourts && assignedCourts.length > 1" class="flex items-center gap-2">
                        <span class="text-xs text-neutral-500 font-medium">Switch Court:</span>
                        <select
                            :value="selectedCourt?.id"
                            @change="selectCourt(Number(($event.target as HTMLSelectElement).value))"
                            class="rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 px-3 py-1.5 text-xs font-semibold text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        >
                            <option v-for="c in assignedCourts" :key="c.id" :value="c.id">
                                {{ c.name }}
                            </option>
                        </select>
                    </div>

                    <!-- In-App Header Notification Bell -->
                    <NotificationMenu :notifications="unreadNotifications" />
                </div>
            </div>

            <!-- Warning if not assigned to any court -->
            <div v-if="hasNoCourts" class="p-8 text-center rounded-2xl border border-dashed border-amber-300 bg-amber-50/50 dark:bg-amber-950/20 text-amber-800 dark:text-amber-300">
                <AlertCircle class="w-8 h-8 mx-auto mb-2 text-amber-600" />
                <h3 class="font-bold text-base">No Court Assigned</h3>
                <p class="text-xs text-amber-700 dark:text-amber-400 mt-1">You are currently not assigned to manage any court. Please contact the Super Admin to assign you a court.</p>
            </div>

            <template v-else-if="selectedCourt">
                <!-- Metrics Bar -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-1">
                        <span class="text-xs text-neutral-500 font-medium">Today's Reservations</span>
                        <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                            {{ stats.todayBookingsCount }}
                        </div>
                        <span class="text-[11px] text-emerald-600 font-medium">Assigned court schedule</span>
                    </div>

                    <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-1">
                        <span class="text-xs text-neutral-500 font-medium">Pending Approvals</span>
                        <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                            {{ stats.pendingCount }}
                        </div>
                        <span class="text-[11px] text-amber-600 font-medium">Requires staff action</span>
                    </div>

                    <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-1">
                        <span class="text-xs text-neutral-500 font-medium">Assigned Court Revenue</span>
                        <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                            ${{ stats.totalRevenue.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                        </div>
                        <span class="text-[11px] text-neutral-500">Confirmed booking earnings</span>
                    </div>

                    <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-1">
                        <span class="text-xs text-neutral-500 font-medium">Total Lifetime Bookings</span>
                        <div class="text-2xl font-bold text-neutral-900 dark:text-white">
                            {{ stats.totalBookings }}
                        </div>
                        <span class="text-[11px] text-neutral-500">Recorded for {{ selectedCourt.name }}</span>
                    </div>
                </div>

                <!-- Two-Column Section: Pending Requests & Today's Schedule -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Pending Booking Requests Queue -->
                    <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                            <div>
                                <h3 class="font-bold text-neutral-900 dark:text-white text-base">Pending Booking Requests</h3>
                                <p class="text-xs text-neutral-500">Approve or reject customer requests for {{ selectedCourt.name }}</p>
                            </div>
                            <span class="px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 text-xs font-bold">
                                {{ pendingBookings.length }} Pending
                            </span>
                        </div>

                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <div
                                v-for="booking in pendingBookings"
                                :key="booking.id"
                                class="p-4 rounded-xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/40 space-y-2"
                            >
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-neutral-900 dark:text-white">#{{ booking.id }} - {{ booking.name }}</span>
                                    <span class="font-semibold text-emerald-600">${{ booking.total_price }}</span>
                                </div>

                                <div class="text-xs text-neutral-500 flex flex-wrap gap-x-4 gap-y-1">
                                    <span>Date: <strong>{{ booking.date }}</strong></span>
                                    <span>Slots: <strong class="font-mono text-neutral-800 dark:text-neutral-200">{{ booking.time_slots ? booking.time_slots.join(', ') : 'N/A' }}</strong></span>
                                    <span>Phone: {{ booking.phone }}</span>
                                </div>

                                <div class="flex items-center justify-between pt-2 border-t border-neutral-200/50 dark:border-neutral-700/50">
                                    <div>
                                        <a
                                            v-if="booking.receipt_url || booking.receipt_path"
                                            :href="booking.receipt_url || '/storage/' + booking.receipt_path"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline"
                                        >
                                            <FileText class="w-3.5 h-3.5" /> View Receipt
                                        </a>
                                    </div>

                                    <div v-if="canUpdate" class="flex items-center gap-2">
                                        <button
                                            @click="updateStatus(booking.id, 'approved')"
                                            class="px-3 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-medium transition-colors flex items-center gap-1"
                                        >
                                            <CheckCircle class="w-3.5 h-3.5" /> Approve
                                        </button>
                                        <button
                                            @click="updateStatus(booking.id, 'rejected')"
                                            class="px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-medium transition-colors flex items-center gap-1"
                                        >
                                            <XCircle class="w-3.5 h-3.5" /> Reject
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div v-if="pendingBookings.length === 0" class="p-6 text-center text-xs text-neutral-400">
                                No pending booking requests for this court.
                            </div>
                        </div>
                    </div>

                    <!-- Today's Reservations Timeline -->
                    <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-4">
                        <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                            <div>
                                <h3 class="font-bold text-neutral-900 dark:text-white text-base">Today's Reservations</h3>
                                <p class="text-xs text-neutral-500">Confirmed schedule for today</p>
                            </div>
                            <Link href="/staff/schedules" class="text-xs text-emerald-600 hover:underline font-semibold">
                                Full Schedule &rarr;
                            </Link>
                        </div>

                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <div
                                v-for="b in todayBookings"
                                :key="b.id"
                                class="p-3 rounded-xl border border-neutral-100 dark:border-neutral-800 bg-white dark:bg-neutral-800/60 flex items-center justify-between"
                            >
                                <div class="space-y-0.5 text-xs">
                                    <p class="font-bold text-neutral-900 dark:text-white">{{ b.name }}</p>
                                    <p class="font-mono text-neutral-500">{{ b.time_slots ? b.time_slots.join(', ') : '' }}</p>
                                </div>

                                <span
                                    :class="[
                                        'px-2.5 py-1 rounded-full text-[10px] font-bold capitalize',
                                        b.status === 'approved' || b.status === 'confirmed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-300'
                                    ]"
                                >
                                    {{ b.status }}
                                </span>
                            </div>

                            <div v-if="todayBookings.length === 0" class="p-6 text-center text-xs text-neutral-400">
                                No reservations scheduled for today.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Unavailable Dates / Maintenance Quick Blockouts -->
                <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                        <div>
                            <h3 class="font-bold text-neutral-900 dark:text-white text-base">Court Schedule Blackout & Maintenance Dates</h3>
                            <p class="text-xs text-neutral-500">Dates blocked off for maintenance or holidays</p>
                        </div>
                        <Link href="/staff/schedules" class="px-3 py-1.5 bg-neutral-900 dark:bg-neutral-100 text-white dark:text-neutral-900 rounded-lg text-xs font-semibold hover:opacity-90 transition-opacity flex items-center gap-1">
                            <Plus class="w-3.5 h-3.5" /> Block Date
                        </Link>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                        <div
                            v-for="u in unavailabilities"
                            :key="u.id"
                            class="p-3 rounded-xl border border-rose-200 dark:border-rose-900/60 bg-rose-50/50 dark:bg-rose-950/20 text-xs space-y-1"
                        >
                            <div class="flex items-center justify-between text-rose-800 dark:text-rose-300 font-bold">
                                <span>{{ u.date }}</span>
                                <span class="text-[10px] uppercase font-mono">{{ u.all_day ? 'All Day' : u.start_time + ' - ' + u.end_time }}</span>
                            </div>
                            <p class="text-neutral-600 dark:text-neutral-400 text-[11px]">{{ u.reason || 'Maintenance' }}</p>
                        </div>

                        <div v-if="unavailabilities.length === 0" class="col-span-full py-4 text-center text-xs text-neutral-400">
                            No active blackout or maintenance dates set for this court.
                        </div>
                    </div>
                </div>
            </template>
        </div>
</template>
