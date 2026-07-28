<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

interface CourtOption {
    id: number;
    name: string;
    base_price?: string | number | null;
    slot_prices?: Record<string, string | number> | null;
}

const props = defineProps<{
    open: boolean;
    courts: CourtOption[];
    action: string;
}>();

const emit = defineEmits<{ (e: 'close'): void }>();

const form = useForm({
    court_id: null as number | null,
    name: '',
    email: '',
    phone: '',
    date: '',
    time_slots: [] as string[],
    notes: '',
});

const currentStep = ref(1);
const localErrors = ref<{ court?: string; date?: string; time?: string; name?: string; email?: string; phone?: string }>({});

const wizardHeading = computed(() => {
    if (currentStep.value === 1) {
        return { title: 'New Booking', subtitle: 'Pick the court, date, and time slots.' };
    }
    return { title: 'Customer Details', subtitle: 'Who is this reservation for?' };
});

// --- Courts ---
const sortedCourts = computed<CourtOption[]>(() =>
    [...props.courts].sort((a, b) => a.name.localeCompare(b.name)),
);

const selectedCourt = computed<CourtOption | null>(
    () => sortedCourts.value.find((c) => c.id === form.court_id) || null,
);

const courtScroller = ref<HTMLElement | null>(null);
function scrollCourts(dir: 'prev' | 'next') {
    courtScroller.value?.scrollBy({ left: dir === 'next' ? 220 : -220, behavior: 'smooth' });
}

// --- Date slider (7-day window) ---
function toDateKey(d: Date): string {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}
const todayDateString = computed(() => toDateKey(new Date()));
const weekOffset = ref(0);
const dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

const visibleDays = computed<Date[]>(() => {
    const base = new Date();
    base.setHours(0, 0, 0, 0);
    base.setDate(base.getDate() + weekOffset.value * 7);
    return Array.from({ length: 7 }, (_, i) => {
        const d = new Date(base);
        d.setDate(base.getDate() + i);
        return d;
    });
});

const monthYearLabel = computed(() => {
    const days = visibleDays.value;
    const monthName = (d: Date) => d.toLocaleDateString('en-US', { month: 'short' });
    const first = days[0];
    const last = days[6];
    if (first.getMonth() === last.getMonth()) return `${monthName(first)} ${first.getFullYear()}`;
    return `${monthName(first)} – ${monthName(last)} ${last.getFullYear()}`;
});

function selectDay(d: Date) {
    form.date = toDateKey(d);
}
function isSelectedDay(d: Date) {
    return toDateKey(d) === form.date;
}
function isToday(d: Date) {
    return toDateKey(d) === todayDateString.value;
}
function prevWeek() {
    if (weekOffset.value > 0) weekOffset.value--;
}
function nextWeek() {
    weekOffset.value++;
}

// --- Time slots grouped by period ---
const timeSlots = [
    '07:00 AM', '08:00 AM', '09:00 AM', '10:00 AM', '11:00 AM', '12:00 PM',
    '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM', '06:00 PM',
    '07:00 PM', '08:00 PM', '09:00 PM', '10:00 PM', '11:00 PM', '12:00 AM',
    '01:00 AM', '02:00 AM',
];

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
const groupedTimeSlots = computed<{ period: string; slots: string[] }[]>(() => {
    const order = ['Morning', 'Afternoon', 'Evening', 'Night'];
    const groups: Record<string, string[]> = {};
    for (const slot of timeSlots) {
        (groups[slotPeriodLabel(slot)] ??= []).push(slot);
    }
    return order.filter((p) => groups[p]?.length).map((p) => ({ period: p, slots: groups[p] }));
});

function getSlotPriceForCourt(slot: string): number {
    if (!selectedCourt.value) return 0;
    const customPrices = selectedCourt.value.slot_prices;
    if (customPrices && customPrices[slot] !== undefined && customPrices[slot] !== null) {
        const val = parseFloat(String(customPrices[slot]));
        if (!isNaN(val) && val > 0) {
            return val;
        }
    }
    const base = parseFloat(String(selectedCourt.value.base_price ?? '0'));
    return isNaN(base) ? 0 : base;
}

const calculatedPrice = computed(() => {
    if (!selectedCourt.value || form.time_slots.length === 0) return '0.00';
    const total = form.time_slots.reduce((sum, slot) => sum + getSlotPriceForCourt(slot), 0);
    return total.toFixed(2);
});

