<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import {
    Calendar,
    Clock,
    CheckCircle,
    XCircle,
    Dumbbell,
    ChevronLeft,
    ChevronRight,
    TrendingUp,
    Zap,
} from '@lucide/vue';

interface CourtItem {
    id: number;
    name: string;
    slug: string;
    sport_type: string;
    base_price: string;
    slot_duration_minutes: number;
    is_active: boolean;
    slot_prices?: Record<string, string | number> | null;
}

const props = defineProps<{
    courts: CourtItem[];
    venueName: string;
}>();

const emit = defineEmits<{
    (e: 'book-slot', payload: { court: CourtItem; date: string; slot?: string }): void;
}>();

// ── Date helpers ─────────────────────────────────────────────────
function toDateKey(d: Date): string {
    return [
        d.getFullYear(),
        String(d.getMonth() + 1).padStart(2, '0'),
        String(d.getDate()).padStart(2, '0'),
    ].join('-');
}

function parseLocalDate(s: string): Date {
    const p = s.split('-').map(Number);
    return p.length === 3 ? new Date(p[0], p[1] - 1, p[2]) : new Date(s);
}

const todayKey = computed(() => toDateKey(new Date()));
const selectedDate = ref(todayKey.value);
const selectedCourtId = ref<number | null>(null);

const formattedDate = computed(() => {
    const d = parseLocalDate(selectedDate.value);
    return d.toLocaleDateString('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
        year: 'numeric',
    });
});

// ── Upcoming 14-day slider ───────────────────────────────────────
const upcomingDays = computed(() => {
    const base = new Date();
    base.setHours(0, 0, 0, 0);
    return Array.from({ length: 14 }, (_, i) => {
        const d = new Date(base);
        d.setDate(base.getDate() + i);
        const key = toDateKey(d);
        return {
            key,
            dayName: d.toLocaleDateString('en-US', { weekday: 'short' }),
            dayNum: d.getDate(),
            month: d.toLocaleDateString('en-US', { month: 'short' }),
            isToday: key === todayKey.value,
            isWeekend: d.getDay() === 0 || d.getDay() === 6,
        };
    });
});

// ── Time slots ───────────────────────────────────────────────────
const TIME_SLOTS = [
    '07:00 AM', '08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM',
    '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM',
    '05:00 PM', '06:00 PM', '07:00 PM', '08:00 PM', '09:00 PM',
    '10:00 PM', '11:00 PM', '12:00 AM', '01:00 AM', '02:00 AM',
];

function slotHour(slot: string): number {
    const [time, period] = slot.split(' ');
    let h = parseInt(time.split(':')[0], 10);
    if (period === 'PM' && h !== 12) h += 12;
    if (period === 'AM' && h === 12) h = 0;
    return h;
}

function slotPeriod(slot: string) {
    const h = slotHour(slot);
    if (h >= 5 && h < 12) return { label: 'Morning', icon: '🌅', color: 'amber' };
    if (h >= 12 && h < 17) return { label: 'Afternoon', icon: '☀️', color: 'orange' };
    if (h >= 17 && h < 21) return { label: 'Evening', icon: '🌆', color: 'violet' };
    return { label: 'Night', icon: '🌙', color: 'slate' };
}

const groupedSlots = computed(() => {
    const order = ['Morning', 'Afternoon', 'Evening', 'Night'];
    const map: Record<string, { slot: string; meta: ReturnType<typeof slotPeriod> }[]> = {};
    for (const slot of TIME_SLOTS) {
        const meta = slotPeriod(slot);
        (map[meta.label] ??= []).push({ slot, meta });
    }
    return order.filter(k => map[k]?.length).map(k => ({
        period: k,
        meta: map[k][0].meta,
        slots: map[k].map(x => x.slot),
    }));
});

// ── Pricing ──────────────────────────────────────────────────────
function slotPrice(court: CourtItem, slot: string): number {
    if (court.slot_prices?.[slot] !== undefined && court.slot_prices[slot] !== null) {
        const v = parseFloat(String(court.slot_prices[slot]));
        if (!isNaN(v) && v > 0) return v;
    }
    const b = parseFloat(court.base_price || '0');
    return isNaN(b) ? 0 : b;
}

