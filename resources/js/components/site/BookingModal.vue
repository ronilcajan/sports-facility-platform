<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { useSite } from '@/composables/useSite';
import type { PublicCourt } from '@/types';
import type { CatalogVenue } from '@/components/site/SiteVenueCard.vue';
import { getMergedTimeSlots } from '@/utils/timeSlots';
import { useCourtAvailability } from '@/composables/useCourtAvailability';

const props = defineProps<{
    court?: PublicCourt | null;
    venue?: CatalogVenue | null;
    venues?: CatalogVenue[];
    isOpen: boolean;
    initialDate?: string | null;
    initialCourtId?: number | null;
    initialSlots?: string[];
}>();

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const site = useSite();
const siteLogo = computed(() => site.value?.logo || '/logo.jpg');
const siteName = computed(() => site.value?.name || 'Sports Facility');

const page = usePage();
const currentUser = computed(
    () => page.props.auth?.user as { name: string; email: string } | undefined,
);

// Selection State
const selectedVenueId = ref<number | null>(null);
const selectedCourtId = ref<number | null>(null);
const { fetchAvailability: loadAvailability, slotsForCourt } =
    useCourtAvailability();

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

// Only offer the venue picker when the modal was opened without a specific venue/court.
// When a venue is already chosen, its name goes in the header and we jump straight to courts.
const showVenuePicker = computed<boolean>(() => {
    return !props.venue && !props.court && !!props.venues && props.venues.length > 0;
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
    transaction_code: '',
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
const submissionError = ref<string | null>(null);

const bookingDetails = ref<{
    id: number;
    reference_code: string;
    name: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_url: string;
    qr_code: string;
} | null>(null);

// Step of the Booking Flow: 'form' | 'submitting' | 'confirmed'
const step = ref<'form' | 'submitting' | 'confirmed'>('form');
const currentWizardStep = ref(1);

// Header title/subtitle shown in the fixed modal header per wizard step
const wizardHeading = computed<{ title: string; subtitle: string }>(() => {
    if (currentWizardStep.value === 1) {
        return {
            title: 'Book a Court',
            subtitle: 'Pick your date, court, and preferred time slots.',
        };
    }
    if (currentWizardStep.value === 2) {
        return {
            title: 'Your Details',
            subtitle: 'Provide your contact information to reserve the court.',
        };
    }
    return {
        title: 'Review & Confirm',
        subtitle: 'Check your reservation, then confirm your booking.',
    };
});

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

// Available Time Slots (Default open hours + custom admin created slots)
const availableTimeSlots = computed(() => {
    return getMergedTimeSlots(selectedCourt.value?.slot_prices);
});

// Parse a slot label like '07:00 AM' into a 24-hour number (0-23)
function parseSlotHour(slot: string): number {
    const [time, period] = slot.split(' ');
    let hour = parseInt(time.split(':')[0], 10);
    if (period === 'PM' && hour !== 12) {
        hour += 12;
    }
    if (period === 'AM' && hour === 12) {
        hour = 0;
    }
    return hour;
}

// Time-of-day period label used to group slots into sections
function slotPeriodLabel(slot: string): string {
    const h = parseSlotHour(slot);
    if (h >= 5 && h < 12) {
        return 'Morning';
    }
    if (h >= 12 && h < 17) {
        return 'Afternoon';
    }
    if (h >= 17 && h < 21) {
        return 'Evening';
    }
    return 'Night';
}

// Time slots grouped under period headers, in chronological period order
const groupedTimeSlots = computed<{ period: string; slots: string[] }[]>(() => {
    const order = ['Morning', 'Afternoon', 'Evening', 'Night'];
    const groups: Record<string, string[]> = {};
    for (const slot of availableTimeSlots.value) {
        const period = slotPeriodLabel(slot);
        (groups[period] ??= []).push(slot);
    }
    return order
        .filter((period) => groups[period]?.length)
        .map((period) => ({ period, slots: groups[period] }));
});

// Calculated Duration based on selected checkmarked slots
const calculatedDuration = computed(() => {
    if (!selectedCourt.value) {
        return 0;
    }
    return form.value.time.length * (selectedCourt.value.slot_duration_minutes || 60);
});

// Duration expressed in hours, e.g. "2 hours" or "1.5 hours"
const calculatedDurationLabel = computed<string>(() => {
    const hours = calculatedDuration.value / 60;
    const rounded = Number.isInteger(hours) ? hours : parseFloat(hours.toFixed(1));
    return `${rounded} ${rounded === 1 ? 'hour' : 'hours'}`;
});

// Get price for a specific time slot (custom override or fallback to base_price)
function getSlotPriceForCourt(slot: string): number {
    if (!selectedCourt.value) return 0;
    const customPrices = selectedCourt.value.slot_prices;
    if (customPrices && customPrices[slot] !== undefined && customPrices[slot] !== null) {
        const val = parseFloat(String(customPrices[slot]));
        if (!isNaN(val) && val > 0) {
            return val;
        }
    }
    const base = parseFloat(selectedCourt.value.base_price || '0');
    return isNaN(base) ? 0 : base;
}

// Calculated Price based on selected slots & dynamic slot pricing
const calculatedPrice = computed(() => {
    if (!selectedCourt.value || form.value.time.length === 0) {
        return '0.00';
    }
    const total = form.value.time.reduce((sum, slot) => sum + getSlotPriceForCourt(slot), 0);
    return total.toFixed(2);
});

// Collapsible reservation summary on the final step (collapsed by default; total stays visible)
const summaryExpanded = ref(false);

// Enlarged (zoomed) payment QR overlay
const qrZoomed = ref(false);

// Payment methods configured on the active venue (GCash / Maya), shown as selectable options
const availablePaymentMethods = computed<{ key: 'gcash' | 'maya'; label: string; number: string; qr_url?: string | null }[]>(() => {
    const pm = activeVenue.value?.payment_methods;
    if (!pm) {
        return [];
    }
    const list: { key: 'gcash' | 'maya'; label: string; number: string; qr_url?: string | null }[] = [];
    if (pm.gcash) {
        list.push({ key: 'gcash', label: 'GCash', number: pm.gcash.number, qr_url: pm.gcash.qr_url });
    }
    if (pm.maya) {
        list.push({ key: 'maya', label: 'Maya', number: pm.maya.number, qr_url: pm.maya.qr_url });
    }
    return list;
});

const selectedPaymentMethod = ref<'gcash' | 'maya' | null>(null);

const activePaymentMethod = computed(() =>
    availablePaymentMethods.value.find((m) => m.key === selectedPaymentMethod.value) || null,
);

// Keep a valid method selected as the available methods change (venue switch / open)
watch(
    availablePaymentMethods,
    (methods) => {
        if (!methods.find((m) => m.key === selectedPaymentMethod.value)) {
            selectedPaymentMethod.value = methods[0]?.key ?? null;
        }
    },
    { immediate: true },
);

// Convert a Date to a local YYYY-MM-DD string (avoids UTC drift from toISOString)
function toDateKey(d: Date): string {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

// Safely parse a YYYY-MM-DD date string into a local Date object without UTC drift
function parseLocalDate(dateStr: string): Date {
    if (!dateStr) return new Date();
    const parts = dateStr.split('-').map(Number);
    if (parts.length === 3 && !isNaN(parts[0]) && !isNaN(parts[1]) && !isNaN(parts[2])) {
        return new Date(parts[0], parts[1] - 1, parts[2]);
    }
    return new Date(dateStr);
}

// Formatted selected date label with day of week (e.g. Mon, Jul 27, 2026)
const formattedSelectedDateLabel = computed(() => {
    if (!form.value.date) return '';
    const d = parseLocalDate(form.value.date);
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
});

// Local YYYY-MM-DD date string for min validation
const todayDateString = computed(() => toDateKey(new Date()));

// Day-slider state: 0 = current window (today + next 6 days), each step slides one week forward
const weekOffset = ref(0);
const slideDir = ref<'next' | 'prev'>('next');
const dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

// The 7 consecutive days shown in the slider. First cell of window 0 is today.
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

// Month + year heading above the slider (handles windows spanning two months/years)
const monthYearLabel = computed(() => {
    const days = visibleDays.value;
    const first = days[0];
    const last = days[6];
    const monthName = (d: Date) => d.toLocaleDateString('en-US', { month: 'short' });
    if (first.getMonth() === last.getMonth()) {
        return `${monthName(first)} ${first.getFullYear()}`;
    }
    if (first.getFullYear() === last.getFullYear()) {
        return `${monthName(first)} – ${monthName(last)} ${last.getFullYear()}`;
    }
    return `${monthName(first)} ${first.getFullYear()} – ${monthName(last)} ${last.getFullYear()}`;
});

// Direction-aware slide transition classes for the day grid
const dateEnterFrom = computed(() =>
    slideDir.value === 'next' ? 'opacity-0 translate-x-6' : 'opacity-0 -translate-x-6',
);
const dateLeaveTo = computed(() =>
    slideDir.value === 'next' ? 'opacity-0 -translate-x-6' : 'opacity-0 translate-x-6',
);

function selectDay(d: Date): void {
    form.value.date = toDateKey(d);
}

function isSelectedDay(d: Date): boolean {
    return toDateKey(d) === form.value.date;
}

function isToday(d: Date): boolean {
    return toDateKey(d) === todayDateString.value;
}

function prevWeek(): void {
    if (weekOffset.value > 0) {
        slideDir.value = 'prev';
        weekOffset.value--;
    }
}

function nextWeek(): void {
    slideDir.value = 'next';
    weekOffset.value++;
}

// Horizontal court slider
const courtScroller = ref<HTMLElement | null>(null);

function scrollCourts(dir: 'prev' | 'next'): void {
    const el = courtScroller.value;
    if (!el) {
        return;
    }
    el.scrollBy({ left: dir === 'next' ? 220 : -220, behavior: 'smooth' });
}

// Fetch real-time court availability from server
async function fetchRealtimeAvailability() {
    if (!form.value.date) return;
    await loadAvailability({ date: form.value.date });
}

// Compute booked slots for a specific court id
function getCourtBookedSlots(courtId: number): string[] {
    return slotsForCourt(courtId);
}

function isSlotBooked(slot: string): boolean {
    if (!selectedCourt.value) return false;
    return getCourtBookedSlots(selectedCourt.value.id).includes(slot);
}

function isCourtFullyBooked(court: PublicCourt): boolean {
    const booked = getCourtBookedSlots(court.id);
    return booked.length >= availableTimeSlots.value.length;
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

            // Set up initial venue & court selection — a specific court (if provided) wins over the venue default
            if (props.court) {
                selectedCourtId.value = props.court.id;
                if (props.court.venue) {
                    selectedVenueId.value = props.court.venue.id;
                } else if (props.venue) {
                    selectedVenueId.value = props.venue.id;
                }
            } else if (props.venue) {
                selectedVenueId.value = props.venue.id;
                if (props.venue.courts && props.venue.courts.length > 0) {
                    selectedCourtId.value = props.venue.courts[0].id;
                }
            } else if (props.venues && props.venues.length > 0) {
                selectedVenueId.value = props.venues[0].id;
                if (props.venues[0].courts && props.venues[0].courts.length > 0) {
                    selectedCourtId.value = props.venues[0].courts[0].id;
                }
            }

            if (props.initialCourtId) {
                selectedCourtId.value = props.initialCourtId;
            }

            // Sync date from props or default to today
            slideDir.value = 'next';
            const targetDateStr = props.initialDate || todayDateString.value;
            form.value.date = targetDateStr;

            // Calculate weekOffset so initialDate is highlighted in visible slider
            if (targetDateStr) {
                const targetDate = parseLocalDate(targetDateStr);
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const diffDays = Math.floor((targetDate.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
                weekOffset.value = diffDays > 0 ? Math.floor(diffDays / 7) : 0;
            } else {
                weekOffset.value = 0;
            }

            form.value.time = props.initialSlots ? [...props.initialSlots] : [];
            form.value.notes = '';
            form.value.transaction_code = '';
            summaryExpanded.value = false;
            qrZoomed.value = false;

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
        const selected = parseLocalDate(form.value.date);
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
    submissionError.value = null;
    if (!validateStep1() || !validateStep2()) {
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
    formData.append('transaction_code', form.value.transaction_code);
    if (receiptFile.value) {
        formData.append('receipt', receiptFile.value);
    }

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
            const errData = await res.json().catch(() => ({ message: 'Failed to submit booking.' }));
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
            const generalMsg = err?.message || err?.error || (typeof err === 'string' ? err : null);
            submissionError.value = generalMsg || 'Unable to submit your booking reservation. Please check your inputs and try again.';

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
                if (err.errors.court_id) {
                    errors.value.court = err.errors.court_id[0];
                    currentWizardStep.value = 1;
                }
                if (err.errors.receipt) {
                    receiptError.value = err.errors.receipt[0];
                    currentWizardStep.value = 3;
                }
            }
        });
}

function loadImage(src: string): Promise<HTMLImageElement> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = src;
    });
}

