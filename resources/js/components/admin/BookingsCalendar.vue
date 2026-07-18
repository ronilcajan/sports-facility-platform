<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, CalendarCheck, FileText, X, CheckCircle, XCircle, Trash2, Dumbbell } from '@lucide/vue';

import BookingDetailModal, { type BookingDetail } from '@/components/admin/BookingDetailModal.vue';

interface CourtRef {
    id: number;
    name: string;
}

interface BoardBooking {
    id: number;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    total_price: string;
    status: string;
    receipt_url?: string | null;
    receipt_path?: string | null;
    notes?: string | null;
    court: CourtRef | null;
}

interface Day {
    date: string;
    weekday: string;
    dayNum: string;
    month: string;
    isToday: boolean;
    bookings: BoardBooking[];
}

interface CalendarWindow {
    start: string;
    prev: string;
    next: string;
    today: string;
    isToday: boolean;
}

const props = withDefaults(
    defineProps<{
        days: Day[];
        courts: { id: number; name: string }[];
        venues?: { id: number; name: string }[] | null;
        filters: { court_id?: string; status?: string; venue_id?: string };
        window: CalendarWindow;
        basePath: string;
        canDelete: boolean;
        showVenueFilter: boolean;
        canUpdate?: boolean;
    }>(),
    {
        canUpdate: true,
    }
);

const canUpdateStatus = computed(() => props.canUpdate !== false);

const courtId = ref(props.filters.court_id || '');
const status = ref(props.filters.status || '');
const venueId = ref(props.filters.venue_id || '');

