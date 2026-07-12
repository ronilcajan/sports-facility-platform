<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import type { PublicCourt } from '@/types';

const props = defineProps<{
    court: PublicCourt | null;
    isOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const page = usePage();
const currentUser = computed(
    () => page.props.auth?.user as { name: string; email: string } | undefined,
);

// Form Fields State
const form = ref({
    name: '',
    email: '',
    phone: '',
    date: '',
    time: [] as string[],
    notes: '',
});

// Form Errors State
const errors = ref({
    name: '',
    email: '',
    phone: '',
    date: '',
    time: '',
});

// Step of the Booking Flow: 'form' | 'submitting' | 'confirmed'
const step = ref<'form' | 'submitting' | 'confirmed'>('form');

// Loading text simulation sequence
const loadingText = ref('Verifying court schedule availability...');
const loadingStep = ref(1);

// Available Time Slots (Daily open hours 7 AM to 2 AM)
const timeSlots = [
    '07:00 AM',
    '08:00 AM',
    '09:00 AM',
    '10:00 AM',
    '11:00 AM',
    '12:00 PM',
    '01:00 PM',
    '02:00 PM',
    '03:00 PM',
    '04:00 PM',
    '05:00 PM',
    '06:00 PM',
    '07:00 PM',
    '08:00 PM',
    '09:00 PM',
    '10:00 PM',
    '11:00 PM',
    '12:00 AM',
    '01:00 AM',
    '02:00 AM',
];

// Calculated Duration based on selected checkmarked slots
const calculatedDuration = computed(() => {
    if (!props.court) {
        return 0;
    }
    return form.value.time.length * (props.court.slot_duration_minutes || 60);
});

// Calculated Price based on duration
const calculatedPrice = computed(() => {
    if (!props.court || form.value.time.length === 0) {
        return '0.00';
    }
    const base = parseFloat(props.court.base_price);
    return (base * form.value.time.length).toFixed(2);
});

// Local YYYY-MM-DD date string for native picker min validation
const todayDateString = computed(() => {
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
});

// Mock booked slots based deterministically on court and selected date
const bookedSlots = computed(() => {
    if (!props.court || !form.value.date) {
        return [];
    }
    const dateNum = form.value.date
        .split('-')
        .reduce((acc, val) => acc + parseInt(val), 0);
    const seed = (props.court.id + dateNum) % 5;

    if (seed === 0) {
        return ['08:00 AM', '10:00 AM', '04:00 PM', '07:00 PM'];
    } else if (seed === 1) {
        return ['09:00 AM', '11:00 AM', '05:00 PM', '08:00 PM'];
    } else if (seed === 2) {
        return ['07:00 AM', '12:00 PM', '06:00 PM', '09:00 PM'];
    } else if (seed === 3) {
        return ['10:00 AM', '01:00 PM', '03:00 PM', '10:00 PM'];
    } else {
        return ['08:00 AM', '02:00 PM', '05:00 PM', '11:00 PM'];
    }
});

function isSlotBooked(slot: string): boolean {
    return bookedSlots.value.includes(slot);
}

// Filter out time slots if they become booked when date changes
watch(
    () => form.value.date,
    () => {
        form.value.time = form.value.time.filter((slot) => !isSlotBooked(slot));
    },
);

// Pre-fill user data when modal opens or user logs in
watch(
    () => props.isOpen,
    (newVal) => {
        if (newVal) {
            step.value = 'form';
            errors.value = {
                name: '',
                email: '',
                phone: '',
                date: '',
                time: '',
            };

            // Default date to tomorrow
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            form.value.date = tomorrow.toISOString().split('T')[0];
            form.value.time = [];
            form.value.notes = '';

            if (currentUser.value) {
                form.value.name = currentUser.value.name || '';
                form.value.email = currentUser.value.email || '';
            } else {
                form.value.name = '';
                form.value.email = '';
            }
            form.value.phone = '';

            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        } else {
            // Restore body scroll
            document.body.style.overflow = '';
        }
    },
);

// Handle keydown for escape key
const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.isOpen) {
        close();
    }
};

onMounted(() => {
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeydown);
    document.body.style.overflow = '';
});

function close() {
    if (step.value !== 'submitting') {
        emit('close');
    }
}