// Resolve the current theme's brand color so the voucher matches the active theme
function getBrandColor(): string {
    if (typeof document === 'undefined') {
        return '#0f766e';
    }
    const el = document.createElement('span');
    el.className = 'text-brand';
    el.style.cssText = 'position:absolute;opacity:0;pointer-events:none';
    document.body.appendChild(el);
    const color = getComputedStyle(el).color;
    document.body.removeChild(el);
    return color || '#0f766e';
}

// Render the booking as a downloadable PNG receipt with a scannable verification QR
async function downloadVoucher() {
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
    const durationMinutes = bookingDetails.value
        ? bookingDetails.value.time_slots.length *
          (selectedCourt.value?.slot_duration_minutes || 60)
        : calculatedDuration.value;
    const durationHours = durationMinutes / 60;
    const duration = `${Number.isInteger(durationHours) ? durationHours : durationHours.toFixed(1)} ${durationHours === 1 ? 'hour' : 'hours'}`;
    const price = bookingDetails.value
        ? bookingDetails.value.total_price
        : calculatedPrice.value;
    const qrSrc = bookingDetails.value?.qr_code || null;

    const brand = getBrandColor();
    const scale = 2;
    const W = 640;
    const H = 960;
    const canvas = document.createElement('canvas');
    canvas.width = W * scale;
    canvas.height = H * scale;
    const ctx = canvas.getContext('2d');
    if (!ctx) {
        return;
    }
    ctx.scale(scale, scale);

    // Card background
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, W, H);

    // Header band with logo
    ctx.fillStyle = brand;
    const headerHeight = 120;
    ctx.fillRect(0, 0, W, headerHeight);

    const logoUrl = siteLogo.value;
    try {
        const logoImg = await loadImage(logoUrl);
        const logoSize = 44;
        const logoX = (W - logoSize) / 2;
        const logoY = 12;

        // Draw white circle background ring for logo
        ctx.save();
        ctx.beginPath();
        ctx.arc(logoX + logoSize / 2, logoY + logoSize / 2, logoSize / 2 + 2, 0, Math.PI * 2);
        ctx.fillStyle = '#ffffff';
        ctx.fill();

        // Clip logo inside circle
        ctx.beginPath();
        ctx.arc(logoX + logoSize / 2, logoY + logoSize / 2, logoSize / 2, 0, Math.PI * 2);
        ctx.clip();
        ctx.drawImage(logoImg, logoX, logoY, logoSize, logoSize);
        ctx.restore();

        ctx.fillStyle = '#ffffff';
        ctx.textAlign = 'center';
        ctx.font = '700 20px Arial, sans-serif';
        ctx.fillText('COURT RESERVATION', W / 2, logoY + logoSize + 22);
        ctx.font = '400 13px Arial, sans-serif';
        ctx.fillText(venueName || siteName.value, W / 2, logoY + logoSize + 40);
    } catch {
        ctx.fillStyle = '#ffffff';
        ctx.textAlign = 'center';
        ctx.font = '700 24px Arial, sans-serif';
        ctx.fillText('COURT RESERVATION', W / 2, 48);
        ctx.font = '400 14px Arial, sans-serif';
        ctx.fillText(venueName || siteName.value, W / 2, 78);
    }

    // Reference
    ctx.fillStyle = '#111827';
    ctx.font = '700 13px monospace';
    ctx.fillText(`REF: ${reference}`, W / 2, 148);

    // QR code
    let y = 176;
    if (qrSrc) {
        try {
            const qrImg = await loadImage(qrSrc);
            const qrSize = 196;
            const boxX = (W - qrSize) / 2 - 10;
            ctx.fillStyle = '#ffffff';
            ctx.fillRect(boxX, y - 10, qrSize + 20, qrSize + 20);
            ctx.strokeStyle = '#e5e7eb';
            ctx.lineWidth = 1;
            ctx.strokeRect(boxX, y - 10, qrSize + 20, qrSize + 20);
            ctx.drawImage(qrImg, (W - qrSize) / 2, y, qrSize, qrSize);
            y += qrSize + 22;
        } catch {
            // QR failed to load — continue without it
        }
    }
    ctx.fillStyle = '#6b7280';
    ctx.textAlign = 'center';
    ctx.font = '400 12px Arial, sans-serif';
    ctx.fillText('Show this QR to staff to verify your booking', W / 2, y);
    y += 34;

    // Detail rows
    const padX = 64;
    const rows: [string, string][] = [
        ['Court', courtName],
        ['Player', playerName],
        ['Date', date],
        ['Time', times],
        ['Duration', duration],
    ];
    for (const [label, value] of rows) {
        ctx.textAlign = 'left';
        ctx.fillStyle = '#6b7280';
        ctx.font = '400 14px Arial, sans-serif';
        ctx.fillText(label, padX, y);
        ctx.textAlign = 'right';
        ctx.fillStyle = '#111827';
        ctx.font = '700 14px Arial, sans-serif';
        ctx.fillText(value, W - padX, y);
        y += 30;
    }

    // Total
    y += 4;
    ctx.strokeStyle = '#e5e7eb';
    ctx.lineWidth = 1;
    ctx.beginPath();
    ctx.moveTo(padX, y);
    ctx.lineTo(W - padX, y);
    ctx.stroke();
    y += 32;
    ctx.textAlign = 'left';
    ctx.fillStyle = '#111827';
    ctx.font = '700 16px Arial, sans-serif';
    ctx.fillText('Total', padX, y);
    ctx.textAlign = 'right';
    ctx.fillStyle = brand;
    ctx.font = '800 20px Arial, sans-serif';
    ctx.fillText(`PHP ${price}`, W - padX, y);
    y += 40;

    // Pending / cancellation disclaimer
    ctx.textAlign = 'center';
    ctx.fillStyle = '#9ca3af';
    ctx.font = '400 11px Arial, sans-serif';
    const disclaimer = [
        'This booking is PENDING and not yet guaranteed.',
        'Staff may cancel it at any time. You will receive a confirmation',
        'email, or log in to track your booking status anytime.',
    ];
    for (const line of disclaimer) {
        ctx.fillText(line, W / 2, y);
        y += 16;
    }

    // Download
    const link = document.createElement('a');
    link.href = canvas.toDataURL('image/png');
    link.download = `Booking-${reference}.png`;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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
                class="relative flex max-h-[90vh] w-full max-w-lg scale-100 transform flex-col overflow-hidden rounded-2xl border border-line bg-surface text-content opacity-100 shadow-2xl transition-all duration-300"
                @click.stop
            >
                <!-- Close Button (confirmed stage only; the form stage has its own header close button) -->
                <button
                    v-if="step === 'confirmed'"
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

                <!-- Enlarged QR overlay -->
                <div
                    v-if="qrZoomed && activePaymentMethod?.qr_url"
                    class="absolute inset-0 z-30 flex flex-col items-center justify-center gap-4 bg-surface-inverse/85 p-6 backdrop-blur-sm"
                    @click="qrZoomed = false"
                >
                    <div class="max-h-[70vh] max-w-full overflow-hidden rounded-2xl border border-line bg-white p-3 shadow-2xl" @click.stop>
                        <img
                            :src="activePaymentMethod.qr_url"
                            alt="Payment QR code"
                            class="mx-auto h-auto max-h-[64vh] w-auto max-w-full object-contain"
                        />
                    </div>
                    <div class="text-center">
                        <p class="text-sm font-bold text-white">Send {{ activePaymentMethod.label }} to</p>
                        <code class="font-mono text-base font-bold text-white">{{ activePaymentMethod.number }}</code>
                        <p class="mt-0.5 text-xs text-white/70">Amount: ₱{{ calculatedPrice }}</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-full bg-white px-5 py-2 text-xs font-bold text-surface-inverse transition-colors hover:bg-white/90 cursor-pointer"
                        @click="qrZoomed = false"
                    >
                        Close
                    </button>
                </div>

                <!-- Stage 1: Booking Wizard -->
                <div v-if="step === 'form'" class="flex min-h-0 flex-1 flex-col">
                    <!-- Fixed Header -->
                    <div class="shrink-0 border-b border-line px-6 pt-5 pb-4 sm:px-8">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 pr-1">
                                <h2 class="font-display text-lg font-bold tracking-tight text-content">
                                    {{ wizardHeading.title }}
                                </h2>
                                <p class="mt-0.5 text-xs text-content-muted">
                                    {{ wizardHeading.subtitle }}
                                </p>
                            </div>
                            <button
                                type="button"
                                class="flex size-9 shrink-0 items-center justify-center rounded-full border border-line bg-surface-elevated/50 text-content transition-colors hover:bg-surface-elevated hover:text-brand cursor-pointer"
                                aria-label="Close booking modal"
                                @click="close"
                            >
                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        <!-- Step tracker -->
                        <div class="mt-4 flex items-center justify-between">
                            <span
                                class="text-xs font-bold tracking-[0.2em] text-brand uppercase"
                                >Step {{ currentWizardStep }} of 3</span
                            >
                            <span class="text-xs font-semibold text-content-muted">
                                {{
                                    currentWizardStep === 1
                                        ? 'Court & Schedule'
                                        : currentWizardStep === 2
                                          ? 'User Details'
                                          : 'Payment Details'
                                }}
                            </span>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
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

                    <form @submit.prevent="handleSubmit" class="flex min-h-0 flex-1 flex-col">
                        <!-- Scrollable Body -->
                        <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-6 py-5 sm:px-8">
                            <!-- Global / Server Submission Error Alert Banner -->
                            <div
                                v-if="submissionError"
                                class="flex items-start justify-between gap-3 rounded-xl border border-destructive/30 bg-destructive/10 p-3.5 text-xs text-destructive shadow-sm"
                            >
                                <div class="flex items-start gap-2.5">
                                    <svg class="mt-0.5 size-4 shrink-0 text-destructive" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                    <div>
                                        <strong class="block font-bold text-sm">Booking Error</strong>
                                        <p class="mt-0.5 leading-relaxed font-semibold">{{ submissionError }}</p>
                                    </div>
                                </div>
                                <button
                                    type="button"
                                    @click="submissionError = null"
                                    class="rounded p-1 font-bold text-destructive hover:bg-destructive/10 cursor-pointer"
                                    aria-label="Dismiss error"
                                >
                                    ✕
                                </button>
                            </div>

                        <!-- Step 1: Venue & Court Selection -->
                        <div v-if="currentWizardStep === 1" class="space-y-4">
                            <!-- Venue banner shown when a venue is already chosen -->
                            <div
                                v-if="activeVenue && !showVenuePicker"
                                class="flex items-center gap-3 rounded-xl border border-line bg-surface-elevated/40 p-2.5"
                            >
                                <div class="relative size-12 shrink-0 overflow-hidden rounded-lg bg-surface-inverse">
                                    <img
                                        :src="activeVenue.cover_image_url || '/images/hero_pickleball.png'"
                                        :alt="activeVenue.name"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <div class="min-w-0">
                                    <span class="block text-[9px] font-bold uppercase tracking-[0.2em] text-brand">Booking at</span>
                                    <h3 class="truncate font-display text-base font-bold tracking-tight text-content">
                                        {{ activeVenue.name }}
                                    </h3>
                                    <p v-if="activeVenue.address" class="truncate text-[11px] text-content-muted">
                                        {{ activeVenue.address }}
                                    </p>
                                </div>
                            </div>

                            <!-- 1. Venue Selection (Image-Based Cards) -->
                            <div v-if="showVenuePicker" class="space-y-2">
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

                            <!-- 2. Date Selection: 7-day slider -->
                            <div>
                                <div class="mb-2 flex items-center justify-between">
                                    <label
                                        class="block text-xs font-bold text-content-muted uppercase tracking-wider"
                                        >Booking Date</label
                                    >
                                    <div class="flex items-center gap-2.5">
                                        <span class="text-sm font-bold text-content">{{ monthYearLabel }}</span>
                                        <div class="flex items-center gap-1">
                                            <button
                                                type="button"
                                                @click="prevWeek"
                                                :disabled="weekOffset === 0"
                                                aria-label="Previous days"
                                                class="flex size-7 items-center justify-center rounded-full border border-line text-content-muted transition-colors hover:bg-surface-elevated hover:text-brand disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:bg-transparent disabled:hover:text-content-muted cursor-pointer"
                                            >
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                                </svg>
                                            </button>
                                            <button
                                                type="button"
                                                @click="nextWeek"
                                                aria-label="Next days"
                                                class="flex size-7 items-center justify-center rounded-full border border-line text-content-muted transition-colors hover:bg-surface-elevated hover:text-brand cursor-pointer"
                                            >
                                                <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="overflow-hidden">
                                    <transition
                                        :enter-active-class="'transition duration-200 ease-out'"
                                        :enter-from-class="dateEnterFrom"
                                        enter-to-class="opacity-100 translate-x-0"
                                        :leave-active-class="'transition duration-150 ease-in'"
                                        leave-from-class="opacity-100 translate-x-0"
                                        :leave-to-class="dateLeaveTo"
                                        mode="out-in"
                                    >
                                        <div :key="weekOffset" class="grid grid-cols-7 gap-1.5">
                                            <button
                                                v-for="d in visibleDays"
                                                :key="toDateKey(d)"
                                                type="button"
                                                @click="selectDay(d)"
                                                class="group flex flex-col items-center gap-1.5 rounded-xl border py-2 transition-all cursor-pointer"
                                                :class="isSelectedDay(d)
                                                    ? 'border-brand bg-brand/5'
                                                    : 'border-transparent hover:bg-surface-elevated/50'"
                                            >
                                                <span
                                                    class="text-[10px] font-bold uppercase tracking-wide"
                                                    :class="isSelectedDay(d) ? 'text-brand' : 'text-content-muted'"
                                                    >{{ dayLabels[d.getDay()] }}</span
                                                >
                                                <span
                                                    class="flex size-9 items-center justify-center rounded-full text-sm font-bold transition-all"
                                                    :class="isSelectedDay(d)
                                                        ? 'bg-brand text-brand-foreground shadow-md shadow-brand/30'
                                                        : 'bg-surface-elevated/60 text-content group-hover:bg-surface-elevated'"
                                                    >{{ d.getDate() }}</span
                                                >
                                                <span
                                                    class="text-[8px] font-bold uppercase tracking-wide"
                                                    :class="isSelectedDay(d) ? 'text-brand' : 'text-content-muted/70'"
                                                    >{{ isToday(d) ? 'Today' : ' ' }}</span
                                                >
                                            </button>
                                        </div>
                                    </transition>
                                </div>
                                <p
                                    v-if="errors.date"
                                    class="mt-1 text-xs font-semibold text-destructive"
                                >
                                    {{ errors.date }}
                                </p>
                            </div>

                            <!-- 3. Court Selection (Horizontal Slider) -->
                            <div>
                                <div class="mb-1.5 flex items-center justify-between">
                                    <label
                                        class="block text-xs font-bold text-content-muted uppercase tracking-wider"
                                        >Choose Available Court</label
                                    >
                                    <div v-if="sortedAvailableCourts.length > 2" class="flex items-center gap-1">
                                        <button
                                            type="button"
                                            @click="scrollCourts('prev')"
                                            aria-label="Previous courts"
                                            class="flex size-7 items-center justify-center rounded-full border border-line text-content-muted transition-colors hover:bg-surface-elevated hover:text-brand cursor-pointer"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click="scrollCourts('next')"
                                            aria-label="Next courts"
                                            class="flex size-7 items-center justify-center rounded-full border border-line text-content-muted transition-colors hover:bg-surface-elevated hover:text-brand cursor-pointer"
                                        >
                                            <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div
                                    ref="courtScroller"
                                    class="flex snap-x snap-mandatory gap-2 overflow-x-auto pb-1 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                                >
                                    <div
                                        v-for="c in sortedAvailableCourts"
                                        :key="c.id"
                                        @click="!isCourtFullyBooked(c) && (selectedCourtId = c.id)"
                                        class="relative flex w-auto min-w-20 shrink-0 snap-start flex-col items-center gap-0.5 rounded-xl border px-4 py-2.5 text-center transition-all select-none"
                                        :class="[
                                            isCourtFullyBooked(c)
                                                ? 'cursor-not-allowed border-line bg-surface/30 opacity-50'
                                                : selectedCourtId === c.id
                                                  ? 'cursor-pointer border-brand bg-brand/5 shadow-sm shadow-brand/10 ring-1 ring-brand'
                                                  : 'cursor-pointer border-line bg-surface-elevated/40 hover:border-brand/40 hover:bg-surface-elevated',
                                        ]"
                                    >
                                        <h4 class="text-sm font-extrabold whitespace-nowrap text-content">
                                            {{ c.name }}
                                        </h4>
                                        <span class="text-sm font-extrabold text-brand">₱{{ c.base_price }}</span>
                                        <span v-if="isCourtFullyBooked(c)" class="text-[10px] font-bold text-destructive uppercase">
                                            Fully Booked
                                        </span>
                                    </div>
                                </div>
                                <p v-if="errors.court" class="mt-1 text-xs font-semibold text-destructive">
                                    {{ errors.court }}
                                </p>
                            </div>

                            <!-- Time Slots Grid -->
                            <div>
                                <label
                                    class="mb-1.5 block text-xs font-bold text-content-muted uppercase tracking-wider"
                                    >Select Time Slots</label
                                >
                                <div class="space-y-3">
                                    <div
                                        v-for="group in groupedTimeSlots"
                                        :key="group.period"
                                    >
                                        <h5 class="mb-1.5 px-0.5 text-[10px] font-bold uppercase tracking-[0.2em] text-content-muted">
                                            {{ group.period }}
                                        </h5>
                                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
                                            <label
                                                v-for="slot in group.slots"
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
                                                <span v-if="selectedCourt" class="text-[9px] font-extrabold text-brand">₱{{ getSlotPriceForCourt(slot) }}</span>
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
                                    </div>
                                </div>
                                <p
                                    v-if="errors.time"
                                    class="mt-1 text-xs font-semibold text-destructive"
                                >
                                    {{ errors.time }}
                                </p>
                            </div>

                        </div>

                        <!-- Step 2: User Details -->
                        <div
                            v-else-if="currentWizardStep === 2"
                            class="space-y-4"
                        >
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
                                        type="text"
                                        v-model="form.phone"
                                        placeholder="0917 123 4567"
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
                                    placeholder="juan.delacruz@email.com"
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

                        </div>

                        <!-- Step 3: Payment Upload -->
                        <div
                            v-else-if="currentWizardStep === 3"
                            class="space-y-4"
                        >
                            <!-- Collapsible Reservation Summary — total always visible -->
                            <div class="rounded-xl border border-line bg-surface-elevated/40 p-4">
                                <button
                                    type="button"
                                    class="flex w-full items-center justify-between text-left cursor-pointer"
                                    @click="summaryExpanded = !summaryExpanded"
                                >
                                    <div>
                                        <span class="block text-[10px] font-bold uppercase tracking-wider text-content-muted">Total Payable</span>
                                        <span class="text-xl font-black text-brand">₱{{ calculatedPrice }}</span>
                                    </div>
                                    <span class="flex items-center gap-1 text-xs font-semibold text-content-muted">
                                        {{ summaryExpanded ? 'Hide' : 'View details' }}
                                        <svg
                                            class="size-4 transition-transform"
                                            :class="{ 'rotate-180': summaryExpanded }"
                                            fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"
                                        >
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </span>
                                </button>

                                <div v-if="summaryExpanded" class="mt-3 space-y-1.5 border-t border-line pt-3 text-xs">
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-content-muted">Venue</span>
                                        <span class="truncate font-bold text-content">{{ activeVenue ? activeVenue.name : 'Main Yard' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-content-muted">Court</span>
                                        <span class="truncate font-bold text-content">{{ selectedCourt ? selectedCourt.name : 'N/A' }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-content-muted">Date</span>
                                        <span class="font-bold text-content">{{ form.date }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="shrink-0 text-content-muted">Time Slots</span>
                                        <span class="truncate text-right font-bold text-content">{{ form.time.join(', ') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between gap-3">
                                        <span class="text-content-muted">Duration</span>
                                        <span class="font-bold text-content">{{ calculatedDurationLabel }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment (optional) -->
                            <div class="space-y-3 rounded-xl border border-line bg-surface-elevated/30 p-4">
                                <div class="flex items-center justify-between">
                                    <h4 class="text-xs font-bold uppercase tracking-wider text-content-muted">
                                        Payment
                                    </h4>
                                    <span class="rounded-full bg-surface-elevated px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-content-muted">
                                        Optional
                                    </span>
                                </div>

                                <!-- Payment method selector (venue-configured) -->
                                <template v-if="availablePaymentMethods.length">
                                    <div class="flex gap-2">
                                        <button
                                            v-for="m in availablePaymentMethods"
                                            :key="m.key"
                                            type="button"
                                            @click="selectedPaymentMethod = m.key"
                                            class="flex-1 rounded-lg border px-3 py-2 text-sm font-bold transition-all cursor-pointer"
                                            :class="selectedPaymentMethod === m.key
                                                ? 'border-brand bg-brand/5 text-brand ring-1 ring-brand'
                                                : 'border-line text-content-muted hover:bg-surface-elevated'"
                                        >
                                            {{ m.label }}
                                        </button>
                                    </div>

                                    <!-- Selected method: where to send + QR -->
                                    <div v-if="activePaymentMethod" class="flex items-center gap-3 rounded-lg border border-line bg-surface p-3">
                                        <button
                                            v-if="activePaymentMethod.qr_url"
                                            type="button"
                                            @click="qrZoomed = true"
                                            aria-label="Enlarge QR code"
                                            class="group relative size-20 shrink-0 overflow-hidden rounded-lg border border-line bg-white p-1 cursor-zoom-in"
                                        >
                                            <img :src="activePaymentMethod.qr_url" alt="Payment QR code" class="h-full w-full object-contain" />
                                            <span class="absolute bottom-0.5 right-0.5 flex size-5 items-center justify-center rounded-full bg-brand text-brand-foreground shadow">
                                                <svg class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 8v6M8 11h6M19 11a8 8 0 11-16 0 8 8 0 0116 0z" />
                                                </svg>
                                            </span>
                                        </button>
                                        <div class="min-w-0">
                                            <span class="block text-[10px] font-bold uppercase tracking-wider text-content-muted">
                                                Send {{ activePaymentMethod.label }} to
                                            </span>
                                            <code class="font-mono text-sm font-bold text-brand">{{ activePaymentMethod.number }}</code>
                                            <span class="mt-0.5 block text-xs text-content-muted">
                                                Amount: <strong class="text-content">₱{{ calculatedPrice }}</strong>
                                            </span>
                                        </div>
                                    </div>
                                </template>

                                <!-- No payment methods configured on this venue -->
                                <p v-else class="text-xs text-content-muted">
                                    The venue will share payment details with you after your booking is received.
                                </p>

                                <!-- Reference number -->
                                <input
                                    id="booking-transaction-code"
                                    type="text"
                                    v-model="form.transaction_code"
                                    placeholder="Reference no. (optional)"
                                    class="w-full rounded-lg border border-line bg-surface px-3.5 py-2.5 text-sm outline-none focus:border-brand focus:ring-1 focus:ring-brand"
                                />

                                <!-- Receipt upload -->
                                <div
                                    v-if="!receiptPreviewUrl"
                                    class="relative flex items-center justify-center gap-2 rounded-lg border border-dashed border-line bg-surface px-3 py-3 text-center transition-colors hover:border-brand/50 cursor-pointer"
                                >
                                    <input
                                        type="file"
                                        accept="image/*"
                                        @change="handleReceiptUpload"
                                        class="absolute inset-0 cursor-pointer opacity-0"
                                    />
                                    <svg class="size-4 shrink-0 text-content-muted" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-xs font-semibold text-content-muted">Upload receipt screenshot</span>
                                </div>

                                <div
                                    v-else
                                    class="relative flex items-center gap-3 rounded-lg border border-line bg-surface p-2.5"
                                >
                                    <img :src="receiptPreviewUrl" alt="Receipt preview" class="size-11 rounded-lg border border-line object-cover" />
                                    <div class="flex-1 overflow-hidden">
                                        <p class="truncate text-xs font-bold text-content">{{ receiptFile?.name }}</p>
                                        <p class="text-[10px] text-content-muted">{{ ((receiptFile?.size || 0) / 1024).toFixed(1) }} KB • Attached</p>
                                    </div>
                                    <button
                                        type="button"
                                        @click="removeReceipt"
                                        class="rounded-full border border-line p-1.5 text-content-muted transition-colors hover:text-destructive cursor-pointer"
                                    >
                                        <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>

                                <p v-if="receiptError" class="text-xs font-semibold text-destructive">
                                    {{ receiptError }}
                                </p>
                            </div>

                            <p class="text-center text-[11px] text-content-muted">
                                Payment isn't required to book — paying ahead just helps staff confirm faster.
                            </p>

                        </div>
                        </div>
                        <!-- end scrollable body -->

                        <!-- Fixed Footer (action bar) -->
                        <div class="shrink-0 border-t border-line bg-surface px-6 py-4 sm:px-8">
                            <!-- Step 1 footer -->
                            <div
                                v-if="currentWizardStep === 1"
                                class="flex items-center justify-between gap-3"
                            >
                                <div>
                                    <span class="block text-[9px] font-semibold text-content-muted uppercase"
                                        >Duration &amp; Estimate</span
                                    >
                                    <span class="text-xs text-content-muted"
                                        >{{ calculatedDurationLabel }} •
                                        <strong class="text-sm text-content">₱{{ calculatedPrice }}</strong></span
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

                            <!-- Step 2 footer -->
                            <div
                                v-else-if="currentWizardStep === 2"
                                class="flex items-center justify-between gap-3"
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

                            <!-- Step 3 footer -->
                            <div
                                v-else-if="currentWizardStep === 3"
                                class="flex items-center justify-between gap-3"
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
                                    Confirm Booking
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

                <!-- Stage 3: Booking Received Screen -->
                <div v-else-if="step === 'confirmed'" class="min-h-0 flex-1 space-y-5 overflow-y-auto p-8 text-center">
                    <div class="mx-auto flex items-center justify-center gap-3">
                        <img v-if="siteLogo" :src="siteLogo" :alt="siteName" class="size-14 rounded-full object-cover ring-2 ring-emerald-500/30 shadow-sm" />
                        <div class="flex size-14 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-500">
                            <svg class="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <span class="inline-block rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-bold text-emerald-500 uppercase tracking-wider">
                            Booking Received
                        </span>
                        <h3 class="mt-3 font-display text-2xl font-black text-content">Your Booking Was Submitted!</h3>
                        <p class="mt-1 text-sm text-content-muted">
                            Ref: <strong class="font-mono text-content">{{ bookingDetails?.reference_code }}</strong>
                        </p>
                    </div>

                    <!-- Verification QR -->
                    <div v-if="bookingDetails?.qr_code" class="flex flex-col items-center gap-2">
                        <div class="rounded-2xl border border-line bg-white p-3 shadow-sm">
                            <img :src="bookingDetails.qr_code" alt="Booking verification QR code" class="size-36 object-contain" />
                        </div>
                        <p class="text-[11px] text-content-muted">Show this QR to staff to verify your booking</p>
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

                    <!-- Pending / cancellation disclaimer -->
                    <div class="rounded-xl border border-amber-500/30 bg-amber-500/5 p-3 text-left">
                        <div class="flex items-start gap-2">
                            <svg class="mt-0.5 size-4 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
                            </svg>
                            <p class="text-[11px] leading-relaxed text-content">
                                Your slot is <strong>not yet guaranteed</strong> — this booking is pending review and staff may cancel it at any time. You'll get a <strong>confirmation email</strong> once it's approved, or you can <strong>log in to track your booking status</strong> anytime.
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row gap-3 pt-1">
                        <button
                            type="button"
                            @click="downloadVoucher"
                            class="flex-1 rounded-full border border-line bg-surface-elevated py-3 text-xs font-bold text-content hover:bg-surface transition-colors cursor-pointer"
                        >
                            Download Receipt
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