// --- Reset when the modal opens ---
watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            currentStep.value = 1;
            localErrors.value = {};
            form.reset();
            form.clearErrors();
            form.court_id = sortedCourts.value[0]?.id ?? null;
            form.date = todayDateString.value;
            weekOffset.value = 0;
        }
    },
);

function validateStep1(): boolean {
    const e: typeof localErrors.value = {};
    if (!form.court_id) e.court = 'Please select a court.';
    if (!form.date) e.date = 'Booking date is required.';
    if (form.time_slots.length === 0) e.time = 'Select at least one time slot.';
    localErrors.value = e;
    return Object.keys(e).length === 0;
}
function validateStep2(): boolean {
    const e: typeof localErrors.value = {};
    if (!form.name.trim()) e.name = 'Full name is required.';
    if (!form.email.trim()) e.email = 'Email is required.';
    if (!form.phone.trim()) e.phone = 'Phone number is required.';
    localErrors.value = e;
    return Object.keys(e).length === 0;
}

function nextStep() {
    if (validateStep1()) currentStep.value = 2;
}
function prevStep() {
    currentStep.value = 1;
}

function submit() {
    if (!validateStep1()) {
        currentStep.value = 1;
        return;
    }
    if (!validateStep2()) return;

    form.post(props.action, {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
}
</script>

<template>
    <Teleport to="body">
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            leave-active-class="transition duration-200 ease-in"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-neutral-900/60 p-4 backdrop-blur-md"
                @click.self="emit('close')"
            >
                <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 text-neutral-900 dark:text-white shadow-2xl">
                    <!-- Header -->
                    <div class="shrink-0 border-b border-neutral-200 dark:border-neutral-800 px-6 pt-5 pb-4 sm:px-8">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 pr-1">
                                <h2 class="text-lg font-bold tracking-tight text-neutral-900 dark:text-white">{{ wizardHeading.title }}</h2>
                                <p class="mt-0.5 text-xs text-neutral-500 dark:text-neutral-400">{{ wizardHeading.subtitle }}</p>
                            </div>
                            <button
                                type="button"
                                class="flex size-9 shrink-0 items-center justify-center rounded-full border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800 text-neutral-900 dark:text-white transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800 hover:text-emerald-600 cursor-pointer"
                                aria-label="Close"
                                @click="emit('close')"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-600">Step {{ currentStep }} of 2</span>
                            <span class="text-xs font-semibold text-neutral-500 dark:text-neutral-400">{{ currentStep === 1 ? 'Court & Schedule' : 'Customer Details' }}</span>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <div
                                v-for="stepNum in 2"
                                :key="stepNum"
                                class="h-1.5 flex-1 rounded-full transition-all duration-300"
                                :class="stepNum <= currentStep ? 'bg-emerald-600' : 'bg-neutral-200 dark:bg-neutral-800'"
                            ></div>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5 sm:px-8">
                        <!-- Server Validation / Submission Error Banner -->
                        <div
                            v-if="Object.keys(form.errors).length > 0"
                            class="flex items-start gap-2.5 rounded-xl border border-rose-500/30 bg-rose-500/10 p-3.5 text-xs text-rose-600 dark:text-rose-400 shadow-sm"
                        >
                            <svg class="mt-0.5 size-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <div>
                                <strong class="block font-bold text-sm">Booking Error</strong>
                                <ul class="mt-1 space-y-0.5 list-disc list-inside">
                                    <li v-for="(err, key) in form.errors" :key="key" class="font-medium">{{ err }}</li>
                                </ul>
                            </div>
                        </div>

                        <!-- Step 1 -->
                        <div v-if="currentStep === 1" class="space-y-4">
                            <!-- Date slider -->
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Booking Date</label>
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-sm font-bold text-neutral-900 dark:text-white">{{ monthYearLabel }}</span>
                                        <div class="flex items-center gap-1">
                                            <button type="button" @click="prevWeek" :disabled="weekOffset === 0" aria-label="Previous days" class="flex size-7 items-center justify-center rounded-full border border-neutral-200 dark:border-neutral-800 text-neutral-500 dark:text-neutral-400 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800 hover:text-emerald-600 disabled:cursor-not-allowed disabled:opacity-30 cursor-pointer">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                                            </button>
                                            <button type="button" @click="nextWeek" aria-label="Next days" class="flex size-7 items-center justify-center rounded-full border border-neutral-200 dark:border-neutral-800 text-neutral-500 dark:text-neutral-400 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800 hover:text-emerald-600 cursor-pointer">
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-7 gap-1.5">
                                    <button
                                        v-for="d in visibleDays"
                                        :key="toDateKey(d)"
                                        type="button"
                                        @click="selectDay(d)"
                                        class="group flex flex-col items-center gap-1.5 rounded-xl border py-2 transition-all cursor-pointer"
                                        :class="isSelectedDay(d) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40' : 'border-transparent hover:bg-neutral-100 dark:hover:bg-neutral-800'"
                                    >
                                        <span class="text-[10px] font-bold uppercase tracking-wide" :class="isSelectedDay(d) ? 'text-emerald-600' : 'text-neutral-500 dark:text-neutral-400'">{{ dayLabels[d.getDay()] }}</span>
                                        <span class="flex size-9 items-center justify-center rounded-full text-sm font-bold transition-all" :class="isSelectedDay(d) ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20' : 'bg-neutral-50 dark:bg-neutral-800 text-neutral-900 dark:text-white group-hover:bg-neutral-100 dark:group-hover:bg-neutral-800'">{{ d.getDate() }}</span>
                                        <span class="text-[8px] font-bold uppercase tracking-wide" :class="isSelectedDay(d) ? 'text-emerald-600' : 'text-neutral-400'">{{ isToday(d) ? 'Today' : ' ' }}</span>
                                    </button>
                                </div>
                                <p v-if="localErrors.date" class="mt-1 text-xs font-semibold text-rose-600">{{ localErrors.date }}</p>
                            </div>

                            <!-- Court slider -->
                            <div>
                                <div class="mb-1.5 flex items-center justify-between">
                                    <label class="block text-xs font-bold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Choose Court</label>
                                    <div v-if="sortedCourts.length > 2" class="flex items-center gap-1">
                                        <button type="button" @click="scrollCourts('prev')" aria-label="Previous courts" class="flex size-7 items-center justify-center rounded-full border border-neutral-200 dark:border-neutral-800 text-neutral-500 dark:text-neutral-400 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800 hover:text-emerald-600 cursor-pointer">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" /></svg>
                                        </button>
                                        <button type="button" @click="scrollCourts('next')" aria-label="Next courts" class="flex size-7 items-center justify-center rounded-full border border-neutral-200 dark:border-neutral-800 text-neutral-500 dark:text-neutral-400 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800 hover:text-emerald-600 cursor-pointer">
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                                        </button>
                                    </div>
                                </div>
                                <div ref="courtScroller" class="flex snap-x snap-mandatory gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                                    <button
                                        v-for="c in sortedCourts"
                                        :key="c.id"
                                        type="button"
                                        @click="form.court_id = c.id"
                                        class="relative flex w-auto min-w-20 shrink-0 snap-start flex-col items-center gap-0.5 rounded-xl border px-4 py-2.5 text-center transition-all cursor-pointer"
                                        :class="form.court_id === c.id ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 shadow-sm shadow-emerald-600/20 ring-1 ring-emerald-500' : 'border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800 hover:border-emerald-500/40 hover:bg-neutral-100 dark:hover:bg-neutral-800'"
                                    >
                                        <h4 class="whitespace-nowrap text-sm font-extrabold text-neutral-900 dark:text-white">{{ c.name }}</h4>
                                        <span class="text-sm font-extrabold text-emerald-600">₱{{ c.base_price ?? '0.00' }}</span>
                                    </button>
                                </div>
                                <p v-if="localErrors.court" class="mt-1 text-xs font-semibold text-rose-600">{{ localErrors.court }}</p>
                            </div>

                            <!-- Time slots -->
                            <div>
                                <label class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-neutral-500 dark:text-neutral-400">Select Time Slots</label>
                                <div class="space-y-3">
                                    <div v-for="group in groupedTimeSlots" :key="group.period">
                                        <h5 class="mb-1.5 px-0.5 text-[10px] font-bold uppercase tracking-[0.2em] text-neutral-500 dark:text-neutral-400">{{ group.period }}</h5>
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                            <label
                                                v-for="slot in group.slots"
                                                :key="slot"
                                                class="relative flex flex-col items-center justify-center rounded-lg border p-2 text-center transition-all select-none cursor-pointer"
                                                :class="form.time_slots.includes(slot) ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/40 font-extrabold text-emerald-600' : 'border-neutral-200 dark:border-neutral-800 text-neutral-500 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'"
                                            >
                                                <input type="checkbox" :value="slot" v-model="form.time_slots" class="sr-only" />
                                                <span class="text-xs font-bold">{{ slot }}</span>
                                                <span class="mt-0.5 text-[8px] tracking-wide" :class="form.time_slots.includes(slot) ? 'font-bold text-emerald-600' : 'text-neutral-400'">
                                                    {{ form.time_slots.includes(slot) ? 'Selected' : 'Available' }}
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <p v-if="localErrors.time" class="mt-1 text-xs font-semibold text-rose-600">{{ localErrors.time }}</p>
                            </div>
                        </div>

                        <!-- Step 2 -->
                        <div v-else class="space-y-4">
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label for="cb-name" class="mb-1.5 block text-xs font-bold uppercase text-neutral-500 dark:text-neutral-400">Full Name</label>
                                    <input id="cb-name" type="text" v-model="form.name" placeholder="Customer name" class="w-full rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800 px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" :class="{ 'border-rose-600': localErrors.name || form.errors.name }" />
                                    <p v-if="localErrors.name || form.errors.name" class="mt-1 text-xs font-semibold text-rose-600">{{ localErrors.name || form.errors.name }}</p>
                                </div>
                                <div>
                                    <label for="cb-phone" class="mb-1.5 block text-xs font-bold uppercase text-neutral-500 dark:text-neutral-400">Phone Number</label>
                                    <input id="cb-phone" type="text" v-model="form.phone" placeholder="0917 123 4567" class="w-full rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800 px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" :class="{ 'border-rose-600': localErrors.phone || form.errors.phone }" />
                                    <p v-if="localErrors.phone || form.errors.phone" class="mt-1 text-xs font-semibold text-rose-600">{{ localErrors.phone || form.errors.phone }}</p>
                                </div>
                            </div>
                            <div>
                                <label for="cb-email" class="mb-1.5 block text-xs font-bold uppercase text-neutral-500 dark:text-neutral-400">Email Address</label>
                                <input id="cb-email" type="email" v-model="form.email" placeholder="customer@email.com" class="w-full rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800 px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" :class="{ 'border-rose-600': localErrors.email || form.errors.email }" />
                                <p v-if="localErrors.email || form.errors.email" class="mt-1 text-xs font-semibold text-rose-600">{{ localErrors.email || form.errors.email }}</p>
                            </div>
                            <div>
                                <label for="cb-notes" class="mb-1.5 block text-xs font-bold uppercase text-neutral-500 dark:text-neutral-400">Notes / Internal Reference</label>
                                <input id="cb-notes" type="text" v-model="form.notes" placeholder="Walk-in, cash payment, phone reservation..." class="w-full rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800 px-4 py-2.5 text-sm outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500" />
                            </div>

                            <!-- Summary -->
                            <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-800 p-3 text-xs">
                                <div class="flex items-center justify-between"><span class="text-neutral-500 dark:text-neutral-400">Court</span><span class="font-bold text-neutral-900 dark:text-white">{{ selectedCourt?.name || '—' }}</span></div>
                                <div class="mt-1 flex items-center justify-between"><span class="text-neutral-500 dark:text-neutral-400">Date</span><span class="font-bold text-neutral-900 dark:text-white">{{ form.date || '—' }}</span></div>
                                <div class="mt-1 flex items-center justify-between"><span class="text-neutral-500 dark:text-neutral-400">Slots</span><span class="font-mono font-bold text-neutral-900 dark:text-white">{{ form.time_slots.length }}</span></div>
                                <div class="mt-1 flex items-center justify-between border-t border-neutral-200 dark:border-neutral-800 pt-1"><span class="text-neutral-500 dark:text-neutral-400">Total</span><span class="font-extrabold text-emerald-600">₱{{ calculatedPrice }}</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex shrink-0 items-center justify-between gap-3 border-t border-neutral-200 dark:border-neutral-800 px-6 py-4 sm:px-8">
                        <button v-if="currentStep === 2" type="button" @click="prevStep" class="rounded-xl border border-neutral-200 dark:border-neutral-800 px-4 py-2 text-xs font-bold text-neutral-500 dark:text-neutral-400 transition-colors hover:bg-neutral-100 dark:hover:bg-neutral-800">Back</button>
                        <span v-else class="text-xs font-bold text-emerald-600">₱{{ calculatedPrice }}</span>

                        <button v-if="currentStep === 1" type="button" @click="nextStep" class="rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white shadow transition-colors hover:bg-emerald-700">Continue</button>
                        <button v-else type="button" @click="submit" :disabled="form.processing" class="rounded-xl bg-emerald-600 px-5 py-2 text-xs font-bold text-white shadow transition-colors hover:bg-emerald-700 disabled:opacity-50">
                            {{ form.processing ? 'Creating…' : 'Create Booking' }}
                        </button>
                    </div>
                </div>
            </div>
        </transition>
    </Teleport>
</template>