// Inline validations
function validate(): boolean {
    let isValid = true;
    errors.value = { name: '', email: '', phone: '', date: '', time: '' };

    if (!form.value.name.trim()) {
        errors.value.name = 'Full name is required.';
        isValid = false;
    } else if (form.value.name.trim().length < 3) {
        errors.value.name = 'Name must be at least 3 characters.';
        isValid = false;
    }

    if (!form.value.email.trim()) {
        errors.value.email = 'Email address is required.';
        isValid = false;
    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) {
        errors.value.email = 'Please enter a valid email address.';
        isValid = false;
    }

    if (!form.value.phone.trim()) {
        errors.value.phone = 'Phone number is required.';
        isValid = false;
    } else if (!/^\+?[\d\s-]{8,15}$/.test(form.value.phone.trim())) {
        errors.value.phone =
            'Please enter a valid phone number (minimum 8 digits).';
        isValid = false;
    }

    if (!form.value.date) {
        errors.value.date = 'Booking date is required.';
        isValid = false;
    } else {
        const selected = new Date(form.value.date + 'T00:00:00');
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (selected < today) {
            errors.value.date = 'Date cannot be in the past.';
            isValid = false;
        }
    }

    if (!form.value.time || form.value.time.length === 0) {
        errors.value.time = 'At least one preferred time slot is required.';
        isValid = false;
    }

    return isValid;
}

