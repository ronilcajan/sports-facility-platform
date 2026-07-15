<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import type { PublicCourt } from '@/types';
import type { CatalogVenue } from '@/components/site/SiteVenueCard.vue';

const props = defineProps<{
    court?: PublicCourt | null;
    venue?: CatalogVenue | null;
    venues?: CatalogVenue[];
    isOpen: boolean;
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const page = usePage();
const currentUser = computed(
    () => page.props.auth?.user as { name: string; email: string } | undefined,
);

// Selection State
const selectedVenueId = ref<number | null>(null);
const selectedCourtId = ref<number | null>(null);
const realtimeBookedSlotsMap = ref<Record<string, string[]>>({});

const activeVenue = computed<CatalogVenue | null>(() => {
    if (props.venue) return props.venue;
    if (selectedVenueId.value && props.venues) {
        return props.venues.find(v => v.id === selectedVenueId.value) || null;
    }
    if (props.court?.venue) {
        return props.court.venue as any;
    }
    return null;
});

const availableCourts = computed<PublicCourt[]>(() => {
    if (props.venue?.courts && props.venue.courts.length > 0) {
        return props.venue.courts;
    }
    if (activeVenue.value?.courts && activeVenue.value.courts.length > 0) {
        return activeVenue.value.courts;
    }
    if (props.court) {
        return [props.court];
    }
    return [];
});

// Courts ordered alphabetically: Court A, Court B, Court C...
const sortedAvailableCourts = computed<PublicCourt[]>(() => {
    return [...availableCourts.value].sort((a, b) => a.name.localeCompare(b.name));
});

const selectedCourt = computed<PublicCourt | null>(() => {
    if (selectedCourtId.value) {
        const found = sortedAvailableCourts.value.find(c => c.id === selectedCourtId.value);
        if (found) return found;
    }
    if (props.court) return props.court;
    // Default to first non-fully-booked court or first court available
    const firstAvail = sortedAvailableCourts.value.find(c => !isCourtFullyBooked(c));
    return firstAvail || sortedAvailableCourts.value[0] || null;
});

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
    court: '',
});

const bookingDetails = ref<{
    id: number;
    reference_code: string;
    name: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_url: string;
} | null>(null);

// Step of the Booking Flow: 'form' | 'submitting' | 'confirmed'
const step = ref<'form' | 'submitting' | 'confirmed'>('form');
const currentWizardStep = ref(1);

// Receipt Upload State
const receiptFile = ref<File | null>(null);
const receiptPreviewUrl = ref<string | null>(null);
const receiptError = ref('');

function handleReceiptUpload(e: Event) {
    const target = e.target as HTMLInputElement;
    const file = target.files?.[0];
    if (!file) {
        return;
    }
    if (!file.type.startsWith('image/')) {
        receiptError.value =
            'Please select a valid image file (PNG, JPG, WEBP).';
        return;
    }
    receiptError.value = '';
    receiptFile.value = file;

    // Generate preview
    const reader = new FileReader();
    reader.onload = (event) => {
        receiptPreviewUrl.value = event.target?.result as string;
    };
    reader.readAsDataURL(file);
}

function removeReceipt() {
    receiptFile.value = null;
    receiptPreviewUrl.value = null;
    receiptError.value = '';
}

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
    if (!selectedCourt.value) {
        return 0;
    }
    return form.value.time.length * (selectedCourt.value.slot_duration_minutes || 60);
});

