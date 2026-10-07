<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
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
import {
    formatDuration,
    formatSlotRange,
    getMergedTimeSlots,
    isSlotPassed,
} from '@/utils/timeSlots';
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
    if (
        parts.length === 3 &&
        !isNaN(parts[0]) &&
        !isNaN(parts[1]) &&
        !isNaN(parts[2])
    ) {
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }
    return new Date(dateStr);
}

const currentNow = ref(new Date());
let clockIntervalId: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    clockIntervalId = setInterval(() => {
        currentNow.value = new Date();
    }, 1000);
});

onUnmounted(() => {
    if (clockIntervalId) {
        clearInterval(clockIntervalId);
        clockIntervalId = null;
    }
});

const todayDateKey = computed(() => toDateKey(currentNow.value));
const isEveningOrNight = computed(() => {
    const hours = currentNow.value.getHours();
    return hours >= 17; // 5:00 PM (17:00) onwards is Evening / Night (PM) period
});
const selectedDate = ref<string>(todayDateKey.value);
const selectedCourtId = ref<number | null>(null);

const formattedSelectedDate = computed(() => {
    if (!selectedDate.value) return '';
    const d = parseLocalDate(selectedDate.value);
    return d.toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
});

function getSlotPriceForCourt(court: PublicCourt, slot: string): number {
    const customPrices = court.slot_prices;
    if (
        customPrices &&
        customPrices[slot] !== undefined &&
        customPrices[slot] !== null
    ) {
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
    customerForSlot,
} = useCourtAvailability();

// Generate 14 upcoming days for quick selector
const upcomingDays = computed(() => {
    const list: {
        dateStr: string;
        dayName: string;
        dayNum: string;
        monthName: string;
        isToday: boolean;
    }[] = [];
    const base = new Date(currentNow.value);
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
        props.venue.courts.forEach((c) => {
            if (c.slot_prices) customPricesList.push(c.slot_prices);
        });
    }
    const combined = customPricesList.reduce(
        (acc, prices) => ({ ...acc, ...prices }),
        {},
    );
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

function getSlotCustomer(courtId: number, slot: string): string | null {
    return customerForSlot(courtId, slot);
}

const courtsToDisplay = computed<PublicCourt[]>(() => {
    if (!props.venue.courts || props.venue.courts.length === 0) return [];
    if (selectedCourtId.value) {
        return props.venue.courts.filter((c) => c.id === selectedCourtId.value);
    }
    return props.venue.courts;
});

watch(selectedDate, (newDate) => {
    fetchAvailability();
    emit('date-selected', newDate);
});

onMounted(() => {
    fetchAvailability();
});
</script>

<template>
    <section class="border-t border-line bg-surface-elevated/10 py-16 sm:py-24">
        <div class="mx-auto max-w-7xl space-y-10 px-4 sm:px-6 lg:px-8">
            <!-- Header Title & Description -->
            <div
                class="flex flex-col justify-between gap-6 md:flex-row md:items-end"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1.5 rounded-full bg-brand/15 px-3 py-1 text-xs font-bold tracking-wider text-brand uppercase"
                        >
                            <Clock class="size-3.5" />
                            <span>Live Schedule</span>
                        </span>
                    </div>
                    <h2
                        class="mt-2 font-display text-3xl font-black tracking-tight text-content sm:text-4xl"
                    >
                        Availability Schedule
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm text-content-muted">
                        View real-time available and booked time slots for
                        courts at
                        <strong class="font-bold text-content">{{
                            venue.name
                        }}</strong>
                        before booking your game.
                    </p>
                </div>

                <!-- Schedule Key / Legend -->
                <div
                    class="flex items-center gap-4 rounded-2xl border border-line bg-surface-elevated p-3 text-xs font-bold shadow-sm"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="size-3 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50"
                        />
                        <span class="text-content">Available Slot</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="size-3 rounded-full bg-rose-500 shadow-sm shadow-rose-500/50"
                        />
                        <span class="text-content-muted"
                            >Booked / Reserved</span
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="size-3 rounded-full bg-neutral-400 dark:bg-neutral-600"
                        />
                        <span class="text-content-muted"
                            >Passed</span
                        >
                    </div>
                </div>
            </div>

            <!-- Date Selector Controls -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <h3
                        class="flex items-center gap-2 text-xs font-black tracking-wider text-content-muted uppercase"
                    >
                        <Calendar class="size-4 text-brand" />
                        <span>Select Date</span>
                    </h3>

                    <!-- Native Date Picker fallback -->
                    <div class="flex items-center gap-2">
                        <label
                            for="schedule-date-picker"
                            class="text-xs font-bold text-content-muted"
                            >Pick Date:</label
                        >
                        <input
                            id="schedule-date-picker"
                            type="date"
                            v-model="selectedDate"
                            :min="todayDateKey"
                            class="rounded-xl border border-line bg-surface-elevated px-3 py-1.5 text-xs font-bold text-content shadow-sm focus:border-brand focus:outline-none"
                        />
                    </div>
                </div>

                <!-- Horizontal Date Pills Carousel -->
                <div
                    class="flex scrollbar-thin items-center gap-2.5 overflow-x-auto pb-2"
                >
                    <button
                        v-for="d in upcomingDays"
                        :key="d.dateStr"
                        type="button"
                        @click="selectedDate = d.dateStr"
                        class="flex min-w-[72px] shrink-0 cursor-pointer flex-col items-center justify-center rounded-2xl border px-4 py-3 text-center transition-all duration-200"
                        :class="[
                            selectedDate === d.dateStr
                                ? 'scale-105 border-brand bg-brand font-black text-brand-foreground shadow-lg shadow-brand/25'
                                : 'border-line bg-surface-elevated text-content hover:border-brand/40 hover:bg-surface-elevated/80',
                        ]"
                    >
                        <span
                            class="text-[10px] font-extrabold tracking-wider uppercase opacity-80"
                        >
                            {{ d.isToday ? (isEveningOrNight ? 'Tonight' : 'Today') : d.dayName }}
                        </span>
                        <span class="my-0.5 text-lg font-black tracking-tight">
                            {{ d.dayNum }}
                        </span>
                        <span class="text-[10px] font-bold opacity-75">
                            {{ d.monthName }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- Court Filter Tabs (If venue has multiple courts) -->
            <div
                v-if="venue.courts && venue.courts.length > 1"
                class="flex items-center gap-2 overflow-x-auto border-t border-line pt-2"
            >
                <span
                    class="mr-2 shrink-0 text-xs font-bold tracking-wider text-content-muted uppercase"
                    >Courts:</span
                >
                <button
                    type="button"
                    @click="selectedCourtId = null"
                    class="shrink-0 cursor-pointer rounded-full px-4 py-1.5 text-xs font-bold transition-all"
                    :class="[
                        selectedCourtId === null
                            ? 'bg-brand text-brand-foreground shadow-md'
                            : 'border border-line bg-surface-elevated text-content-muted hover:text-content',
                    ]"
                >
                    All Courts ({{ venue.courts.length }})
                </button>
                <button
                    v-for="c in venue.courts"
                    :key="c.id"
                    type="button"
                    @click="selectedCourtId = c.id"
                    class="shrink-0 cursor-pointer rounded-full px-4 py-1.5 text-xs font-bold transition-all"
                    :class="[
                        selectedCourtId === c.id
                            ? 'bg-brand text-brand-foreground shadow-md'
                            : 'border border-line bg-surface-elevated text-content-muted hover:text-content',
                    ]"
                >
                    {{ c.name }}
                </button>
            </div>

            <!-- Loading Spinner -->
            <div v-if="isLoading" class="py-12 text-center text-content-muted">
                <div
                    class="inline-flex animate-pulse items-center gap-2 text-sm font-bold text-brand"
                >
                    <Clock class="size-5 animate-spin" />
                    <span>Loading schedule availability...</span>
                </div>
            </div>

            <!-- Courts Schedule Display Cards -->
            <div v-else-if="courtsToDisplay.length > 0" class="space-y-8">
                <div
                    v-for="court in courtsToDisplay"
                    :key="court.id"
                    class="space-y-6 rounded-[var(--site-radius,1.25rem)] border border-line bg-surface-elevated p-6 shadow-md"
                >
                    <!-- Court Header Summary -->
                    <div
                        class="flex flex-col justify-between gap-4 border-b border-line pb-4 sm:flex-row sm:items-center"
                    >
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-brand/10 p-3 text-brand">
                                <Dumbbell class="size-6" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h3
                                        class="font-display text-xl font-black text-content"
                                    >
                                        {{ court.name }}
                                    </h3>
                                    <span
                                        class="rounded-full border border-line bg-surface px-2.5 py-0.5 text-[10px] font-bold tracking-wider text-content uppercase"
                                    >
                                        {{ court.sport_type }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-xs text-content-muted">
                                    <strong class="font-bold text-brand"
                                        >₱{{ court.base_price }}</strong
                                    >
                                    /
                                    {{
                                        formatDuration(
                                            court.slot_duration_minutes,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Availability Quick Counts -->
                        <div class="flex items-center gap-4 text-xs font-bold">
                            <div class="text-right">
                                <span
                                    class="block text-[10px] tracking-wider text-content-muted uppercase"
                                    >Available</span
                                >
                                <span
                                    class="text-sm font-extrabold text-emerald-500"
                                >
                                    {{
                                        activeTimeSlots.filter(
                                            (s) =>
                                                !isSlotBooked(court.id, s) &&
                                                !isSlotPassed(selectedDate, s, currentNow),
                                        ).length
                                    }}
                                    Slots
                                </span>
                            </div>
                            <div class="h-8 w-px bg-line" />
                            <div class="text-right">
                                <span
                                    class="block text-[10px] tracking-wider text-content-muted uppercase"
                                    >Booked</span
                                >
                                <span
                                    class="text-sm font-bold text-content-muted"
                                >
                                    {{ getCourtBookedSlots(court.id).length }}
                                    Slots
                                </span>
                            </div>
                            <button
                                type="button"
                                @click="emit('book-court', court, selectedDate)"
                                class="ml-2 inline-flex cursor-pointer items-center gap-1.5 rounded-md bg-brand px-4 py-2 text-xs font-bold text-brand-foreground shadow-md transition-all hover:-translate-y-0.5 hover:bg-brand/95"
                            >
                                <CalendarCheck class="size-3.5" />
                                <span>Book Court</span>
                            </button>
                        </div>
                    </div>

                    <!-- Time Slots Grouped Matrix -->
                    <div class="space-y-5">
                        <div
                            v-for="group in groupedTimeSlots"
                            :key="group.period"
                            class="space-y-2"
                        >
                            <h4
                                class="flex items-center gap-1.5 text-[11px] font-extrabold tracking-widest text-content-muted uppercase"
                            >
                                <span class="size-1.5 rounded-full bg-brand" />
                                <span>{{ group.period }}</span>
                            </h4>

                            <div
                                class="grid grid-cols-2 gap-2.5 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6"
                            >
                                <div
                                    v-for="slot in group.slots"
                                    :key="slot"
                                    class="relative flex flex-col items-center justify-between rounded-xl border p-2.5 text-center transition-all duration-200"
                                    :class="[
                                        isSlotBooked(court.id, slot)
                                            ? 'cursor-not-allowed border-line/40 bg-surface-inverse/85 text-content-muted'
                                            : isSlotPassed(selectedDate, slot, currentNow)
                                              ? 'cursor-not-allowed border-line/30 bg-surface-elevated/40 text-content-muted/60 opacity-50'
                                              : 'group/slot cursor-pointer border-emerald-500/40 bg-surface-elevated text-content hover:scale-102 hover:border-emerald-500 hover:shadow-md',
                                    ]"
                                    @click="
                                        !isSlotBooked(court.id, slot) &&
                                        !isSlotPassed(selectedDate, slot, currentNow) &&
                                        emit(
                                            'book-court',
                                            court,
                                            selectedDate,
                                            slot,
                                        )
                                    "
                                >
                                    <span
                                        class="text-xs font-black tracking-tight"
                                        :class="{
                                            'text-slate-400 line-through':
                                                isSlotBooked(court.id, slot) ||
                                                isSlotPassed(selectedDate, slot, currentNow),
                                        }"
                                    >
                                        {{
                                            formatSlotRange(
                                                slot,
                                                court.slot_duration_minutes ||
                                                    60,
                                            )
                                        }}
                                    </span>
                                    <span
                                        class="mt-0.5 text-[10px] font-extrabold"
                                        :class="
                                            isSlotBooked(court.id, slot) ||
                                            isSlotPassed(selectedDate, slot, currentNow)
                                                ? 'text-slate-400 opacity-70'
                                                : 'text-brand'
                                        "
                                        >₱{{
                                            getSlotPriceForCourt(court, slot)
                                        }}</span
                                    >

                                    <!-- Status Pill -->
                                    <span
                                        v-if="isSlotBooked(court.id, slot)"
                                        class="mt-1 inline-flex items-center gap-1.5 rounded-md border border-rose-500/30 bg-rose-500/15 px-2 py-0.5 text-[9px] font-bold text-rose-400"
                                    >
                                        <span
                                            class="size-1.5 rounded-full bg-rose-500 shadow-sm shadow-rose-500/50"
                                        />
                                        <span>Booked</span>
                                    </span>
                                    <span
                                        v-else-if="
                                            isSlotPassed(selectedDate, slot, currentNow)
                                        "
                                        class="mt-1 inline-flex items-center gap-1 rounded-md border border-line/40 bg-surface-elevated/80 px-2 py-0.5 text-[9px] font-bold text-content-muted/70"
                                    >
                                        <span>Passed</span>
                                    </span>
                                    <span
                                        v-else
                                        class="mt-1 inline-flex items-center gap-1 rounded-md border border-emerald-500/30 bg-emerald-500/15 px-2 py-0.5 text-[9px] font-bold text-emerald-400 transition-colors group-hover/slot:bg-emerald-500 group-hover/slot:text-white"
                                    >
                                        <CheckCircle class="size-2.5" />
                                        <span>Available</span>
                                    </span>

                                    <!-- Customer Name for Booked Slots -->
                                    <div
                                        v-if="
                                            isSlotBooked(court.id, slot) &&
                                            getSlotCustomer(court.id, slot)
                                        "
                                        class="mt-2 w-full max-w-full truncate border-t border-white/10 pt-1.5 text-center text-[10px] leading-tight"
                                        :title="`Customer: ${getSlotCustomer(court.id, slot)}`"
                                    >
                                        <span class="text-slate-400">Customer: </span>
                                        <strong class="font-bold text-white">{{
                                            getSlotCustomer(court.id, slot)
                                        }}</strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Fallback if No Courts -->
            <div
                v-else
                class="space-y-3 rounded-2xl border border-line bg-surface-elevated p-12 text-center text-content-muted"
            >
                <Dumbbell
                    class="mx-auto size-10 text-content-muted opacity-40"
                />
                <h4 class="text-base font-bold text-content">
                    No Active Courts Available
                </h4>
                <p class="text-xs">
                    There are currently no active courts listed for this
                    location.
                </p>
            </div>
        </div>
    </section>
</template>
