<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import {
    Calendar,
    Clock,
    CheckCircle,
    XCircle,
    Dumbbell,
    CalendarCheck,
} from '@lucide/vue';
import type { CatalogVenue } from '@/components/site/SiteVenueCard.vue';
import type { PublicCourt } from '@/types';
import { getMergedTimeSlots } from '@/utils/timeSlots';
import { useCourtAvailability } from '@/composables/useCourtAvailability';

const props = defineProps<{
    venue: CatalogVenue;
}>();

const emit = defineEmits<{
    (e: 'book-court', court: PublicCourt, date?: string, slot?: string): void;
    (e: 'date-selected', date: string): void;
}>();

// Date selection state
function toDateKey(d: Date): string {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

function parseLocalDate(dateStr: string): Date {
    if (!dateStr) return new Date();
    const parts = dateStr.split('-').map(Number);
    if (parts.length === 3 && !isNaN(parts[0]) && !isNaN(parts[1]) && !isNaN(parts[2])) {
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }
    return new Date(dateStr);
}

const todayDateKey = computed(() => toDateKey(new Date()));
const selectedDate = ref<string>(todayDateKey.value);
const selectedCourtId = ref<number | null>(null);

const formattedSelectedDate = computed(() => {
    if (!selectedDate.value) return '';
    const d = parseLocalDate(selectedDate.value);
    return d.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });
});

function getSlotPriceForCourt(court: PublicCourt, slot: string): number {
    const customPrices = court.slot_prices;
    if (customPrices && customPrices[slot] !== undefined && customPrices[slot] !== null) {
        const val = parseFloat(String(customPrices[slot]));
        if (!isNaN(val) && val > 0) {
            return val;
        }
    }
    const base = parseFloat(court.base_price || '0');
    return isNaN(base) ? 0 : base;
}

// Realtime availability map: courtId -> array of booked slot strings
const {
    isLoading,
    fetchAvailability: loadAvailability,
    slotsForCourt,
} = useCourtAvailability();

// Generate 14 upcoming days for quick selector
const upcomingDays = computed(() => {
    const list: { dateStr: string; dayName: string; dayNum: string; monthName: string; isToday: boolean }[] = [];
    const base = new Date();
    base.setHours(0, 0, 0, 0);

    for (let i = 0; i < 14; i++) {
        const d = new Date(base);
        d.setDate(base.getDate() + i);
        const dateStr = toDateKey(d);
        list.push({
            dateStr,
            dayName: d.toLocaleDateString('en-US', { weekday: 'short' }),
            dayNum: String(d.getDate()),
            monthName: d.toLocaleDateString('en-US', { month: 'short' }),
            isToday: dateStr === todayDateKey.value,
        });
    }
    return list;
});

// Time slots (Default open hours + custom admin created slots)
const activeTimeSlots = computed(() => {
    let customPricesList: Record<string, any>[] = [];
    if (props.venue?.courts) {
        props.venue.courts.forEach(c => {
            if (c.slot_prices) customPricesList.push(c.slot_prices);
        });
    }
    const combined = customPricesList.reduce((acc, prices) => ({ ...acc, ...prices }), {});
    return getMergedTimeSlots(combined);
});

function parseSlotHour(slot: string): number {
    const [time, period] = slot.split(' ');
    let hour = parseInt(time.split(':')[0], 10);
    if (period === 'PM' && hour !== 12) hour += 12;
    if (period === 'AM' && hour === 12) hour = 0;
    return hour;
}

function slotPeriodLabel(slot: string): string {
    const h = parseSlotHour(slot);
    if (h >= 5 && h < 12) return 'Morning';
    if (h >= 12 && h < 17) return 'Afternoon';
    if (h >= 17 && h < 21) return 'Evening';
    return 'Night';
}

const groupedTimeSlots = computed(() => {
    const order = ['Morning', 'Afternoon', 'Evening', 'Night'];
    const groups: Record<string, string[]> = {};
    for (const slot of activeTimeSlots.value) {
        const period = slotPeriodLabel(slot);
        (groups[period] ??= []).push(slot);
    }
    return order
        .filter((period) => groups[period]?.length)
        .map((period) => ({ period, slots: groups[period] }));
});

// Fetch realtime server availability
async function fetchAvailability() {
    if (!selectedDate.value) return;
    await loadAvailability({ date: selectedDate.value });
}

// Compute full booked slots from database bookings
function getCourtBookedSlots(courtId: number): string[] {
    return slotsForCourt(courtId);
}

function isSlotBooked(courtId: number, slot: string): boolean {
    return getCourtBookedSlots(courtId).includes(slot);
}

const courtsToDisplay = computed<PublicCourt[]>(() => {
    if (!props.venue.courts || props.venue.courts.length === 0) return [];
    if (selectedCourtId.value) {
        return props.venue.courts.filter(c => c.id === selectedCourtId.value);
    }
    return props.venue.courts;
});