function navigate(overrides: Record<string, string> = {}) {
    router.get(
        props.basePath,
        {
            view: 'calendar',
            court_id: courtId.value,
            status: status.value,
            venue_id: venueId.value,
            start: props.window.start,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

// Mobile: which day column is visible. Default to today if it's in the window.
const activeDayIndex = ref(Math.max(0, props.days.findIndex((d) => d.isToday)));

// Parse "hh:mm AM/PM" into minutes for sorting.
function slotMinutes(slot: string): number {
    const match = /(\d{1,2}):(\d{2})\s*(AM|PM)?/i.exec(slot || '');
    if (!match) return 0;
    let hours = parseInt(match[1], 10) % 12;
    if (match[3]?.toUpperCase() === 'PM') hours += 12;
    return hours * 60 + parseInt(match[2], 10);
}

function earliestSlot(booking: BoardBooking): number {
    if (!booking.time_slots?.length) return 0;
    return Math.min(...booking.time_slots.map(slotMinutes));
}

interface CourtGroup {
    court: CourtRef | null;
    bookings: BoardBooking[];
}

// Group a day's bookings by court, sorting groups and cards by earliest time.
function groupByCourt(bookings: BoardBooking[]): CourtGroup[] {
    const groups = new Map<number, CourtGroup>();
    for (const booking of bookings) {
        const key = booking.court?.id ?? 0;
        if (!groups.has(key)) {
            groups.set(key, { court: booking.court, bookings: [] });
        }
        groups.get(key)!.bookings.push(booking);
    }
    const result = [...groups.values()];
    for (const group of result) {
        group.bookings.sort((a, b) => earliestSlot(a) - earliestSlot(b));
    }
    return result.sort((a, b) => (a.court?.name || '').localeCompare(b.court?.name || ''));
}

const dayGroups = computed(() => props.days.map((day) => groupByCourt(day.bookings)));

const confirmedStatuses = ['approved', 'confirmed', 'completed'];

function dayTotalCount(day: Day): number {
    return day.bookings.filter((b) => confirmedStatuses.includes(b.status)).length;
}

function dayTotalAmount(day: Day): number {
    return day.bookings
        .filter((b) => confirmedStatuses.includes(b.status))
        .reduce((sum, b) => sum + (parseFloat(b.total_price) || 0), 0);
}

function statusClasses(s: string): string {
    if (s === 'approved' || s === 'confirmed') return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300';
    if (s === 'pending') return 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300';
    if (s === 'completed') return 'bg-neutral-200 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-300';
    return 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300';
}

function borderClasses(s: string): string {
    if (s === 'approved' || s === 'confirmed') return 'border-l-emerald-500';
    if (s === 'pending') return 'border-l-amber-500';
    if (s === 'completed') return 'border-l-neutral-400';
    return 'border-l-rose-500';
}

// Detail / action modal
const selected = ref<BoardBooking | null>(null);
const actionForm = useForm({ status: '' });

function openBooking(booking: BoardBooking) {
    selected.value = booking;
}

function closeModal() {
    selected.value = null;
}

const selectedBookingDetail = computed<BookingDetail | null>(() => {
    if (!selected.value) return null;
    return {
        id: selected.value.id,
        reference_code: `DY-RESRV-${String(selected.value.id).padStart(6, '0')}`,
        customer_name: selected.value.name,
        email: selected.value.email,
        phone: selected.value.phone,
        date: selected.value.date,
        time_slots: selected.value.time_slots,
        total_price: selected.value.total_price,
        receipt_url: selected.value.receipt_url || (selected.value.receipt_path ? `/storage/${selected.value.receipt_path}` : null),
        status: selected.value.status,
        notes: selected.value.notes,
        court_name: selected.value.court?.name || 'Assigned Court',
    };
});

function updateStatus(newStatus: string) {
    if (!selected.value) return;
    actionForm.status = newStatus;
    actionForm.patch(`${props.basePath}/${selected.value.id}/status`, {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function deleteBooking() {
    if (!selected.value) return;
    if (!confirm('Delete this booking entry?')) return;
    actionForm.delete(`${props.basePath}/${selected.value.id}`, {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}
</script>

<template>
    <div class="space-y-4">
        <!-- Filters + window navigation -->
        <div class="flex flex-col gap-3 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-2">
                <select
                    v-if="showVenueFilter"
                    v-model="venueId"
                    @change="navigate()"
                    class="rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 px-3 py-2 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                >
                    <option value="">All Venues</option>
                    <option v-for="v in venues || []" :key="v.id" :value="v.id">{{ v.name }}</option>
                </select>

                <select
                    v-model="courtId"
                    @change="navigate()"
                    class="rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 px-3 py-2 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                >
                    <option value="">All Courts</option>
                    <option v-for="c in courts" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>

                <select
                    v-model="status"
                    @change="navigate()"
                    class="rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 px-3 py-2 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                >
                    <option value="">Active (default)</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="completed">Completed</option>
                    <option value="rejected">Rejected</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="flex items-center gap-2">
                <button
                    @click="navigate({ start: window.prev })"
                    class="inline-flex items-center gap-1 rounded-xl border border-neutral-300 dark:border-neutral-700 px-2.5 py-2 text-xs font-semibold text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800"
                    title="Previous 5 days"
                >
                    <ChevronLeft class="w-4 h-4" /> Prev
                </button>
                <button
                    @click="navigate({ start: window.today })"
                    :disabled="window.isToday"
                    class="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-700 disabled:opacity-40"
                >
                    Today
                </button>
                <button
                    @click="navigate({ start: window.next })"
                    class="inline-flex items-center gap-1 rounded-xl border border-neutral-300 dark:border-neutral-700 px-2.5 py-2 text-xs font-semibold text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800"
                    title="Next 5 days"
                >
                    Next <ChevronRight class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Mobile: day chip strip -->
        <div class="flex gap-2 overflow-x-auto pb-1 lg:hidden">
            <button
                v-for="(day, i) in days"
                :key="day.date"
                @click="activeDayIndex = i"
                :class="[
                    'flex min-w-[80px] shrink-0 flex-col items-center rounded-xl border px-3 py-2 text-center transition-colors',
                    i === activeDayIndex
                        ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40'
                        : 'border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900',
                ]"
            >
                <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">{{ day.weekday }}</span>
                <span class="text-base font-black text-neutral-900 dark:text-white">{{ day.dayNum }}</span>
                <span class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400">₱{{ dayTotalAmount(day).toLocaleString('en-US', { minimumFractionDigits: 0 }) }}</span>
                <span class="text-[9px] font-medium text-neutral-400">{{ dayTotalCount(day) }} {{ dayTotalCount(day) === 1 ? 'booking' : 'bkgs' }}</span>
                <span v-if="day.isToday" class="mt-0.5 h-1 w-1 rounded-full bg-emerald-500"></span>
            </button>
        </div>

        <!-- Desktop grid (5 columns) / Mobile single active column -->
        <div class="grid grid-cols-1 gap-3 lg:grid-cols-5">
            <div
                v-for="(day, i) in days"
                :key="day.date"
                :class="[
                    'rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50/60 dark:bg-neutral-900/40 p-3',
                    i === activeDayIndex ? 'block' : 'hidden lg:block',
                ]"
            >
                <!-- Column header (desktop) -->
                <div class="mb-3 hidden flex-col border-b border-neutral-200/70 dark:border-neutral-800 pb-2 lg:flex">
                    <div class="flex items-center justify-between">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">{{ day.weekday }}</span>
                        <span v-if="day.isToday" class="rounded-full bg-emerald-100 px-1.5 py-0.5 text-[9px] font-bold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Today</span>
                    </div>

                    <div class="mt-1 flex items-baseline justify-between gap-1 flex-wrap">
                        <div class="text-lg font-black text-neutral-900 dark:text-white leading-none">
                            {{ day.dayNum }} <span class="text-xs font-semibold text-neutral-400">{{ day.month }}</span>
                        </div>
                        <div class="flex items-center gap-1 text-xs">
                            <span class="font-bold text-emerald-600 dark:text-emerald-400">
                                ₱{{ dayTotalAmount(day).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                            </span>
                            <span class="text-[10px] font-medium text-neutral-500 dark:text-neutral-400">
                                ({{ dayTotalCount(day) }})
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Column header (mobile active day) -->
                <div class="mb-3 flex items-center justify-between border-b border-neutral-200/70 dark:border-neutral-800 pb-2 lg:hidden">
                    <div class="flex items-baseline gap-2 flex-wrap">
                        <span class="text-base font-black text-neutral-900 dark:text-white">{{ day.weekday }}, {{ day.month }} {{ day.dayNum }}</span>
                        <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">₱{{ dayTotalAmount(day).toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>
                        <span class="text-[10px] font-medium text-neutral-500">({{ dayTotalCount(day) }} {{ dayTotalCount(day) === 1 ? 'booking' : 'bookings' }})</span>
                    </div>
                    <span v-if="day.isToday" class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Today</span>
                </div>

                <div class="space-y-3">
                    <div v-for="group in dayGroups[i]" :key="group.court?.id ?? 0">
                        <div class="mb-1.5 flex items-center gap-1 text-[11px] font-bold text-neutral-500 dark:text-neutral-400">
                            <Dumbbell class="w-3 h-3 text-emerald-600" />
                            {{ group.court?.name || 'Unassigned' }}
                        </div>
                        <div class="space-y-2">
                            <button
                                v-for="booking in group.bookings"
                                :key="booking.id"
                                @click="openBooking(booking)"
                                :class="['w-full rounded-xl border border-l-4 border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-2.5 text-left shadow-sm transition-shadow hover:shadow-md', borderClasses(booking.status)]"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <span class="truncate text-xs font-bold text-neutral-900 dark:text-white">{{ booking.name }}</span>
                                    <span :class="['shrink-0 rounded-full px-1.5 py-0.5 text-[9px] font-bold capitalize', statusClasses(booking.status)]">{{ booking.status }}</span>
                                </div>
                                <div class="mt-1 font-mono text-[10px] text-neutral-500">{{ booking.time_slots?.join(', ') || 'N/A' }}</div>
                                <div class="mt-0.5 text-[11px] font-bold text-emerald-600">₱{{ booking.total_price }}</div>
                            </button>
                        </div>
                    </div>

                    <div v-if="day.bookings.length === 0" class="flex flex-col items-center gap-1 py-6 text-center text-[11px] text-neutral-400">
                        <CalendarCheck class="w-6 h-6 text-neutral-300 dark:text-neutral-700" />
                        No bookings
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking detail / action modal -->
        <BookingDetailModal
            :is-open="!!selected"
            :booking="selectedBookingDetail"
            :update-route-prefix="basePath"
            :can-update="canUpdateStatus"
            @close="closeModal"
        />
    </div>
</template>