// Calculated Price based on duration
const calculatedPrice = computed(() => {
    if (!selectedCourt.value || form.value.time.length === 0) {
        return '0.00';
    }
    const base = parseFloat(selectedCourt.value.base_price);
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

// Fetch real-time court availability from server
async function fetchRealtimeAvailability() {
    if (!form.value.date || typeof window === 'undefined') return;
    try {
        const res = await fetch(`/bookings/availability?date=${encodeURIComponent(form.value.date)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        if (res.ok) {
            const data = await res.json();
            realtimeBookedSlotsMap.value = data.booked_slots || {};
        }
    } catch (e) {
        // Fallback gracefully
    }
}

// Compute booked slots for a specific court id
function getCourtBookedSlots(courtId: number): string[] {
    const dbSlots = realtimeBookedSlotsMap.value[String(courtId)] || [];

    // Combine with deterministic seed mock logic for consistent demo testing
    let seedSlots: string[] = [];
    if (form.value.date) {
        const dateNum = form.value.date.split('-').reduce((acc, val) => acc + parseInt(val), 0);
        const seed = (courtId + dateNum) % 5;
        if (seed === 0) {
            seedSlots = ['08:00 AM', '10:00 AM', '04:00 PM', '07:00 PM'];
        } else if (seed === 1) {
            seedSlots = ['09:00 AM', '11:00 AM', '05:00 PM', '08:00 PM'];
        } else if (seed === 2) {
            seedSlots = ['07:00 AM', '12:00 PM', '06:00 PM', '09:00 PM'];
        } else if (seed === 3) {
            seedSlots = ['10:00 AM', '01:00 PM', '03:00 PM', '10:00 PM'];
        } else {
            seedSlots = ['08:00 AM', '02:00 PM', '05:00 PM', '11:00 PM'];
        }
    }

    return Array.from(new Set([...dbSlots, ...seedSlots]));
}

function isSlotBooked(slot: string): boolean {
    if (!selectedCourt.value) return false;
    return getCourtBookedSlots(selectedCourt.value.id).includes(slot);
}

function isCourtFullyBooked(court: PublicCourt): boolean {
    const booked = getCourtBookedSlots(court.id);
    return booked.length >= timeSlots.length;
}

// Watch date changes to refresh server availability and unselect occupied slots
watch(
    [() => form.value.date, () => selectedCourtId.value],
    async () => {
        await fetchRealtimeAvailability();
        form.value.time = form.value.time.filter((slot) => !isSlotBooked(slot));
    }
);

// Pre-fill user data and initialize selections when modal opens
watch(
    () => props.isOpen,
    async (newVal) => {
        if (newVal) {
            step.value = 'form';
            currentWizardStep.value = 1;
            receiptFile.value = null;
            receiptPreviewUrl.value = null;
            receiptError.value = '';
            bookingDetails.value = null;
            errors.value = {
                name: '',
                email: '',
                phone: '',
                date: '',
                time: '',
                court: '',
            };

            // Set up initial venue & court selection
            if (props.venue) {
                selectedVenueId.value = props.venue.id;
                if (props.venue.courts && props.venue.courts.length > 0) {
                    selectedCourtId.value = props.venue.courts[0].id;
                }
            } else if (props.court) {
                selectedCourtId.value = props.court.id;
                if (props.court.venue) {
                    selectedVenueId.value = props.court.venue.id;
                }
            } else if (props.venues && props.venues.length > 0) {
                selectedVenueId.value = props.venues[0].id;
                if (props.venues[0].courts && props.venues[0].courts.length > 0) {
                    selectedCourtId.value = props.venues[0].courts[0].id;
                }
            }

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

            await fetchRealtimeAvailability();

            // Auto-select first available court if currently selected court is fully booked
            if (selectedCourt.value && isCourtFullyBooked(selectedCourt.value)) {
                const available = sortedAvailableCourts.value.find(c => !isCourtFullyBooked(c));
                if (available) {
                    selectedCourtId.value = available.id;
                }
            }

            // Prevent body scroll
            if (typeof document !== 'undefined') {
                document.body.style.overflow = 'hidden';
            }
        } else {
            // Restore body scroll
            if (typeof document !== 'undefined') {
                document.body.style.overflow = '';
            }
        }
    },
    { immediate: true }
);

// Update courts list when venue selection changes manually
function handleVenueChange() {
    if (sortedAvailableCourts.value.length > 0) {
        const available = sortedAvailableCourts.value.find(c => !isCourtFullyBooked(c));
        selectedCourtId.value = available ? available.id : sortedAvailableCourts.value[0].id;
    } else {
        selectedCourtId.value = null;
    }
}

// Handle keydown for escape key
const handleKeydown = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && props.isOpen) {
        close();
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('keydown', handleKeydown);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('keydown', handleKeydown);
    }
    if (typeof document !== 'undefined') {
        document.body.style.overflow = '';
    }
});

function close() {
    if (step.value !== 'submitting') {
        emit('close');
    }
}

// Step 1 Validation
function validateStep1(): boolean {
    errors.value.date = '';
    errors.value.time = '';
    errors.value.court = '';
    let isValid = true;

    if (!selectedCourt.value) {
        errors.value.court = 'Please select a court.';
        isValid = false;
    } else if (isCourtFullyBooked(selectedCourt.value)) {
        errors.value.court = 'This court is already fully booked for the selected date. Please choose another court.';
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

// Step 2 Validation
function validateStep2(): boolean {
    errors.value.name = '';
    errors.value.email = '';
    errors.value.phone = '';
    let isValid = true;

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

    return isValid;
}

function nextStep() {
    if (currentWizardStep.value === 1) {
        if (validateStep1()) {
            currentWizardStep.value = 2;
        }
    } else if (currentWizardStep.value === 2) {
        if (validateStep2()) {
            currentWizardStep.value = 3;
        }
    }
}

function prevStep() {
    if (currentWizardStep.value > 1) {
        currentWizardStep.value--;
    }
}

// Persist booking to database
function handleSubmit() {
    if (!validateStep1() || !validateStep2()) {
        return;
    }
    if (!receiptFile.value) {
        receiptError.value = 'Payment receipt is required.';
        return;
    }

    step.value = 'submitting';
    loadingStep.value = 1;
    loadingText.value = 'Verifying court schedule availability...';

    // Construct form data with the receipt image file
    const formData = new FormData();
    formData.append('court_id', String(selectedCourt.value?.id));
    formData.append('name', form.value.name);
    formData.append('email', form.value.email);
    formData.append('phone', form.value.phone);
    formData.append('date', form.value.date);
    form.value.time.forEach((t) => formData.append('time[]', t));
    formData.append('notes', form.value.notes);
    formData.append('receipt', receiptFile.value);

    // Send HTTP POST request to Laravel backend
    const uploadPromise = fetch('/bookings', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            Accept: 'application/json',
        },
        body: formData,
    }).then(async (res) => {
        if (!res.ok) {
            const errData = await res.json();
            throw errData;
        }
        return res.json();
    });

    // Step 2 loading text sequence
    setTimeout(() => {
        loadingStep.value = 2;
        loadingText.value = 'Verifying uploaded payment receipt...';
    }, 900);

    // Step 3 loading text sequence
    setTimeout(() => {
        loadingStep.value = 3;
        loadingText.value = 'Generating booking confirmation QR code...';
    }, 1800);

    // Transition to confirmed after minimum animation time (2.7s) and upload complete
    Promise.all([
        uploadPromise,
        new Promise((resolve) => setTimeout(resolve, 2700)),
    ])
        .then(([resData]) => {
            bookingDetails.value = resData.booking;
            step.value = 'confirmed';
        })
        .catch((err) => {
            step.value = 'form';
            if (err && err.errors) {
                if (err.errors.name) {
                    errors.value.name = err.errors.name[0];
                }
                if (err.errors.email) {
                    errors.value.email = err.errors.email[0];
                }
                if (err.errors.phone) {
                    errors.value.phone = err.errors.phone[0];
                }
                if (err.errors.date) {
                    errors.value.date = err.errors.date[0];
                    currentWizardStep.value = 1;
                }
                if (err.errors.time) {
                    errors.value.time = err.errors.time[0];
                    currentWizardStep.value = 1;
                }
                if (err.errors.receipt) {
                    receiptError.value = err.errors.receipt[0];
                    currentWizardStep.value = 3;
                }
            } else {
                receiptError.value =
                    err?.message ||
                    'An unexpected error occurred. Please try again.';
                currentWizardStep.value = 3;
            }
        });
}

function downloadVoucher() {
    if (!bookingDetails.value && !selectedCourt.value) {
        return;
    }

    const reference = bookingDetails.value
        ? bookingDetails.value.reference_code
        : 'DY-RESRV-MOCK';
    const courtName = selectedCourt.value ? selectedCourt.value.name : 'N/A';
    const venueName = activeVenue.value ? activeVenue.value.name : '';
    const playerName = bookingDetails.value
        ? bookingDetails.value.name
        : form.value.name;
    const date = bookingDetails.value
        ? bookingDetails.value.date
        : form.value.date;
    const times = bookingDetails.value
        ? bookingDetails.value.time_slots.join(', ')
        : form.value.time.join(', ');
    const duration = bookingDetails.value
        ? bookingDetails.value.time_slots.length *
          (selectedCourt.value?.slot_duration_minutes || 60)
        : calculatedDuration.value;
    const price = bookingDetails.value
        ? bookingDetails.value.total_price
        : calculatedPrice.value;

    const content = `=========================================
      COURT RESERVATION VOUCHER
=========================================
Booking Reference : ${reference}
Venue Location    : ${venueName}
Court Name        : ${courtName}
Player Name       : ${playerName}
Reservation Date  : ${date}
Preferred Time    : ${times}
Duration          : ${duration} minutes
Total Amount      : $${price}
Status            : CONFIRMED
=========================================
Show this voucher upon arrival at the
facility shop. Thank you for booking!
=========================================`;

    const blob = new Blob([content], { type: 'text/plain;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `Voucher-${reference}.txt`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url);
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

                <!-- Stage 1: Booking Wizard -->
                <div v-if="step === 'form'" class="p-6 sm:p-8">
                    <!-- Wizard Progress Header -->
                    <div class="mb-6">
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-bold tracking-[0.2em] text-brand uppercase"
                                >Step {{ currentWizardStep }} of 3</span
                            >
                            <span
                                class="text-xs font-semibold text-content-muted"
                            >
                                {{
                                    currentWizardStep === 1
                                        ? 'Court & Schedule'
                                        : currentWizardStep === 2
                                          ? 'User Details'
                                          : 'Payment Details'
                                }}
                            </span>
                        </div>
                        <!-- Horizontal progress bar tracker -->
                        <div class="mt-3 flex items-center gap-2">
                            <div
                                v-for="stepNum in 3"
                                :key="stepNum"
                                class="h-1.5 flex-1 rounded-full transition-all duration-300"
                                :class="[
                                    stepNum < currentWizardStep
                                        ? 'bg-brand'
                                        : stepNum === currentWizardStep
                                          ? 'animate-pulse bg-brand'
                                          : 'bg-line',
                                ]"
                            ></div>
                        </div>
                    </div>

                    <form @submit.prevent="handleSubmit" class="space-y-4">
                        <!-- Step 1: Venue & Court Selection -->
                        <div v-if="currentWizardStep === 1" class="space-y-4">
                            <header class="mb-2">
                                <h3
                                    class="font-display text-lg font-bold tracking-tight text-content"
                                >
                                    Select Venue & Court
                                </h3>
                            </header>

                            <!-- 1. Venue Selection (Image-Based Cards) -->
                            <div v-if="props.venues && props.venues.length > 0" class="space-y-2">
                                <label class="block text-xs font-bold text-content-muted uppercase tracking-wider">
                                    Select Venue Facility
                                </label>
                                <div class="grid gap-2.5 sm:grid-cols-3 max-h-48 overflow-y-auto pr-1">
                                    <div
                                        v-for="v in props.venues"
                                        :key="v.id"
                                        @click="selectedVenueId = v.id; handleVenueChange()"
                                        class="relative flex flex-col justify-between rounded-xl border p-2.5 transition-all cursor-pointer select-none overflow-hidden"
                                        :class="[
                                            selectedVenueId === v.id
                                                ? 'border-brand bg-brand/5 shadow-md shadow-brand/10 ring-1 ring-brand'
                                                : 'border-line bg-surface-elevated/40 hover:bg-surface-elevated hover:border-brand/40'
                                        ]"
                                    >
                                        <div class="relative aspect-[16/9] w-full overflow-hidden rounded-lg bg-surface-inverse">
                                            <img
                                                :src="v.cover_image_url || '/images/hero_pickleball.png'"
                                                :alt="v.name"
                                                class="h-full w-full object-cover"
                                            />
                                            <span class="absolute top-1.5 right-1.5 rounded-full bg-brand/90 px-2 py-0.5 text-[9px] font-bold text-brand-foreground shadow">
                                                {{ v.courts_count }} {{ v.courts_count === 1 ? 'Court' : 'Courts' }}
                                            </span>
                                        </div>

                                        <div class="mt-2 flex items-center justify-between">
                                            <div class="min-w-0 pr-1">
                                                <h4 class="text-xs font-black text-content truncate">{{ v.name }}</h4>
                                                <p v-if="v.address" class="text-[10px] text-content-muted truncate">{{ v.address }}</p>
                                            </div>
                                            <div
                                                class="size-4 shrink-0 rounded-full border flex items-center justify-center transition-colors"
                                                :class="selectedVenueId === v.id ? 'border-brand bg-brand text-brand-foreground' : 'border-line bg-surface'"
                                            >
                                                <div v-if="selectedVenueId === v.id" class="size-1.5 rounded-full bg-white" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Date Selection Field -->
                            <div>
                                <label
                                    for="booking-date"
                                    class="mb-1.5 block text-xs font-bold text-content-muted uppercase tracking-wider"
                                    >Booking Date</label
                                >
                                <input
                                    id="booking-date"
                                    type="date"
                                    :min="todayDateString"
                                    v-model="form.date"
                                    class="w-full rounded-xl border border-line bg-surface-elevated/40 px-4 py-2.5 text-sm outline-none focus:border-brand focus:ring-1 focus:ring-brand"
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

                            <!-- 3. Court Selection (Dropdown Selection) -->
                            <div>
                                <label
                                    for="court-select"
                                    class="mb-1.5 block text-xs font-bold text-content-muted uppercase tracking-wider"
                                    >Choose Available Court</label
                                >
                                <select
                                    id="court-select"
                                    v-model="selectedCourtId"
                                    class="w-full rounded-xl border border-line bg-surface-elevated/40 px-4 py-2.5 text-sm font-semibold text-content outline-none focus:border-brand focus:ring-1 focus:ring-brand cursor-pointer"
                                >
                                    <option
                                        v-for="c in sortedAvailableCourts"
                                        :key="c.id"
                                        :value="c.id"
                                        :disabled="isCourtFullyBooked(c)"
                                    >
                                        {{ c.name }} ({{ c.sport_type }}) — ${{ c.base_price }}/{{ c.slot_duration_minutes }}m {{ isCourtFullyBooked(c) ? ' [Fully Booked]' : '' }}
                                    </option>
                                </select>
                                <p v-if="errors.court" class="mt-1 text-xs font-semibold text-destructive">
                                    {{ errors.court }}
                                </p>
                            </div>

                            <!-- Active Selected Court Display Card -->
                            <div
                                v-if="selectedCourt"
                                class="flex items-center gap-3 rounded-xl border border-line bg-surface-elevated/40 p-4"
                            >
                                <div>
                                    <h4 class="text-sm font-extrabold text-content">
                                        {{ selectedCourt.name }}
                                    </h4>
                                    <p class="text-xs text-content-muted">
                                        {{ activeVenue ? activeVenue.name + ' • ' : '' }}{{ selectedCourt.sport_type }}
                                    </p>
                                </div>
                                <div class="ml-auto text-right">
                                    <span
                                        class="block text-[9px] font-bold text-content-muted uppercase"
                                        >Rate</span
                                    >
                                    <span
                                        class="text-sm font-extrabold text-brand"
                                        >${{ selectedCourt.base_price }}</span
                                    >
                                    <span class="text-xs text-content-muted"
                                        >/{{
                                            selectedCourt.slot_duration_minutes
                                        }}m</span
                                    >
                                </div>
                            </div>

                            <!-- Time Slots Grid -->
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-bold text-content-muted uppercase tracking-wider"
                                    >Select Time Slots</label
                                >
                                <div
                                    class="grid max-h-48 grid-cols-2 gap-2 overflow-y-auto rounded-xl border border-line bg-surface-elevated/20 p-2 sm:grid-cols-3"
                                >
                                    <label
                                        v-for="slot in timeSlots"
                                        :key="slot"
                                        class="relative flex flex-col items-center justify-center rounded-lg border p-2 text-center transition-all select-none"
                                        :class="[
                                            isSlotBooked(slot)
                                                ? 'cursor-not-allowed border-line bg-surface/30 opacity-40 pointer-events-none'
                                                : form.time.includes(slot)
                                                  ? 'border-brand bg-brand/5 font-extrabold text-brand cursor-pointer'
                                                  : 'border-line text-content-muted hover:bg-surface-elevated/50 cursor-pointer',
                                        ]"
                                    >
                                        <input
                                            type="checkbox"
                                            :value="slot"
                                            v-model="form.time"
                                            :disabled="isSlotBooked(slot)"
                                            class="sr-only"
                                        />
                                        <span class="text-xs font-bold">{{ slot }}</span>
                                        <span
                                            v-if="isSlotBooked(slot)"
                                            class="mt-0.5 text-[8px] font-bold tracking-tight text-destructive uppercase"
                                        >
                                            Already Booked
                                        </span>
                                        <span
                                            v-else
                                            class="mt-0.5 text-[8px] tracking-wide"
                                            :class="
                                                form.time.includes(slot)
                                                    ? 'text-brand font-bold'
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

                            <!-- Running Fee Summary for Step 1 -->
                            <div
                                class="mt-6 flex items-center justify-between border-t border-line pt-4"
                            >
                                <div>
                                    <span
                                        class="block text-[9px] font-semibold text-content-muted uppercase"
                                        >Duration & Estimate</span
                                    >
                                    <span class="text-xs text-content-muted"
                                        >{{ calculatedDuration }} minutes •
                                        <strong class="text-sm text-content"
                                            >${{ calculatedPrice }}</strong
                                        ></span
                                    >
                                </div>
                                <div class="flex gap-2">
                                    <button
                                        type="button"
                                        class="rounded-full border border-line px-5 py-2.5 text-sm font-semibold text-content-muted transition-colors hover:bg-surface-elevated cursor-pointer"
                                        @click="close"
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-full bg-brand px-6 py-2.5 text-sm font-bold text-brand-foreground shadow-lg shadow-brand/20 transition-all hover:-translate-y-0.5 hover:bg-brand/95 cursor-pointer"
                                        @click="nextStep"
                                    >
                                        Next
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Step 2: User Details -->
                        <div
                            v-else-if="currentWizardStep === 2"
                            class="space-y-4"
                        >
                            <header class="mb-4">
                                <h3
                                    class="font-display text-lg font-bold tracking-tight text-content"
                                >
                                    Your Details
                                </h3>
                                <p class="text-xs text-content-muted">
                                    Please provide your contact information to
                                    reserve the court.
                                </p>
                            </header>

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
                                        class="w-full rounded-xl border border-line bg-surface-elevated/40 px-4 py-2.5 text-sm outline-none focus:border-brand focus:ring-1 focus:ring-brand"
                                        :class="{
                                            'border-destructive focus:border-destructive':
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
                                        class="w-full rounded-xl border border-line bg-surface-elevated/40 px-4 py-2.5 text-sm outline-none focus:border-brand focus:ring-1 focus:ring-brand"
                                        :class="{
                                            'border-destructive focus:border-destructive':
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
                                    class="w-full rounded-xl border border-line bg-surface-elevated/40 px-4 py-2.5 text-sm outline-none focus:border-brand focus:ring-1 focus:ring-brand"
                                    :class="{
                                        'border-destructive focus:border-destructive':
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

                            <div>
                                <label
                                    for="booking-notes"
                                    class="mb-1.5 block text-xs font-bold text-content-muted uppercase"
                                    >Special Requests (Optional)</label
                                >
                                <textarea
                                    id="booking-notes"
                                    v-model="form.notes"
                                    rows="2"
                                    placeholder="Any equipment rental needs, paddle requests, etc."
                                    class="w-full rounded-xl border border-line bg-surface-elevated/40 px-4 py-2.5 text-sm outline-none focus:border-brand focus:ring-1 focus:ring-brand"
                                ></textarea>
                            </div>

                            <div
                                class="mt-6 flex items-center justify-between border-t border-line pt-4"
                            >
                                <button
                                    type="button"
                                    class="rounded-full border border-line px-5 py-2.5 text-sm font-semibold text-content-muted transition-colors hover:bg-surface-elevated cursor-pointer"
                                    @click="prevStep"
                                >
                                    Back
                                </button>
                                <button
                                    type="button"
                                    class="rounded-full bg-brand px-6 py-2.5 text-sm font-bold text-brand-foreground shadow-lg shadow-brand/20 transition-all hover:-translate-y-0.5 hover:bg-brand/95 cursor-pointer"
                                    @click="nextStep"
                                >
                                    Continue to Payment
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Payment Upload -->
                        <div
                            v-else-if="currentWizardStep === 3"
                            class="space-y-4"
                        >
                            <header class="mb-4">
                                <h3
                                    class="font-display text-lg font-bold tracking-tight text-content"
                                >
                                    Confirm & Pay
                                </h3>
                                <p class="text-xs text-content-muted">
                                    Upload proof of payment to finalize your
                                    court reservation.
                                </p>
                            </header>

                            <!-- Reservation Summary Card -->
                            <div
                                class="rounded-xl border border-line bg-surface-elevated/40 p-4"
                            >
                                <h4
                                    class="text-xs font-bold tracking-wider text-content-muted uppercase"
                                >
                                    Reservation Summary
                                </h4>
                                <div class="mt-2 space-y-1.5 text-xs">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-content-muted"
                                            >Location / Venue:</span
                                        >
                                        <span class="font-bold text-content">{{
                                            activeVenue ? activeVenue.name : 'Main Yard'
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-content-muted"
                                            >Selected Court:</span
                                        >
                                        <span class="font-bold text-content">{{
                                            selectedCourt ? selectedCourt.name : 'N/A'
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-content-muted"
                                            >Date:</span
                                        >
                                        <span class="font-bold text-content">{{
                                            form.date
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-content-muted"
                                            >Time Slots:</span
                                        >
                                        <span class="font-bold text-content">{{
                                            form.time.join(', ')
                                        }}</span>
                                    </div>
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <span class="text-content-muted"
                                            >Duration:</span
                                        >
                                        <span class="font-bold text-content"
                                            >{{
                                                calculatedDuration
                                            }}
                                            mins</span
                                        >
                                    </div>
                                    <div
                                        class="mt-2 flex items-center justify-between border-t border-line pt-2 text-sm font-black"
                                    >
                                        <span class="text-content"
                                            >Total Payable:</span
                                        >
                                        <span class="text-brand"
                                            >${{ calculatedPrice }}</span
                                        >
                                    </div>
                                </div>
                            </div>

                            <!-- Mock QR Code Payment Instructions -->
                            <div
                                class="flex items-center gap-4 rounded-xl border border-line bg-surface-elevated/20 p-3"
                            >
                                <div
                                    class="flex size-16 shrink-0 items-center justify-center rounded-lg border border-line bg-white p-1 shadow-sm"
                                >
                                    <!-- Embedded SVG QR Code Mock Visual -->
                                    <svg
                                        class="size-full text-black"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                    >
                                        <path
                                            d="M2 2h8v8H2V2zm2 2v4h4V4H4zm11-2h7v7h-7V2zm2 2v3h3V4h-3zM2 14h8v8H2v-8zm2 2v4h4v-4H4zm13-2h3v3h-3v-3zm0 5h5v3h-5v-3zm-5-3h3v8h-3v-8zm5-2h3v2h-3v-2z"
                                        />
                                    </svg>
                                </div>
                                <div class="text-xs">
                                    <h5 class="font-bold text-content">
                                        Scan QR or Send Payment
                                    </h5>
                                    <p class="mt-0.5 text-content-muted">
                                        Transfer
                                        <strong class="text-content"
                                            >${{ calculatedPrice }}</strong
                                        >
                                        to
                                        <code class="font-mono text-brand"
                                            >pay@dinkyard.test</code
                                        >
                                        (Venmo / Zelle / GPay)
                                    </p>
                                </div>
                            </div>

                            <!-- File Upload Field -->
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-bold text-content-muted uppercase"
                                    >Upload Receipt Image *</label
                                >

                                <div
                                    v-if="!receiptPreviewUrl"
                                    class="relative flex flex-col items-center justify-center rounded-xl border-2 border-dashed border-line bg-surface-elevated/30 p-6 text-center transition-colors hover:border-brand/50 hover:bg-surface-elevated/50 cursor-pointer"
                                >
                                    <input
                                        type="file"
                                        accept="image/*"
                                        @change="handleReceiptUpload"
                                        class="absolute inset-0 cursor-pointer opacity-0"
                                    />
                                    <svg
                                        class="size-8 text-content-muted"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="1.5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                        />
                                    </svg>
                                    <p
                                        class="mt-2 text-xs font-semibold text-content"
                                    >
                                        Click or drop receipt screenshot here
                                    </p>
                                    <p class="mt-1 text-[10px] text-content-muted">
                                        PNG, JPG, or WEBP up to 5MB
                                    </p>
                                </div>

                                <div
                                    v-else
                                    class="relative flex items-center gap-3 rounded-xl border border-line bg-surface-elevated p-3"
                                >
                                    <img
                                        :src="receiptPreviewUrl"
                                        alt="Receipt preview"
                                        class="size-14 rounded-lg object-cover border border-line"
                                    />
                                    <div class="flex-1 overflow-hidden">
                                        <p
                                            class="truncate text-xs font-bold text-content"
                                        >
                                            {{ receiptFile?.name }}
                                        </p>
                                        <p class="text-[10px] text-content-muted">
                                            {{
                                                (
                                                    (receiptFile?.size || 0) /
                                                    1024
                                                ).toFixed(1)
                                            }}
                                            KB • Attached
                                        </p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeReceipt"
                                        class="rounded-full border border-line p-1.5 text-content-muted hover:text-destructive transition-colors cursor-pointer"
                                    >
                                        <svg
                                            class="size-4"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            stroke-width="2"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>

                                <p
                                    v-if="receiptError"
                                    class="mt-1.5 text-xs font-semibold text-destructive"
                                >
                                    {{ receiptError }}
                                </p>
                            </div>

                            <div
                                class="mt-6 flex items-center justify-between border-t border-line pt-4"
                            >
                                <button
                                    type="button"
                                    class="rounded-full border border-line px-5 py-2.5 text-sm font-semibold text-content-muted transition-colors hover:bg-surface-elevated cursor-pointer"
                                    @click="prevStep"
                                >
                                    Back
                                </button>
                                <button
                                    type="submit"
                                    class="rounded-full bg-brand px-6 py-2.5 text-sm font-bold text-brand-foreground shadow-lg shadow-brand/20 transition-all hover:-translate-y-0.5 hover:bg-brand/95 cursor-pointer"
                                >
                                    Submit Reservation
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Stage 2: Submitting Simulated Process Screen -->
                <div
                    v-else-if="step === 'submitting'"
                    class="p-10 text-center space-y-6"
                >
                    <div class="mx-auto flex size-20 items-center justify-center rounded-full bg-brand/10 text-brand">
                        <svg class="size-10 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-display text-xl font-black text-content">Processing Booking</h3>
                        <p class="mt-2 text-sm text-content-muted animate-pulse">{{ loadingText }}</p>
                    </div>
                </div>

                <!-- Stage 3: Confirmed Voucher Screen -->
                <div v-else-if="step === 'confirmed'" class="p-8 text-center space-y-6">
                    <div class="mx-auto flex size-16 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-500">
                        <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <div>
                        <span class="inline-block rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-bold text-emerald-500 uppercase tracking-wider">
                            Booking Confirmed
                        </span>
                        <h3 class="mt-3 font-display text-2xl font-black text-content">Court Reserved Successfully!</h3>
                        <p class="mt-1 text-sm text-content-muted">
                            Ref: <strong class="font-mono text-content">{{ bookingDetails?.reference_code }}</strong>
                        </p>
                    </div>

                    <div class="rounded-xl border border-line bg-surface-elevated/50 p-4 text-left text-xs space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-content-muted">Venue:</span>
                            <span class="font-bold text-content">{{ activeVenue ? activeVenue.name : 'Facility' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-content-muted">Court:</span>
                            <span class="font-bold text-content">{{ selectedCourt?.name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-content-muted">Date:</span>
                            <span class="font-bold text-content">{{ bookingDetails?.date || form.date }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-content-muted">Slots:</span>
                            <span class="font-bold text-content">{{ bookingDetails?.time_slots ? bookingDetails.time_slots.join(', ') : form.time.join(', ') }}</span>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-2">
                        <button
                            type="button"
                            @click="downloadVoucher"
                            class="flex-1 rounded-full border border-line bg-surface-elevated py-3 text-xs font-bold text-content hover:bg-surface transition-colors cursor-pointer"
                        >
                            Download Voucher TXT
                        </button>
                        <button
                            type="button"
                            @click="close"
                            class="flex-1 rounded-full bg-brand py-3 text-xs font-bold text-brand-foreground shadow-md hover:bg-brand/95 transition-colors cursor-pointer"
                        >
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </transition>
</template>
