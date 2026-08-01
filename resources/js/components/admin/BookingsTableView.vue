<script setup lang="ts">
import { ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import {
    Calendar,
    Clock,
    ChevronLeft,
    ChevronRight,
    Search,
    Plus,
    CheckCircle,
    XCircle,
    AlertCircle,
    User,
    Building,
    Dumbbell,
    BarChart2,
    DollarSign,
} from '@lucide/vue';

interface CourtOption {
    id: number;
    name: string;
}

interface VenueOption {
    id: number;
    name: string;
}

export interface TableDateHeader {
    dateStr: string;
    dayName: string;
    dayNum: string;
    monthName: string;
    formatted: string;
    isToday: boolean;
}

export interface TableBookingItem {
    id: number;
    reference_code: string;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    total_price: string;
    status: string;
    receipt_url?: string | null;
    notes?: string | null;
    court?: { id: number; name: string; sport_type?: string } | null;
    user?: { id: number; name: string } | null;
}

const props = defineProps<{
    tableDates: TableDateHeader[];
    tableBookings: TableBookingItem[];
    courts: CourtOption[];
    venues?: VenueOption[] | null;
    filters: { search?: string; court_id?: string; status?: string; date?: string; venue_id?: string };
    basePath: string;
    showVenueFilter: boolean;
}>();

const emit = defineEmits<{
    (e: 'select-booking', booking: TableBookingItem): void;
    (e: 'create-booking', payload?: { date?: string; slot?: string }): void;
}>();

const timeSlots = [
    '07:00 AM', '08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM',
    '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM',
    '05:00 PM', '06:00 PM', '07:00 PM', '08:00 PM', '09:00 PM',
    '10:00 PM', '11:00 PM', '12:00 AM', '01:00 AM', '02:00 AM',
];

const selectedDate = ref(props.filters.date || props.tableDates[0]?.dateStr || new Date().toISOString().split('T')[0]);
const court_id = ref(props.filters.court_id || '');
const status = ref(props.filters.status || '');
const venue_id = ref(props.filters.venue_id || '');

function applyFilters() {
    router.get(
        props.basePath,
        {
            view: 'table',
            date: selectedDate.value,
            court_id: court_id.value || undefined,
            status: status.value || undefined,
            venue_id: venue_id.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

function clearFilters() {
    selectedDate.value = new Date().toISOString().split('T')[0];
    court_id.value = '';
    status.value = '';
    venue_id.value = '';
    applyFilters();
}

function shiftDays(daysOffset: number) {
    const current = new Date(selectedDate.value || new Date());
    current.setDate(current.getDate() + daysOffset);
    selectedDate.value = current.toISOString().split('T')[0];
    applyFilters();
}

// Find booking for cell (dateStr, slot)
function getBookingForCell(dateStr: string, slot: string): TableBookingItem | undefined {
    return props.tableBookings.find(
        (b) => b.date === dateStr && Array.isArray(b.time_slots) && b.time_slots.includes(slot)
    );
}

function cellStatusClass(statusStr: string): string {
    switch (statusStr) {
        case 'approved':
        case 'confirmed':
        case 'completed':
            return 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/25';
        case 'pending':
            return 'bg-amber-500/15 text-amber-700 dark:text-amber-300 border-amber-500/30 hover:bg-amber-500/25';
        case 'rejected':
        case 'cancelled':
            return 'bg-rose-500/15 text-rose-700 dark:text-rose-300 border-rose-500/30';
        default:
            return 'bg-neutral-500/15 text-neutral-700 dark:text-neutral-300 border-neutral-500/30';
    }
}

// Summary Calculation per date
function getBookingsForDate(dateStr: string): TableBookingItem[] {
    const map = new Map<number, TableBookingItem>();
    props.tableBookings.forEach((b) => {
        if (b.date === dateStr) {
            map.set(b.id, b);
        }
    });
    return Array.from(map.values());
}

function getCountByStatus(dateStr: string, statusType: 'confirmed' | 'pending' | 'rejected'): number {
    const list = getBookingsForDate(dateStr);
    if (statusType === 'confirmed') {
        return list.filter((b) => b.status === 'approved' || b.status === 'confirmed').length;
    }
    if (statusType === 'pending') {
        return list.filter((b) => b.status === 'pending').length;
    }
    if (statusType === 'rejected') {
        return list.filter((b) => b.status === 'rejected' || b.status === 'cancelled').length;
    }
    return 0;
}

function getTotalCountForDate(dateStr: string): number {
    return getBookingsForDate(dateStr).length;
}

function getTotalPriceForDate(dateStr: string): number {
    const list = getBookingsForDate(dateStr);
    return list
        .filter((b) => b.status === 'approved' || b.status === 'confirmed')
        .reduce((sum, b) => sum + (parseFloat(b.total_price) || 0), 0);
}

function formatPrice(val: number): string {
    return '₱' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

const grandTotals = computed(() => {
    let confirmed = 0;
    let pending = 0;
    let rejected = 0;
    let revenue = 0;
    props.tableDates.forEach((d) => {
        confirmed += getCountByStatus(d.dateStr, 'confirmed');
        pending += getCountByStatus(d.dateStr, 'pending');
        rejected += getCountByStatus(d.dateStr, 'rejected');
        revenue += getTotalPriceForDate(d.dateStr);
    });
    return {
        confirmed,
        pending,
        rejected,
        revenue,
        total: confirmed + pending + rejected,
    };
});
</script>

<template>
    <div class="space-y-6">
        <!-- Controls & Filter Toolbar -->
        <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                <!-- Date Navigation Bar -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="shiftDays(-7)"
                        class="p-2 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 hover:border-emerald-500 hover:text-emerald-600 transition-colors"
                        title="Previous 7 Days"
                    >
                        <ChevronLeft class="size-4" />
                    </button>

                    <div class="flex items-center gap-2">
                        <Calendar class="size-4 text-emerald-600" />
                        <input
                            type="date"
                            v-model="selectedDate"
                            @change="applyFilters"
                            class="rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 px-3 py-1.5 text-xs font-bold text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                        />
                    </div>

                    <button
                        type="button"
                        @click="shiftDays(7)"
                        class="p-2 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 hover:border-emerald-500 hover:text-emerald-600 transition-colors"
                        title="Next 7 Days"
                    >
                        <ChevronRight class="size-4" />
                    </button>

                    <button
                        type="button"
                        @click="selectedDate = new Date().toISOString().split('T')[0]; applyFilters();"
                        class="px-3 py-1.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs font-bold text-neutral-700 dark:text-neutral-300 hover:border-emerald-500 hover:text-emerald-600 transition-colors"
                    >
                        Today
                    </button>
                </div>

                <!-- Filters -->
                <div class="flex flex-wrap items-center gap-2">
                    <select
                        v-if="showVenueFilter && venues?.length"
                        v-model="venue_id"
                        @change="applyFilters"
                        class="px-3 py-1.5 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs font-semibold text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="">All Venues</option>
                        <option v-for="v in venues" :key="v.id" :value="v.id">{{ v.name }}</option>
                    </select>

                    <select
                        v-model="court_id"
                        @change="applyFilters"
                        class="px-3 py-1.5 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs font-semibold text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="">All Courts</option>
                        <option v-for="c in courts" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>

                    <select
                        v-model="status"
                        @change="applyFilters"
                        class="px-3 py-1.5 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs font-semibold text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="">All Statuses</option>
                        <option value="approved">Approved</option>
                        <option value="pending">Pending</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="rejected">Rejected</option>
                    </select>

                    <button
                        v-if="court_id || status || venue_id"
                        @click="clearFilters"
                        class="px-3 py-1.5 bg-neutral-200 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 rounded-xl text-xs font-semibold hover:bg-neutral-300 dark:hover:bg-neutral-700"
                    >
                        Clear
                    </button>
                </div>
            </div>

            <!-- Legend -->
            <div class="flex flex-wrap items-center gap-4 text-xs font-bold pt-2 border-t border-neutral-100 dark:border-neutral-800">
                <div class="flex items-center gap-1.5">
                    <span class="size-2.5 rounded-full bg-emerald-500" />
                    <span class="text-neutral-600 dark:text-neutral-300">Available Slot</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="size-2.5 rounded-full bg-blue-500" />
                    <span class="text-neutral-600 dark:text-neutral-300">Approved / Confirmed</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="size-2.5 rounded-full bg-amber-500" />
                    <span class="text-neutral-600 dark:text-neutral-300">Pending</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="size-2.5 rounded-full bg-rose-500" />
                    <span class="text-neutral-600 dark:text-neutral-300">Rejected / Cancelled</span>
                </div>
            </div>
        </div>

        <!-- Table View Grid Matrix -->
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[900px]">
                <thead>
                    <tr class="border-b border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800/60 text-neutral-500">
                        <!-- First Column: Time Slots Header -->
                        <th class="sticky left-0 z-20 bg-neutral-100 dark:bg-neutral-800 py-3.5 px-4 text-xs font-black uppercase tracking-wider text-neutral-900 dark:text-white border-r border-neutral-200 dark:border-neutral-700 min-w-[130px]">
                            <div class="flex items-center gap-1.5">
                                <Clock class="size-4 text-emerald-600" />
                                <span>Time Slot</span>
                            </div>
                        </th>

                        <!-- Following Columns: Dates -->
                        <th
                            v-for="d in tableDates"
                            :key="d.dateStr"
                            class="py-3 px-3 text-center border-r border-neutral-200 dark:border-neutral-800 min-w-[140px]"
                            :class="{ 'bg-emerald-50/50 dark:bg-emerald-950/20': d.isToday }"
                        >
                            <div class="flex flex-col items-center">
                                <span class="text-[10px] font-extrabold uppercase tracking-wider text-neutral-400">
                                    {{ d.isToday ? 'Today' : d.dayName }}
                                </span>
                                <span class="text-sm font-black text-neutral-900 dark:text-white my-0.5">
                                    {{ d.monthName }} {{ d.dayNum }}
                                </span>
                                <span class="text-[9px] font-mono font-semibold text-neutral-400 opacity-75">
                                    {{ d.dateStr }}
                                </span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800 text-xs">
                    <tr v-for="slot in timeSlots" :key="slot" class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30 transition-colors">
                        <!-- Sticky Time Slot Column -->
                        <td class="sticky left-0 z-10 bg-neutral-50 dark:bg-neutral-900 py-3 px-4 font-mono font-bold text-neutral-900 dark:text-white border-r border-neutral-200 dark:border-neutral-800 whitespace-nowrap">
                            {{ slot }}
                        </td>

                        <!-- Cells for each date column -->
                        <td
                            v-for="d in tableDates"
                            :key="`${d.dateStr}-${slot}`"
                            class="p-1.5 border-r border-neutral-100 dark:border-neutral-800/60 align-top h-16"
                        >
                            <!-- If booking exists for this slot & date -->
                            <div
                                v-if="getBookingForCell(d.dateStr, slot)"
                                @click="emit('select-booking', getBookingForCell(d.dateStr, slot)!)"
                                class="h-full w-full rounded-xl border p-2 flex flex-col justify-between cursor-pointer transition-all duration-150 shadow-xs"
                                :class="cellStatusClass(getBookingForCell(d.dateStr, slot)!.status)"
                            >
                                <div class="flex items-center justify-between gap-1">
                                    <span class="font-bold truncate text-[11px]">
                                        {{ getBookingForCell(d.dateStr, slot)!.name }}
                                    </span>
                                    <span class="text-[9px] font-extrabold uppercase px-1 rounded-sm bg-black/10">
                                        {{ getBookingForCell(d.dateStr, slot)!.status }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-[10px] opacity-90 mt-1">
                                    <span class="truncate font-mono">{{ getBookingForCell(d.dateStr, slot)!.court?.name || 'Court' }}</span>
                                    <span class="font-bold">₱{{ getBookingForCell(d.dateStr, slot)!.total_price }}</span>
                                </div>
                            </div>

                            <!-- If NO booking exists: Available Slot -->
                            <div
                                v-else
                                @click="emit('create-booking', { date: d.dateStr, slot })"
                                class="group h-full w-full rounded-xl border border-dashed border-emerald-300/40 dark:border-emerald-800/40 bg-emerald-50/20 dark:bg-emerald-950/10 hover:border-emerald-500 hover:bg-emerald-50 dark:hover:bg-emerald-950/30 p-2 flex flex-col items-center justify-center cursor-pointer transition-all duration-150"
                            >
                                <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400 group-hover:hidden flex items-center gap-1">
                                    <span class="size-1.5 rounded-full bg-emerald-500" /> Open
                                </span>
                                <span class="hidden group-hover:inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300">
                                    <Plus class="size-3" /> Book
                                </span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Booking Status Summary Section Below Booking Table -->
        <div class="space-y-4 pt-2">
            <!-- Section Header & Quick Metric Badges -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
                <div>
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-neutral-900 dark:text-white flex items-center gap-2">
                        <BarChart2 class="size-4 text-emerald-600" />
                        <span>Booking Status &amp; Daily Revenue Summary</span>
                    </h3>
                    <p class="text-xs text-neutral-500 mt-0.5">
                        Breakdown of booking totals, statuses, and daily total revenue for each date in view.
                    </p>
                </div>

                <!-- Grand Total Metric Badges -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-emerald-200 dark:border-emerald-900/50 bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 text-xs font-bold">
                        <span class="size-2 rounded-full bg-emerald-500" />
                        <span>Confirmed: {{ grandTotals.confirmed }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-amber-200 dark:border-amber-900/50 bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 text-xs font-bold">
                        <span class="size-2 rounded-full bg-amber-500" />
                        <span>Pending: {{ grandTotals.pending }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50 dark:bg-rose-950/40 text-rose-700 dark:text-rose-300 text-xs font-bold">
                        <span class="size-2 rounded-full bg-rose-500" />
                        <span>Rejected: {{ grandTotals.rejected }}</span>
                    </div>
                    <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-teal-300 dark:border-teal-800 bg-teal-100/70 dark:bg-teal-950/60 text-teal-800 dark:text-teal-300 text-xs font-extrabold shadow-2xs">
                        <span>Revenue: {{ formatPrice(grandTotals.revenue) }}</span>
                    </div>
                </div>
            </div>

            <!-- Summary Table Matrix -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="border-b border-neutral-200 dark:border-neutral-800 bg-neutral-100/70 dark:bg-neutral-800/80 text-neutral-500">
                            <th class="sticky left-0 z-20 bg-neutral-100 dark:bg-neutral-800 py-3 px-4 text-xs font-black uppercase tracking-wider text-neutral-900 dark:text-white border-r border-neutral-200 dark:border-neutral-700 min-w-[130px]">
                                Summary Metric
                            </th>
                            <th
                                v-for="d in tableDates"
                                :key="d.dateStr"
                                class="py-2.5 px-3 text-center border-r border-neutral-200 dark:border-neutral-800 min-w-[140px]"
                                :class="{ 'bg-emerald-50/40 dark:bg-emerald-950/20': d.isToday }"
                            >
                                <span class="text-xs font-bold text-neutral-900 dark:text-white block">
                                    {{ d.formatted }}
                                </span>
                                <span class="text-[9px] font-semibold text-neutral-400">
                                    {{ d.isToday ? 'Today' : d.dayName }}
                                </span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800 text-xs font-semibold">
                        <!-- Confirmed Row -->
                        <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30 transition-colors">
                            <td class="sticky left-0 z-10 bg-neutral-50 dark:bg-neutral-900 py-2.5 px-4 text-emerald-700 dark:text-emerald-400 font-bold border-r border-neutral-200 dark:border-neutral-800 flex items-center gap-2">
                                <span class="size-2.5 rounded-full bg-emerald-500" />
                                Confirmed
                            </td>
                            <td v-for="d in tableDates" :key="`conf-${d.dateStr}`" class="py-2.5 px-3 text-center border-r border-neutral-100 dark:border-neutral-800/60 font-bold">
                                <span :class="getCountByStatus(d.dateStr, 'confirmed') > 0 ? 'text-emerald-600 dark:text-emerald-400 bg-emerald-100 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full text-xs font-black' : 'text-neutral-400'">
                                    {{ getCountByStatus(d.dateStr, 'confirmed') }}
                                </span>
                            </td>
                        </tr>

                        <!-- Pending Row -->
                        <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30 transition-colors">
                            <td class="sticky left-0 z-10 bg-neutral-50 dark:bg-neutral-900 py-2.5 px-4 text-amber-700 dark:text-amber-400 font-bold border-r border-neutral-200 dark:border-neutral-800 flex items-center gap-2">
                                <span class="size-2.5 rounded-full bg-amber-500" />
                                Pending
                            </td>
                            <td v-for="d in tableDates" :key="`pend-${d.dateStr}`" class="py-2.5 px-3 text-center border-r border-neutral-100 dark:border-neutral-800/60 font-bold">
                                <span :class="getCountByStatus(d.dateStr, 'pending') > 0 ? 'text-amber-600 dark:text-amber-400 bg-amber-100 dark:bg-amber-950/60 px-2 py-0.5 rounded-full text-xs font-black' : 'text-neutral-400'">
                                    {{ getCountByStatus(d.dateStr, 'pending') }}
                                </span>
                            </td>
                        </tr>

                        <!-- Rejected Row -->
                        <tr class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30 transition-colors">
                            <td class="sticky left-0 z-10 bg-neutral-50 dark:bg-neutral-900 py-2.5 px-4 text-rose-700 dark:text-rose-400 font-bold border-r border-neutral-200 dark:border-neutral-800 flex items-center gap-2">
                                <span class="size-2.5 rounded-full bg-rose-500" />
                                Rejected
                            </td>
                            <td v-for="d in tableDates" :key="`rej-${d.dateStr}`" class="py-2.5 px-3 text-center border-r border-neutral-100 dark:border-neutral-800/60 font-bold">
                                <span :class="getCountByStatus(d.dateStr, 'rejected') > 0 ? 'text-rose-600 dark:text-rose-400 bg-rose-100 dark:bg-rose-950/60 px-2 py-0.5 rounded-full text-xs font-black' : 'text-neutral-400'">
                                    {{ getCountByStatus(d.dateStr, 'rejected') }}
                                </span>
                            </td>
                        </tr>

                        <!-- Total Bookings Row -->
                        <tr class="bg-neutral-100/60 dark:bg-neutral-800/60 font-black">
                            <td class="sticky left-0 z-10 bg-neutral-100 dark:bg-neutral-800 py-2.5 px-4 text-neutral-900 dark:text-white uppercase tracking-wider text-[11px] border-r border-neutral-200 dark:border-neutral-700">
                                Total Bookings
                            </td>
                            <td v-for="d in tableDates" :key="`tot-${d.dateStr}`" class="py-2.5 px-3 text-center border-r border-neutral-200 dark:border-neutral-700 text-neutral-900 dark:text-white font-extrabold text-xs">
                                {{ getTotalCountForDate(d.dateStr) }}
                            </td>
                        </tr>

                        <!-- Total Daily Price / Revenue Row -->
                        <tr class="bg-emerald-50/70 dark:bg-emerald-950/30 font-black border-t-2 border-emerald-500/20">
                            <td class="sticky left-0 z-10 bg-emerald-100/80 dark:bg-emerald-950/80 py-3 px-4 text-emerald-900 dark:text-emerald-300 uppercase tracking-wider text-[11px] border-r border-emerald-200 dark:border-emerald-800 flex items-center gap-1.5">
                                <DollarSign class="size-3.5 text-emerald-600" />
                                Total Revenue (₱)
                            </td>
                            <td v-for="d in tableDates" :key="`price-${d.dateStr}`" class="py-3 px-3 text-center border-r border-emerald-200/60 dark:border-emerald-900/40 text-emerald-700 dark:text-emerald-300 font-black text-xs">
                                {{ formatPrice(getTotalPriceForDate(d.dateStr)) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