// Simulated checkout process
function handleSubmit() {
    if (!validate()) {
        return;
    }

    step.value = 'submitting';
    loadingStep.value = 1;
    loadingText.value = 'Verifying court schedule availability...';

    // Step 2 loading text
    setTimeout(() => {
        loadingStep.value = 2;
        loadingText.value = 'Securing court reservation slot...';
    }, 900);

    // Step 3 loading text
    setTimeout(() => {
        loadingStep.value = 3;
        loadingText.value = 'Generating booking confirmation QR code...';
    }, 1800);

    // Confirmation screen
    setTimeout(() => {
        step.value = 'confirmed';
    }, 2700);
}
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="isOpen"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-surface-inverse/65 p-4 backdrop-blur-md"
        >
            <!-- Modal Container -->
            <div
                class="relative w-full max-w-lg scale-100 transform overflow-hidden rounded-2xl border border-line bg-surface text-content opacity-100 shadow-2xl transition-all duration-300"
                @click.stop
            >
                <!-- Close Button -->
                <button
                    v-if="step !== 'submitting'"
                    type="button"
                    class="absolute top-4 right-4 z-10 flex size-9 items-center justify-center rounded-full border border-line bg-surface-elevated/50 text-content transition-colors hover:bg-surface-elevated hover:text-brand"
                    aria-label="Close booking modal"
                    @click="close"
                >
                    <svg
                        class="size-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="2.5"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                </button>

                <!-- Stage 1: Booking Form -->
                <div v-if="step === 'form'" class="p-6 sm:p-8">
                    <header class="mb-6">
                        <span
                            class="text-xs font-bold tracking-[0.2em] text-brand uppercase"
                            >Court Booking</span
                        >
                        <h2
                            class="mt-1 font-display text-2xl font-black tracking-tight"
                        >
                            Reserve a Court
                        </h2>
                        <div
                            v-if="court"
                            class="mt-3 flex items-center gap-3 rounded-xl border border-line bg-surface-elevated/40 p-3"
                        >
                            <div
                                class="size-12 shrink-0 overflow-hidden rounded-lg border border-line bg-surface-inverse"
                            >
                                <img
                                    :src="
                                        court.primary_image_url ||
                                        '/images/court_pickleball.png'
                                    "
                                    :alt="court.name"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div>
                                <h4 class="text-sm font-extrabold">
                                    {{ court.name }}
                                </h4>
                                <p class="text-xs text-content-muted">
                                    {{ court.sport_type }} • Cushion Court
                                </p>
                            </div>
                            <div class="ml-auto text-right">
                                <span
                                    class="block text-xs text-[9px] font-bold text-content-muted uppercase"
                                    >Rate</span
                                >
                                <span class="text-sm font-extrabold text-brand"
                                    >${{ court.base_price }}</span
                                >
                                <span class="text-xs text-content-muted"
                                    >/{{ court.slot_duration_minutes }}m</span
                                >
                            </div>
                        </div>
                    </header>

                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <!-- Name & Contact -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label
                                    for="booking-name"
                                    class="mb-1.5 block text-xs font-bold text-content-muted uppercase"
                                    >Full Name</label
                                >
                                <input
                                    id="booking-name"
                                    type="text"
                                    v-model="form.name"
                                    placeholder="Enter your name"
                                    class="w-full rounded-xl border bg-surface-elevated/40 px-4 py-2.5 text-sm outline-none focus:border-brand focus:ring-1 focus:ring-brand"
                                    :class="{
                                        'border-destructive focus:border-destructive focus:ring-destructive':
                                            errors.name,
                                    }"
                                />
                                <p
                                    v-if="errors.name"
                                    class="mt-1 text-xs font-semibold text-destructive"
                                >
                                    {{ errors.name }}
                                </p>
                            </div>
                            <div>
                                <label
                                    for="booking-phone"
                                    class="mb-1.5 block text-xs font-bold text-content-muted uppercase"
                                    >Phone Number</label
                                >
                                <input
                                    id="booking-phone"
                                    type="tel"
                                    v-model="form.phone"
                                    placeholder="(512) 555-0199"
                                    class="w-full rounded-xl border bg-surface-elevated/40 px-4 py-2.5 text-sm outline-none focus:border-brand focus:ring-1 focus:ring-brand"
                                    :class="{
                                        'border-destructive focus:border-destructive focus:ring-destructive':
                                            errors.phone,
                                    }"
                                />
                                <p
                                    v-if="errors.phone"
                                    class="mt-1 text-xs font-semibold text-destructive"
                                >
                                    {{ errors.phone }}
                                </p>
                            </div>
                        </div>

                        <!-- Email -->
                        <div>
                            <label
                                for="booking-email"
                                class="mb-1.5 block text-xs font-bold text-content-muted uppercase"
                                >Email Address</label
                            >
                            <input
                                id="booking-email"
                                type="email"
                                v-model="form.email"
                                placeholder="your.email@example.com"
                                class="w-full rounded-xl border bg-surface-elevated/40 px-4 py-2.5 text-sm outline-none focus:border-brand focus:ring-1 focus:ring-brand"
                                :class="{
                                    'border-destructive focus:border-destructive focus:ring-destructive':
                                        errors.email,
                                }"
                            />
                            <p
                                v-if="errors.email"
                                class="mt-1 text-xs font-semibold text-destructive"
                            >
                                {{ errors.email }}
                            </p>
                        </div>

                        <!-- Date & Time Slot -->
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="col-span-full">
                                <label
                                    for="booking-date"
                                    class="mb-1.5 block text-xs font-bold text-content-muted uppercase"
                                    >Booking Date</label
                                >
                                <input
                                    id="booking-date"
                                    type="date"
                                    :min="todayDateString"
                                    v-model="form.date"
                                    class="w-full rounded-xl border bg-surface-elevated/40 px-4 py-2.5 text-sm outline-none focus:border-brand focus:ring-1 focus:ring-brand"
                                    :class="{
                                        'border-destructive focus:border-destructive focus:ring-destructive':
                                            errors.date,
                                    }"
                                />
                                <p
                                    v-if="errors.date"
                                    class="mt-1 text-xs font-semibold text-destructive"
                                >
                                    {{ errors.date }}
                                </p>
                            </div>

                            <div class="col-span-full">
                                <label
                                    class="mb-1.5 block text-xs font-bold text-content-muted uppercase"
                                    >Select Time Slots</label
                                >
                                <div
                                    class="grid max-h-48 grid-cols-2 gap-2 overflow-y-auto rounded-xl border border-line bg-surface-elevated/20 p-2 sm:grid-cols-3"
                                >
                                    <label
                                        v-for="slot in timeSlots"
                                        :key="slot"
                                        class="relative flex cursor-pointer flex-col items-center justify-center rounded-lg border p-2 text-center transition-all select-none"
                                        :class="[
                                            isSlotBooked(slot)
                                                ? 'cursor-not-allowed border-line bg-surface/30 opacity-50'
                                                : form.time.includes(slot)
                                                  ? 'border-brand bg-brand/5 font-extrabold text-brand'
                                                  : 'border-line text-content-muted hover:bg-surface-elevated/50',
                                        ]"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="slot"
                                            v-model="form.time"
                                            :disabled="isSlotBooked(slot)"
                                            class="sr-only"
                                        />
                                        <span class="text-xs font-bold">{{
                                            slot
                                        }}</span>
                                        <span
                                            v-if="isSlotBooked(slot)"
                                            class="mt-0.5 text-[8px] font-semibold tracking-tight text-destructive uppercase"
                                        >
                                            This court is already bookd
                                        </span>
                                        <span
                                            v-else
                                            class="mt-0.5 text-[8px] tracking-wide"
                                            :class="
                                                form.time.includes(slot)
                                                    ? 'text-brand'
                                                    : 'text-content-muted/65'
                                            "
                                        >
                                            {{
                                                form.time.includes(slot)
                                                    ? 'Selected'
                                                    : 'Available'
                                            }}
                                        </span>
                                    </label>
                                </div>
                                <p
                                    v-if="errors.time"
                                    class="mt-1 text-xs font-semibold text-destructive"
                                >
                                    {{ errors.time }}
                                </p>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label
                                for="booking-notes"
                                class="mb-1.5 block text-xs font-bold text-content-muted uppercase"
                                >Additional Notes (Optional)</label
                            >
                            <textarea
                                id="booking-notes"
                                v-model="form.notes"
                                placeholder="E.g., rental paddles needed, coaching inquiries, etc."
                                rows="2"
                                class="w-full resize-none rounded-xl border bg-surface-elevated/40 px-4 py-2.5 text-sm outline-none focus:border-brand focus:ring-1 focus:ring-brand"
                            ></textarea>
                        </div>

                        <!-- Submit Section -->
                        <div
                            class="flex items-center justify-between gap-4 border-t border-line pt-4"
                        >
                            <div>
                                <span
                                    class="block text-[10px] font-semibold tracking-wider text-content-muted uppercase"
                                    >Total Cost</span
                                >
                                <span class="text-2xl font-black text-content"
                                    >${{ calculatedPrice }}</span
                                >
                            </div>
                            <div class="flex gap-3">
                                <button
                                    type="button"
                                    class="rounded-full border border-line px-5 py-2.5 text-sm font-semibold text-content-muted transition-colors hover:bg-surface-elevated"
                                    @click="close"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    class="rounded-full bg-brand px-6 py-2.5 text-sm font-bold text-brand-foreground shadow-lg shadow-brand/20 transition-all hover:-translate-y-0.5 hover:bg-brand/95 hover:shadow-brand/35"
                                >
                                    Confirm Reservation
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Stage 2: Simulating Submission -->
                <div
                    v-else-if="step === 'submitting'"
                    class="flex min-h-[350px] flex-col items-center justify-center p-12 text-center"
                >
                    <!-- Sports-themed animated loader -->
                    <div
                        class="relative mb-8 flex size-16 items-center justify-center"
                    >
                        <div
                            class="absolute inset-0 animate-pulse rounded-full border-4 border-brand/20"
                        ></div>
                        <div
                            class="absolute inset-0 animate-spin rounded-full border-4 border-transparent border-t-brand border-r-brand"
                        ></div>
                        <!-- Centered pickleball wiffle ball dot motif -->
                        <div
                            class="flex size-6 items-center justify-center rounded-full bg-brand shadow"
                        >
                            <span class="size-1 rounded-full bg-white"></span>
                        </div>
                    </div>

                    <h3
                        class="font-display text-xl font-extrabold tracking-tight text-content"
                    >
                        Processing Reservation
                    </h3>
                    <div class="mt-4 flex flex-col items-center gap-1.5">
                        <p
                            class="text-sm font-medium text-content-muted transition-all duration-300"
                        >
                            {{ loadingText }}
                        </p>

                        <!-- Mini step indicators -->
                        <div class="mt-2 flex gap-1.5">
                            <span
                                class="size-2 rounded-full transition-colors duration-300"
                                :class="
                                    loadingStep >= 1 ? 'bg-brand' : 'bg-line'
                                "
                            ></span>
                            <span
                                class="size-2 rounded-full transition-colors duration-300"
                                :class="
                                    loadingStep >= 2 ? 'bg-brand' : 'bg-line'
                                "
                            ></span>
                            <span
                                class="size-2 rounded-full transition-colors duration-300"
                                :class="
                                    loadingStep >= 3 ? 'bg-brand' : 'bg-line'
                                "
                            ></span>
                        </div>
                    </div>
                </div>

                <!-- Stage 3: Confirmed Voucher Ticket -->
                <div
                    v-else-if="step === 'confirmed'"
                    class="flex flex-col items-center bg-chalk/30 p-6 text-center sm:p-8"
                >
                    <div
                        class="mb-4 flex size-14 animate-bounce items-center justify-center rounded-full border border-emerald-500/20 bg-emerald-500/10 text-emerald-500 shadow-inner"
                    >
                        <svg
                            class="size-7"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            stroke-width="3"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M5 13l4 4L19 7"
                            />
                        </svg>
                    </div>

                    <span
                        class="text-xs font-bold tracking-[0.2em] text-emerald-500 uppercase"
                        >RESERVATION SECURED</span
                    >
                    <h3
                        class="mt-1 font-display text-2xl font-black tracking-tight text-content"
                    >
                        Booking Confirmed!
                    </h3>
                    <p class="mt-2 max-w-sm text-sm text-content-muted">
                        Thank you, {{ form.name }}. Your court reservation is
                        confirmed. Show this voucher QR code upon arrival at the
                        facility shop.
                    </p>

                    <!-- Ticket Voucher Layout -->
                    <div
                        class="relative mt-6 flex w-full flex-col overflow-hidden rounded-2xl border border-line bg-surface text-left shadow-md"
                    >
                        <!-- Court header background -->
                        <div
                            class="flex items-center gap-3 bg-surface-inverse p-4 text-content-inverse"
                        >
                            <div
                                class="size-10 shrink-0 overflow-hidden rounded-lg border border-content-inverse/10 bg-surface/10"
                            >
                                <img
                                    :src="
                                        court?.primary_image_url ||
                                        '/images/court_pickleball.png'
                                    "
                                    :alt="court?.name"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <div>
                                <h4
                                    class="text-sm font-extrabold tracking-tight text-content-inverse"
                                >
                                    {{ court?.name }}
                                </h4>
                                <p
                                    class="text-[10px] font-semibold tracking-wider text-content-muted uppercase"
                                >
                                    Austin Dinkyard Facility
                                </p>
                            </div>
                            <span
                                class="ml-auto rounded-full border border-emerald-500/40 bg-emerald-500/25 px-2.5 py-0.5 text-[9px] font-bold tracking-widest text-emerald-400 uppercase"
                            >
                                Confirmed
                            </span>
                        </div>

                        <!-- Ticket details -->
                        <div
                            class="grid gap-4 border-b border-line bg-surface-elevated/30 p-5 text-sm sm:grid-cols-2"
                        >
                            <div>
                                <span
                                    class="block text-[9px] font-bold tracking-wider text-content-muted uppercase"
                                    >Player Name</span
                                >
                                <span
                                    class="mt-0.5 block truncate font-bold text-content"
                                    >{{ form.name }}</span
                                >
                            </div>
                            <div>
                                <span
                                    class="block text-[9px] font-bold tracking-wider text-content-muted uppercase"
                                    >Reservation Date</span
                                >
                                <span
                                    class="mt-0.5 block font-bold text-content"
                                    >{{ form.date }}</span
                                >
                            </div>
                            <div>
                                <span
                                    class="block text-[9px] font-bold tracking-wider text-content-muted uppercase"
                                    >Preferred Time</span
                                >
                                <span
                                    class="mt-0.5 block font-bold text-brand text-content"
                                    >{{ form.time.join(', ') }}</span
                                >
                            </div>
                            <div>
                                <span
                                    class="block text-[9px] font-bold tracking-wider text-content-muted uppercase"
                                    >Duration & Rate</span
                                >
                                <span
                                    class="mt-0.5 block font-bold text-content"
                                    >{{ calculatedDuration }} minutes • ${{
                                        calculatedPrice
                                    }}</span
                                >
                            </div>
                        </div>

                        <!-- Ticket QR code and guidelines -->
                        <div
                            class="relative flex flex-col items-center justify-center bg-surface p-6"
                        >
                            <!-- Curved ticket punches at the dividing line -->
                            <div
                                class="absolute -top-3 -left-3 z-10 size-6 shrink-0 rounded-full border border-line bg-chalk"
                            ></div>
                            <div
                                class="absolute -top-3 -right-3 z-10 size-6 shrink-0 rounded-full border border-line bg-chalk"
                            ></div>

                            <!-- Styled QR Code SVG with neon details -->
                            <div
                                class="rounded-xl border border-line bg-surface-elevated p-3 shadow-inner transition-transform duration-300 hover:scale-[1.02]"
                            >
                                <svg
                                    class="size-28 text-content-inverse"
                                    viewBox="0 0 100 100"
                                    fill="currentColor"
                                >
                                    <!-- QR outline blocks -->
                                    <rect
                                        x="5"
                                        y="5"
                                        width="25"
                                        height="25"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="5"
                                    />
                                    <rect
                                        x="12.5"
                                        y="12.5"
                                        width="10"
                                        height="10"
                                        fill="currentColor"
                                    />

                                    <rect
                                        x="70"
                                        y="5"
                                        width="25"
                                        height="25"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="5"
                                    />
                                    <rect
                                        x="77.5"
                                        y="12.5"
                                        width="10"
                                        height="10"
                                        fill="currentColor"
                                    />

                                    <rect
                                        x="5"
                                        y="70"
                                        width="25"
                                        height="25"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="5"
                                    />
                                    <rect
                                        x="12.5"
                                        y="77.5"
                                        width="10"
                                        height="10"
                                        fill="currentColor"
                                    />

                                    <!-- Random QR grid noise -->
                                    <rect
                                        x="35"
                                        y="5"
                                        width="8"
                                        height="8"
                                        fill="currentColor"
                                    />
                                    <rect
                                        x="48"
                                        y="10"
                                        width="12"
                                        height="6"
                                        fill="currentColor"
                                    />
                                    <rect
                                        x="35"
                                        y="20"
                                        width="15"
                                        height="4"
                                        fill="currentColor"
                                    />

                                    <rect
                                        x="70"
                                        y="35"
                                        width="10"
                                        height="10"
                                        fill="currentColor"
                                    />
                                    <rect
                                        x="85"
                                        y="40"
                                        width="8"
                                        height="15"
                                        fill="currentColor"
                                    />
                                    <rect
                                        x="72"
                                        y="55"
                                        width="14"
                                        height="6"
                                        fill="currentColor"
                                    />

                                    <rect
                                        x="35"
                                        y="70"
                                        width="6"
                                        height="18"
                                        fill="currentColor"
                                    />
                                    <rect
                                        x="45"
                                        y="80"
                                        width="15"
                                        height="8"
                                        fill="currentColor"
                                    />
                                    <rect
                                        x="52"
                                        y="68"
                                        width="8"
                                        height="8"
                                        fill="currentColor"
                                    />

                                    <rect
                                        x="40"
                                        y="40"
                                        width="20"
                                        height="20"
                                        fill="none"
                                        stroke="var(--color-brand)"
                                        stroke-width="4"
                                    />
                                    <!-- Neon-volt wiffle ball dot in the center of QR code -->
                                    <circle
                                        cx="50"
                                        cy="50"
                                        r="4"
                                        fill="var(--color-volt)"
                                    />
                                </svg>
                            </div>
                            <span
                                class="mt-3 font-mono text-[10px] tracking-widest text-content-muted uppercase"
                                >DY-RESRV-{{
                                    Math.floor(100000 + Math.random() * 900000)
                                }}</span
                            >
                        </div>
                    </div>

                    <!-- Confirm CTA Buttons -->
                    <div class="mt-8 flex w-full gap-4">
                        <button
                            type="button"
                            class="flex-1 rounded-full border border-line py-3 text-sm font-semibold text-content transition-colors hover:bg-surface-elevated"
                            onclick="window.print()"
                        >
                            Print Voucher
                        </button>
                        <button
                            type="button"
                            class="flex-1 rounded-full bg-brand py-3 text-sm font-bold text-brand-foreground shadow-lg shadow-brand/20 transition-all hover:-translate-y-0.5 hover:bg-brand/95 hover:shadow-brand/35"
                            @click="close"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
/* Scoped overrides for absolute scroll locker */
</style>