watch(selectedDate, (newDate) => {
    if (newDate && newDate < todayDateKey.value) {
        selectedDate.value = todayDateKey.value;
        return;
    }
    fetchAvailability();
    emit('date-selected', newDate);
});

onMounted(() => {
    fetchAvailability();
});
</script>

<template>
    <section class="border-t border-line bg-surface-elevated/10 py-16 sm:py-24">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-10">
            <!-- Header Title & Description -->
            <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-brand/15 px-3 py-1 text-xs font-bold text-brand uppercase tracking-wider">
                            <Clock class="size-3.5" />
                            <span>Live Schedule</span>
                        </span>
                    </div>
                    <h2 class="mt-2 font-display text-3xl font-black tracking-tight text-content sm:text-4xl">
                        Availability Schedule
                    </h2>
                    <p class="mt-2 text-sm text-content-muted max-w-2xl">
                        View real-time available and booked time slots for courts at <strong class="text-content font-bold">{{ venue.name }}</strong> before booking your game.
                    </p>
                </div>

                <!-- Schedule Key / Legend -->
                <div class="flex items-center gap-4 text-xs font-bold bg-surface-elevated border border-line p-3 rounded-2xl shadow-sm">
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50" />
                        <span class="text-content">Available Slot</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="size-3 rounded-full bg-surface-inverse/80 border border-white/20" />
                        <span class="text-content-muted">Booked / Reserved</span>
                    </div>
                </div>
            </div>

            <!-- Date Selector Controls -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-black uppercase tracking-wider text-content-muted flex items-center gap-2">
                        <Calendar class="size-4 text-brand" />
                        <span>Select Date</span>
                    </h3>

                    <!-- Native Date Picker fallback -->
                    <div class="flex items-center gap-2">
                        <label for="schedule-date-picker" class="text-xs text-content-muted font-bold">Pick Date:</label>
                        <input
                            id="schedule-date-picker"
                            type="date"
                            v-model="selectedDate"
                            :min="todayDateKey"
                            class="rounded-xl border border-line bg-surface-elevated px-3 py-1.5 text-xs font-bold text-content focus:border-brand focus:outline-none shadow-sm"
                        />
                    </div>
                </div>

                <!-- Horizontal Date Pills Carousel -->
                <div class="flex items-center gap-2.5 overflow-x-auto pb-2 scrollbar-thin">
                    <button
                        v-for="d in upcomingDays"
                        :key="d.dateStr"
                        type="button"
                        @click="selectedDate = d.dateStr"
                        class="flex shrink-0 flex-col items-center justify-center rounded-2xl border px-4 py-3 text-center transition-all duration-200 cursor-pointer min-w-[72px]"
                        :class="[
                            selectedDate === d.dateStr
                                ? 'border-brand bg-brand text-brand-foreground shadow-lg shadow-brand/25 scale-105 font-black'
                                : 'border-line bg-surface-elevated text-content hover:border-brand/40 hover:bg-surface-elevated/80',
                        ]"
                    >
                        <span class="text-[10px] font-extrabold uppercase tracking-wider opacity-80">
                            {{ d.isToday ? 'Today' : d.dayName }}
                        </span>
                        <span class="text-lg font-black tracking-tight my-0.5">
                            {{ d.dayNum }}
                        </span>
                        <span class="text-[10px] font-bold opacity-75">
                            {{ d.monthName }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Court Filter Tabs (If venue has multiple courts) -->
            <div v-if="venue.courts && venue.courts.length > 1" class="flex items-center gap-2 overflow-x-auto pt-2 border-t border-line">
                <span class="text-xs font-bold text-content-muted uppercase tracking-wider mr-2 shrink-0">Courts:</span>
                <button
                    type="button"
                    @click="selectedCourtId = null"
                    class="rounded-full px-4 py-1.5 text-xs font-bold transition-all cursor-pointer shrink-0"
                    :class="[
                        selectedCourtId === null
                            ? 'bg-brand text-brand-foreground shadow-md'
                            : 'bg-surface-elevated text-content-muted border border-line hover:text-content',
                    ]"
                >
                    All Courts ({{ venue.courts.length }})
                </button>
                <button
                    v-for="c in venue.courts"
                    :key="c.id"
                    type="button"
                    @click="selectedCourtId = c.id"
                    class="rounded-full px-4 py-1.5 text-xs font-bold transition-all cursor-pointer shrink-0"
                    :class="[
                        selectedCourtId === c.id
                            ? 'bg-brand text-brand-foreground shadow-md'
                            : 'bg-surface-elevated text-content-muted border border-line hover:text-content',
                    ]"
                >
                    {{ c.name }}
                </button>
            </div>

            <!-- Loading Spinner -->
            <div v-if="isLoading" class="py-12 text-center text-content-muted">
                <div class="inline-flex items-center gap-2 text-sm font-bold animate-pulse text-brand">
                    <Clock class="size-5 animate-spin" />
                    <span>Loading schedule availability...</span>
                </div>
            </div>

            <!-- Courts Schedule Display Cards -->
            <div v-else-if="courtsToDisplay.length > 0" class="space-y-8">
                <div
                    v-for="court in courtsToDisplay"
                    :key="court.id"
                    class="rounded-[var(--site-radius,1.25rem)] border border-line bg-surface-elevated p-6 shadow-md space-y-6"
                >
                    <!-- Court Header Summary -->
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-line pb-4">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-brand/10 p-3 text-brand">
                                <Dumbbell class="size-6" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3 class="font-display text-xl font-black text-content">
                                        {{ court.name }}
                                    </h3>
                                    <span class="rounded-full bg-surface px-2.5 py-0.5 text-[10px] font-bold text-content uppercase tracking-wider border border-line">
                                        {{ court.sport_type }}
                                    </span>
                                </div>
                                <p class="text-xs text-content-muted mt-0.5">
                                    Rate: <strong class="text-brand font-bold">₱{{ court.base_price }}</strong> / {{ court.slot_duration_minutes }} min
                                </p>
                            </div>
                        </div>

                        <!-- Availability Quick Counts -->
                        <div class="flex items-center gap-4 text-xs font-bold">
                            <div class="text-right">
                                <span class="block text-[10px] text-content-muted uppercase tracking-wider">Available</span>
                                <span class="text-emerald-500 font-extrabold text-sm">
                                    {{ activeTimeSlots.length - getCourtBookedSlots(court.id).length }} Slots
                                </span>
                            </div>
                            <div class="h-8 w-px bg-line" />
                            <div class="text-right">
                                <span class="block text-[10px] text-content-muted uppercase tracking-wider">Booked</span>
                                <span class="text-content-muted font-bold text-sm">
                                    {{ getCourtBookedSlots(court.id).length }} Slots
                                </span>
                            </div>
                            <button
                                type="button"
                                @click="emit('book-court', court, selectedDate)"
                                class="ml-2 inline-flex items-center gap-1.5 rounded-full bg-brand px-4 py-2 text-xs font-bold text-brand-foreground shadow-md transition-all hover:scale-105 hover:bg-brand/95 cursor-pointer"
                            >
                                <CalendarCheck class="size-3.5" />
                                <span>Book Court</span>
                            </button>
                        </div>
                    </div>

                    <!-- Time Slots Grouped Matrix -->
                    <div class="space-y-5">
                        <div v-for="group in groupedTimeSlots" :key="group.period" class="space-y-2">
                            <h4 class="text-[11px] font-extrabold uppercase tracking-widest text-content-muted flex items-center gap-1.5">
                                <span class="size-1.5 rounded-full bg-brand" />
                                <span>{{ group.period }}</span>
                            </h4>

                            <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-2.5">
                                <div
                                    v-for="slot in group.slots"
                                    :key="slot"
                                    class="relative flex flex-col items-center justify-between rounded-xl p-2.5 text-center transition-all duration-200 border"
                                    :class="[
                                        isSlotBooked(court.id, slot)
                                            ? 'bg-surface-inverse/80 text-content-muted border-line/40 opacity-75'
                                            : 'bg-surface-elevated text-content border-emerald-500/40 hover:border-emerald-500 hover:shadow-md hover:scale-102 cursor-pointer group/slot',
                                    ]"
                                    @click="!isSlotBooked(court.id, slot) && emit('book-court', court, selectedDate, slot)"
                                >
                                    <span class="text-xs font-black tracking-tight" :class="{ 'line-through text-slate-400': isSlotBooked(court.id, slot) }">
                                        {{ slot }}
                                    </span>
                                    <span class="text-[10px] font-extrabold text-brand mt-0.5">₱{{ getSlotPriceForCourt(court, slot) }}</span>

                                    <!-- Status Pill -->
                                    <span
                                        v-if="isSlotBooked(court.id, slot)"
                                        class="mt-1 inline-flex items-center gap-1 rounded-md bg-rose-500/15 px-2 py-0.5 text-[9px] font-bold text-rose-400 border border-rose-500/30"
                                    >
                                        <XCircle class="size-2.5" />
                                        <span>Booked</span>
                                    </span>
                                    <span
                                        v-else
                                        class="mt-1 inline-flex items-center gap-1 rounded-md bg-emerald-500/15 px-2 py-0.5 text-[9px] font-bold text-emerald-400 border border-emerald-500/30 group-hover/slot:bg-emerald-500 group-hover/slot:text-white transition-colors"
                                    >
                                        <CheckCircle class="size-2.5" />
                                        <span>Available</span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fallback if No Courts -->
            <div v-else class="rounded-2xl border border-line bg-surface-elevated p-12 text-center text-content-muted space-y-3">
                <Dumbbell class="mx-auto size-10 text-content-muted opacity-40" />
                <h4 class="text-base font-bold text-content">No Active Courts Available</h4>
                <p class="text-xs">There are currently no active courts listed for this venue.</p>
            </div>
        </div>
    </section>
</template>