// ── Availability fetch ───────────────────────────────────────────
const bookedMap = ref<Record<string, string[]>>({});
const isLoading = ref(false);

async function fetchAvailability() {
    if (!selectedDate.value || typeof window === 'undefined') return;
    isLoading.value = true;
    try {
        const res = await fetch(
            `/bookings/availability?date=${encodeURIComponent(selectedDate.value)}`,
            { headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' } }
        );
        if (res.ok) {
            const data = await res.json();
            bookedMap.value = data.booked_slots || {};
        }
    } catch {
        // silent
    } finally {
        isLoading.value = false;
    }
}

function bookedSlots(courtId: number): string[] {
    return bookedMap.value[String(courtId)] || [];
}

function isBooked(courtId: number, slot: string): boolean {
    return bookedSlots(courtId).includes(slot);
}

// ── Derived ──────────────────────────────────────────────────────
const activeCourts = computed(() => props.courts.filter(c => c.is_active));

const courtsToShow = computed(() =>
    selectedCourtId.value !== null
        ? activeCourts.value.filter(c => c.id === selectedCourtId.value)
        : activeCourts.value
);

// ── Summary stats ────────────────────────────────────────────────
const totalAvailable = computed(() =>
    activeCourts.value.reduce(
        (sum, c) => sum + (TIME_SLOTS.length - bookedSlots(c.id).length),
        0
    )
);

const totalBooked = computed(() =>
    activeCourts.value.reduce((sum, c) => sum + bookedSlots(c.id).length, 0)
);

const occupancyPct = computed(() => {
    const total = activeCourts.value.length * TIME_SLOTS.length;
    return total > 0 ? Math.round((totalBooked.value / total) * 100) : 0;
});

watch(selectedDate, fetchAvailability);
onMounted(fetchAvailability);
</script>

<template>
    <div class="space-y-6">

        <!-- ── Header + stat strip ──────────────────────────── -->
        <div class="relative overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-gradient-to-br from-emerald-600 via-teal-600 to-emerald-700 p-6 text-white shadow-lg">
            <!-- Decorative blobs -->
            <div class="pointer-events-none absolute -top-10 -right-10 size-48 rounded-full bg-white/5 blur-3xl" />
            <div class="pointer-events-none absolute -bottom-8 -left-8 size-36 rounded-full bg-black/10 blur-2xl" />

            <div class="relative flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
                <!-- Title block -->
                <div>
                    <div class="flex items-center gap-2">
                        <span class="flex items-center gap-1.5 rounded-full bg-white/15 px-3 py-1 text-[11px] font-bold uppercase tracking-widest backdrop-blur-sm">
                            <Zap class="size-3" />
                            Live Schedule
                        </span>
                    </div>
                    <h3 class="mt-3 text-2xl font-black tracking-tight">Availability Schedule</h3>
                    <p class="mt-1 text-sm text-emerald-100/80">
                        Real-time court slots for
                        <strong class="text-white">{{ venueName }}</strong>
                    </p>
                </div>

                <!-- Stat cards -->
                <div class="flex gap-3 shrink-0">
                    <div class="rounded-xl bg-white/15 px-4 py-3 text-center backdrop-blur-sm min-w-[72px]">
                        <p class="text-2xl font-black tabular-nums">{{ totalAvailable }}</p>
                        <p class="mt-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-100/80">Open</p>
                    </div>
                    <div class="rounded-xl bg-white/15 px-4 py-3 text-center backdrop-blur-sm min-w-[72px]">
                        <p class="text-2xl font-black tabular-nums">{{ totalBooked }}</p>
                        <p class="mt-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-100/80">Booked</p>
                    </div>
                    <div class="rounded-xl bg-white/15 px-4 py-3 text-center backdrop-blur-sm min-w-[72px]">
                        <p class="text-2xl font-black tabular-nums">{{ occupancyPct }}%</p>
                        <p class="mt-0.5 text-[10px] font-bold uppercase tracking-wider text-emerald-100/80">Full</p>
                    </div>
                </div>
            </div>

            <!-- Occupancy bar -->
            <div class="relative mt-5">
                <div class="mb-1 flex items-center justify-between text-[11px] font-semibold text-emerald-100/70">
                    <span class="flex items-center gap-1"><TrendingUp class="size-3" /> Occupancy rate</span>
                    <span>{{ occupancyPct }}%</span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-white/20">
                    <div
                        class="h-full rounded-full bg-white transition-all duration-700 ease-out"
                        :style="{ width: `${occupancyPct}%` }"
                    />
                </div>
            </div>
        </div>

        <!-- ── Date selector card ────────────────────────────── -->
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm overflow-hidden">
            <!-- Card header -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-neutral-100 dark:border-neutral-800 px-5 py-4">
                <div class="flex items-center gap-2">
                    <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400">
                        <Calendar class="size-4" />
                    </div>
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-wider text-neutral-400">Viewing</p>
                        <p class="text-sm font-black text-neutral-900 dark:text-white">{{ formattedDate }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <label for="admin-date-pick" class="text-xs font-semibold text-neutral-500">Jump to:</label>
                    <input
                        id="admin-date-pick"
                        type="date"
                        v-model="selectedDate"
                        :min="todayKey"
                        class="rounded-lg border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 px-3 py-1.5 text-xs font-bold text-neutral-900 dark:text-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 focus:outline-none transition"
                    />
                </div>
            </div>

            <!-- 14-day strip -->
            <div class="flex gap-2 overflow-x-auto px-5 py-4 scrollbar-thin scrollbar-thumb-neutral-200 dark:scrollbar-thumb-neutral-700">
                <button
                    v-for="d in upcomingDays"
                    :key="d.key"
                    type="button"
                    @click="selectedDate = d.key"
                    class="group flex shrink-0 flex-col items-center justify-center rounded-xl border px-3.5 py-2.5 text-center transition-all duration-200 min-w-[64px] focus:outline-none focus:ring-2 focus:ring-emerald-500/40"
                    :class="[
                        selectedDate === d.key
                            ? 'border-emerald-500 bg-emerald-600 text-white shadow-lg shadow-emerald-600/25 scale-105'
                            : d.isWeekend
                                ? 'border-violet-100 dark:border-violet-900/40 bg-violet-50 dark:bg-violet-950/20 text-neutral-700 dark:text-neutral-300 hover:border-violet-300 hover:bg-violet-100 dark:hover:bg-violet-950/40'
                                : 'border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 hover:border-emerald-300 hover:bg-emerald-50 dark:hover:bg-emerald-950/20',
                    ]"
                >
                    <span class="text-[9px] font-extrabold uppercase tracking-wider"
                        :class="selectedDate === d.key ? 'text-emerald-100' : 'text-neutral-400'">
                        {{ d.isToday ? 'Today' : d.dayName }}
                    </span>
                    <span class="my-1 text-xl font-black leading-none tabular-nums">{{ d.dayNum }}</span>
                    <span class="text-[9px] font-semibold"
                        :class="selectedDate === d.key ? 'text-emerald-100' : 'text-neutral-400'">
                        {{ d.month }}
                    </span>
                    <!-- Today dot -->
                    <span v-if="d.isToday && selectedDate !== d.key"
                        class="mt-1 size-1 rounded-full bg-emerald-500" />
                </button>
            </div>
        </div>

        <!-- ── Court filter tabs ─────────────────────────────── -->
        <div v-if="activeCourts.length > 1"
            class="flex flex-wrap items-center gap-2 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 px-4 py-3 shadow-sm">
            <span class="text-[11px] font-bold uppercase tracking-wider text-neutral-400">Filter court:</span>
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="selectedCourtId = null"
                    class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-150"
                    :class="[
                        selectedCourtId === null
                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25'
                            : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 border border-neutral-200 dark:border-neutral-700 hover:border-emerald-300',
                    ]"
                >
                    <Dumbbell class="size-3" />
                    All Courts
                    <span class="rounded-full px-1.5"
                        :class="selectedCourtId === null ? 'bg-white/20 text-white' : 'bg-neutral-200 dark:bg-neutral-700 text-neutral-500'">
                        {{ activeCourts.length }}
                    </span>
                </button>
                <button
                    v-for="c in activeCourts"
                    :key="c.id"
                    type="button"
                    @click="selectedCourtId = c.id"
                    class="rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-150"
                    :class="[
                        selectedCourtId === c.id
                            ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/25'
                            : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400 border border-neutral-200 dark:border-neutral-700 hover:border-emerald-300',
                    ]"
                >
                    {{ c.name }}
                </button>
            </div>
        </div>

        <!-- ── Loading skeleton ──────────────────────────────── -->
        <div v-if="isLoading" class="space-y-4">
            <div v-for="i in Math.max(1, courtsToShow.length || 1)" :key="i"
                class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 animate-pulse">
                <div class="flex items-center gap-3 border-b border-neutral-100 dark:border-neutral-800 pb-4">
                    <div class="size-10 rounded-xl bg-neutral-200 dark:bg-neutral-700" />
                    <div class="space-y-2 flex-1">
                        <div class="h-4 w-32 rounded-lg bg-neutral-200 dark:bg-neutral-700" />
                        <div class="h-3 w-20 rounded-lg bg-neutral-100 dark:bg-neutral-800" />
                    </div>
                </div>
                <div class="mt-4 grid grid-cols-4 gap-2 sm:grid-cols-6 lg:grid-cols-8">
                    <div v-for="j in 16" :key="j"
                        class="h-16 rounded-xl bg-neutral-100 dark:bg-neutral-800" />
                </div>
            </div>
        </div>

        <!-- ── Courts schedule ───────────────────────────────── -->
        <transition-group
            v-else-if="courtsToShow.length > 0"
            name="court-fade"
            tag="div"
            class="space-y-5"
        >
            <div
                v-for="court in courtsToShow"
                :key="court.id"
                class="overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm"
            >
                <!-- Court card header -->
                <div class="relative flex flex-col gap-4 border-b border-neutral-100 dark:border-neutral-800 bg-gradient-to-r from-neutral-50 to-white dark:from-neutral-800/60 dark:to-neutral-900 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 shadow-sm">
                            <Dumbbell class="size-5" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="font-black text-neutral-900 dark:text-white">{{ court.name }}</h4>
                                <span class="rounded-full border border-neutral-200 dark:border-neutral-700 bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 text-[10px] font-bold text-neutral-500 uppercase tracking-wider">
                                    {{ court.sport_type }}
                                </span>
                            </div>
                            <p class="mt-0.5 text-xs text-neutral-500">
                                From <strong class="font-bold text-emerald-600 dark:text-emerald-400">₱{{ court.base_price }}</strong>
                                / {{ court.slot_duration_minutes }} min
                            </p>
                        </div>
                    </div>

                    <!-- Availability mini-chart -->
                    <div class="flex items-center gap-4 shrink-0">
                        <div class="text-center">
                            <p class="text-xl font-black tabular-nums text-emerald-500">
                                {{ TIME_SLOTS.length - bookedSlots(court.id).length }}
                            </p>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Open</p>
                        </div>
                        <div class="h-10 w-px bg-neutral-200 dark:bg-neutral-700" />
                        <div class="text-center">
                            <p class="text-xl font-black tabular-nums text-neutral-400">{{ bookedSlots(court.id).length }}</p>
                            <p class="text-[10px] font-bold uppercase tracking-wider text-neutral-400">Booked</p>
                        </div>
                        <div class="h-10 w-px bg-neutral-200 dark:bg-neutral-700" />
                        <!-- Mini occupancy bar pill -->
                        <div class="flex flex-col items-center gap-1">
                            <p class="text-xs font-black text-neutral-700 dark:text-neutral-200 tabular-nums">
                                {{ Math.round((bookedSlots(court.id).length / TIME_SLOTS.length) * 100) }}%
                            </p>
                            <div class="h-1.5 w-16 overflow-hidden rounded-full bg-neutral-200 dark:bg-neutral-700">
                                <div
                                    class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                                    :style="{ width: `${Math.round((bookedSlots(court.id).length / TIME_SLOTS.length) * 100)}%` }"
                                />
                            </div>
                            <p class="text-[9px] font-semibold uppercase tracking-wider text-neutral-400">Full</p>
                        </div>
                    </div>
                </div>

                <!-- Slot groups -->
                <div class="space-y-5 p-5">
                    <div v-for="group in groupedSlots" :key="group.period">
                        <!-- Period label -->
                        <div class="mb-3 flex items-center gap-2">
                            <span class="text-base leading-none">{{ group.meta.icon }}</span>
                            <h5 class="text-[11px] font-extrabold uppercase tracking-widest text-neutral-400">
                                {{ group.period }}
                            </h5>
                            <div class="flex-1 h-px bg-neutral-100 dark:bg-neutral-800" />
                            <!-- Quick count for this period -->
                            <span class="text-[10px] font-bold text-neutral-400">
                                {{ group.slots.filter(s => !isBooked(court.id, s)).length }} / {{ group.slots.length }} open
                            </span>
                        </div>

                        <!-- Slot grid -->
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5">
                            <div
                                v-for="slot in group.slots"
                                :key="slot"
                                class="group/slot relative flex flex-col items-center justify-center rounded-xl border p-3 text-center transition-all duration-150 select-none"
                                :class="[
                                    isBooked(court.id, slot)
                                        ? 'border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800/40 cursor-not-allowed'
                                        : 'border-emerald-100 dark:border-emerald-900/40 bg-emerald-50/60 dark:bg-emerald-950/20 cursor-pointer hover:border-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/40 hover:shadow-md hover:shadow-emerald-500/10 hover:-translate-y-0.5',
                                ]"
                                @click="!isBooked(court.id, slot) && emit('book-slot', { court, date: selectedDate, slot })"
                            >
                                <!-- Available glow dot -->
                                <span v-if="!isBooked(court.id, slot)"
                                    class="absolute top-2 right-2 size-1.5 rounded-full bg-emerald-400 shadow-sm shadow-emerald-400/60" />

                                <span
                                    class="text-xs font-black leading-tight"
                                    :class="isBooked(court.id, slot) ? 'line-through text-neutral-300 dark:text-neutral-600' : 'text-neutral-900 dark:text-white'"
                                >{{ slot }}</span>

                                <span
                                    class="mt-1 text-[10px] font-extrabold"
                                    :class="isBooked(court.id, slot) ? 'text-neutral-300 dark:text-neutral-700' : 'text-emerald-600 dark:text-emerald-400'"
                                >₱{{ slotPrice(court, slot) }}</span>

                                <span
                                    v-if="isBooked(court.id, slot)"
                                    class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-rose-100 dark:bg-rose-950/60 px-2 py-0.5 text-[9px] font-bold text-rose-400 dark:text-rose-500"
                                >
                                    <XCircle class="size-2.5" /> Taken
                                </span>
                                <span
                                    v-else
                                    class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-emerald-100 dark:bg-emerald-950/60 px-2 py-0.5 text-[9px] font-bold text-emerald-600 dark:text-emerald-400 group-hover/slot:bg-emerald-500 group-hover/slot:text-white transition-colors"
                                >
                                    <CheckCircle class="size-2.5" /> Open
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </transition-group>

        <!-- ── Empty state ───────────────────────────────────── -->
        <div v-else
            class="flex flex-col items-center justify-center gap-4 rounded-2xl border border-dashed border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-900 py-16 text-center">
            <div class="flex size-14 items-center justify-center rounded-2xl bg-neutral-100 dark:bg-neutral-800">
                <Clock class="size-7 text-neutral-400" />
            </div>
            <div>
                <p class="font-bold text-neutral-600 dark:text-neutral-300">No active courts</p>
                <p class="mt-1 text-xs text-neutral-400">No active courts have been added to this venue yet.</p>
            </div>
        </div>

    </div>
</template>

<style scoped>
.court-fade-enter-active,
.court-fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.court-fade-enter-from,
.court-fade-leave-to {
    opacity: 0;
    transform: translateY(6px);
}
</style>
