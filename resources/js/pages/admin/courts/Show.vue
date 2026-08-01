<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building,
    MapPin,
    Phone,
    Mail,
    Dumbbell,
    CheckCircle,
    XCircle,
    Trash2,
    ArrowUpRight,
    FileText,
    Search,
    Eye,
    CalendarPlus,
    Calendar,
    Pencil,
    ImageIcon,
    Clock,
    Users,
    Tag,
} from '@lucide/vue';
import BookingDetailModal, { type BookingDetail } from '@/components/admin/BookingDetailModal.vue';
import CreateBookingModal from '@/components/admin/CreateBookingModal.vue';
import CourtImageManagerModal from '@/components/admin/CourtImageManagerModal.vue';
import EditCourtModal from '@/components/admin/EditCourtModal.vue';
import VenueScheduleTab from '@/components/admin/VenueScheduleTab.vue';

interface VenueInfo {
    id: number;
    name: string;
    slug: string;
    address: string | null;
    phone: string | null;
    email: string | null;
}

interface ImageItem {
    id: number;
    path: string;
    url: string;
    is_primary: boolean;
    sort_order: number;
}

interface StaffMember {
    id: number;
    name: string;
    email: string;
}

interface CourtProfile {
    id: number;
    venue_id: number | null;
    name: string;
    slug: string;
    sport_type: string;
    description: string | null;
    base_price: string;
    slot_prices: Record<string, string | number> | null;
    slot_duration_minutes: number;
    buffer_minutes: number;
    is_active: boolean;
    status: 'available' | 'maintenance' | 'closed';
    primary_image_url: string | null;
    venue: VenueInfo | null;
    images: ImageItem[];
    staff: StaffMember[];
    created_at: string;
}

interface BookingItem {
    id: number;
    reference_code: string;
    court_name: string;
    sport_type?: string;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_url?: string | null;
    status: string;
    notes?: string | null;
    created_at: string;
}

interface PaginatedBookings {
    data: BookingItem[];
    links: any[];
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    court: CourtProfile;
    bookings: PaginatedBookings;
    filters: { search?: string; status?: string };
    canDelete: boolean;
    canManageCourt: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Courts', href: '/admin/courts' },
            { title: 'Court Profile' },
        ],
    },
});

const search = ref(props.filters.search || '');
const statusFilter = ref(props.filters.status || '');
const activeTab = ref<'bookings' | 'schedule'>('bookings');

const showDetailModal = ref(false);
const selectedBooking = ref<BookingDetail | null>(null);
const isBookingModalOpen = ref(false);
const showImageModal = ref(false);
const showEditCourtModal = ref(false);
const modalInitialDate = ref<string | undefined>(undefined);
const modalInitialCourtId = ref<number | undefined>(undefined);
const modalInitialSlot = ref<string | undefined>(undefined);

function handleBookSlot(payload: { court: any; date: string; slot?: string }) {
    modalInitialCourtId.value = payload.court?.id;
    modalInitialDate.value = payload.date;
    modalInitialSlot.value = payload.slot;
    isBookingModalOpen.value = true;
}

const courtOptionsForModal = computed(() => [
    {
        id: props.court.id,
        name: props.court.name,
        base_price: props.court.base_price,
        slot_prices: props.court.slot_prices,
    },
]);

const scheduleCourts = computed(() => [
    {
        id: props.court.id,
        name: props.court.name,
        slug: props.court.slug,
        sport_type: props.court.sport_type,
        base_price: props.court.base_price,
        slot_duration_minutes: props.court.slot_duration_minutes,
        is_active: props.court.is_active,
        slot_prices: props.court.slot_prices,
    },
]);

function applyFilters() {
    router.get(`/admin/courts/${props.court.id}`, {
        search: search.value || undefined,
        status: statusFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    statusFilter.value = '';
    applyFilters();
}

function openBookingDetails(b: BookingItem) {
    selectedBooking.value = {
        id: b.id,
        reference_code: b.reference_code,
        name: b.name,
        email: b.email,
        phone: b.phone,
        court_name: b.court_name,
        sport_type: b.sport_type,
        date: b.date,
        time_slots: b.time_slots,
        total_price: b.total_price,
        receipt_url: b.receipt_url,
        status: b.status,
        notes: b.notes,
        created_at: b.created_at,
    };
    showDetailModal.value = true;
}

function updateBookingStatus(bookingId: number, newStatus: string) {
    router.patch(`/admin/bookings/${bookingId}/status`, { status: newStatus }, { preserveScroll: true });
}

function deleteBooking(bookingId: number) {
    if (confirm('Delete this booking permanently?')) {
        router.delete(`/admin/bookings/${bookingId}`, { preserveScroll: true });
    }
}

const statusBadgeClass = computed(() => {
    switch (props.court.status) {
        case 'available':
            return 'bg-emerald-500 text-white';
        case 'maintenance':
            return 'bg-amber-500 text-white';
        case 'closed':
            return 'bg-rose-500 text-white';
        default:
            return 'bg-neutral-500 text-white';
    }
});
</script>

<template>
    <Head :title="`${court.name} – Court Profile`" />

    <div class="p-6 space-y-8 w-full">
        <!-- Back link -->
        <Link href="/admin/courts" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-500 hover:text-emerald-600 transition-colors">
            <ArrowLeft class="w-3.5 h-3.5" /> Back to Courts
        </Link>

        <!-- Court Header Hero Card -->
        <div class="relative overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
            <!-- Primary Image Banner -->
            <div class="relative h-48 sm:h-64 overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                <img
                    v-if="court.primary_image_url"
                    :src="court.primary_image_url"
                    :alt="court.name"
                    class="h-full w-full object-cover"
                />
                <div v-else class="flex h-full w-full items-center justify-center text-neutral-300 dark:text-neutral-600">
                    <Dumbbell class="h-20 w-20" />
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />

                <!-- Status Badges -->
                <div class="absolute right-4 top-4 flex items-center gap-2">
                    <span :class="['rounded-full px-3 py-1 text-[11px] font-bold capitalize shadow-md', statusBadgeClass]">
                        {{ court.status }}
                    </span>
                    <span :class="['rounded-full px-3 py-1 text-[11px] font-bold shadow-md', court.is_active ? 'bg-emerald-600 text-white' : 'bg-neutral-600 text-white']">
                        {{ court.is_active ? 'Online' : 'Offline' }}
                    </span>
                </div>
            </div>

            <!-- Court Header Body -->
            <div class="p-6 sm:p-8">
                <div class="flex flex-col lg:flex-row lg:items-start justify-between gap-6">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-emerald-100 dark:bg-emerald-950/60 px-3 py-0.5 text-xs font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">
                                {{ court.sport_type }}
                            </span>
                            <span v-if="court.venue" class="inline-flex items-center gap-1 text-xs font-medium text-neutral-500">
                                <Building class="w-3.5 h-3.5 text-neutral-400" />
                                <Link :href="`/admin/venues/${court.venue.id}`" class="hover:text-emerald-600 hover:underline">
                                    {{ court.venue.name }}
                                </Link>
                            </span>
                        </div>

                        <h1 class="text-2xl sm:text-3xl font-black text-neutral-900 dark:text-white tracking-tight">{{ court.name }}</h1>
                        <p v-if="court.description" class="text-sm text-neutral-500 max-w-3xl leading-relaxed">{{ court.description }}</p>

                        <!-- Quick Pill Badges -->
                        <div class="flex flex-wrap gap-x-6 gap-y-2 pt-2 text-xs text-neutral-500">
                            <span class="inline-flex items-center gap-1.5 font-semibold text-emerald-600 dark:text-emerald-400">
                                <Tag class="w-3.5 h-3.5" /> ₱{{ court.base_price }} / hr
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <Clock class="w-3.5 h-3.5 text-neutral-400" /> {{ court.slot_duration_minutes }} min slots
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <Clock class="w-3.5 h-3.5 text-neutral-400" /> {{ court.buffer_minutes }} min buffer
                            </span>
                            <span class="inline-flex items-center gap-1.5">
                                <Users class="w-3.5 h-3.5 text-neutral-400" /> {{ court.staff.length }} staff assigned
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assigned Venue & Specifications Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Assigned Venue Card -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-neutral-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <Building class="w-4 h-4 text-emerald-600" /> Assigned Venue
                    </h3>
                    <span class="rounded-full bg-emerald-100 dark:bg-emerald-950/60 px-2 py-0.5 text-[10px] font-bold text-emerald-700 dark:text-emerald-300">
                        Facility
                    </span>
                </div>

                <div v-if="court.venue" class="space-y-3 pt-1">
                    <div>
                        <Link :href="`/admin/venues/${court.venue.id}`" class="text-lg font-bold text-neutral-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors flex items-center gap-1.5">
                            {{ court.venue.name }} <ArrowUpRight class="w-4 h-4 text-emerald-600" />
                        </Link>
                    </div>

                    <div class="space-y-2 text-xs text-neutral-500">
                        <div v-if="court.venue.address" class="flex items-start gap-2">
                            <MapPin class="w-3.5 h-3.5 text-neutral-400 shrink-0 mt-0.5" />
                            <span>{{ court.venue.address }}</span>
                        </div>
                        <div v-if="court.venue.phone" class="flex items-center gap-2">
                            <Phone class="w-3.5 h-3.5 text-neutral-400 shrink-0" />
                            <span>{{ court.venue.phone }}</span>
                        </div>
                        <div v-if="court.venue.email" class="flex items-center gap-2">
                            <Mail class="w-3.5 h-3.5 text-neutral-400 shrink-0" />
                            <span>{{ court.venue.email }}</span>
                        </div>
                    </div>
                </div>

                <div v-else class="py-4 text-xs text-neutral-400 italic">
                    No venue assigned to this court yet.
                </div>
            </div>

            <!-- Pricing & Slot Specs Card -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-neutral-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <Tag class="w-4 h-4 text-emerald-600" /> Pricing &amp; Duration
                    </h3>
                    <span class="rounded-full bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 text-[10px] font-bold text-neutral-500">
                        Rates
                    </span>
                </div>

                <div class="space-y-2 text-xs divide-y divide-neutral-100 dark:divide-neutral-800">
                    <div class="flex justify-between py-1.5">
                        <span class="text-neutral-500">Base Hourly Rate</span>
                        <span class="font-bold text-emerald-600 dark:text-emerald-400">₱{{ court.base_price }} / hr</span>
                    </div>
                    <div class="flex justify-between py-1.5">
                        <span class="text-neutral-500">Slot Duration</span>
                        <span class="font-semibold text-neutral-900 dark:text-white">{{ court.slot_duration_minutes }} minutes</span>
                    </div>
                    <div class="flex justify-between py-1.5">
                        <span class="text-neutral-500">Buffer Between Slots</span>
                        <span class="font-semibold text-neutral-900 dark:text-white">{{ court.buffer_minutes }} minutes</span>
                    </div>
                    <div class="flex justify-between py-1.5">
                        <span class="text-neutral-500">Custom Slot Rates</span>
                        <span class="font-semibold text-neutral-900 dark:text-white">
                            {{ court.slot_prices && Object.keys(court.slot_prices).length > 0 ? `${Object.keys(court.slot_prices).length} configured` : 'Default base rate' }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Assigned Staff Card -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-sm space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-bold text-neutral-900 dark:text-white uppercase tracking-wider flex items-center gap-2">
                        <Users class="w-4 h-4 text-emerald-600" /> Assigned Staff
                    </h3>
                    <span class="rounded-full bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 text-[10px] font-bold text-neutral-500">
                        {{ court.staff.length }}
                    </span>
                </div>

                <div v-if="court.staff.length" class="space-y-2 max-h-40 overflow-y-auto pr-1">
                    <div v-for="member in court.staff" :key="member.id" class="flex items-center justify-between p-2 rounded-xl bg-neutral-50 dark:bg-neutral-800/60 text-xs">
                        <span class="font-semibold text-neutral-900 dark:text-white">{{ member.name }}</span>
                        <span class="text-neutral-400 text-[11px]">{{ member.email }}</span>
                    </div>
                </div>
                <div v-else class="py-4 text-xs text-neutral-400 italic">
                    No staff members assigned to this court.
                </div>
            </div>
        </div>

        <!-- Main Tabs Section: Bookings & Availability Schedule -->
        <section class="space-y-4">
            <!-- Tab Switcher -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-1 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-100 dark:bg-neutral-900 p-1">
                    <button
                        type="button"
                        @click="activeTab = 'bookings'"
                        :class="[
                            'flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all duration-200',
                            activeTab === 'bookings'
                                ? 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white shadow-sm'
                                : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300',
                        ]"
                    >
                        <FileText class="w-3.5 h-3.5" />
                        Booking Records
                        <span class="ml-0.5 rounded-full bg-neutral-200 dark:bg-neutral-700 px-1.5 py-0.5 text-[10px] font-bold text-neutral-600 dark:text-neutral-300">
                            {{ bookings.data.length }}
                        </span>
                    </button>
                    <button
                        type="button"
                        @click="activeTab = 'schedule'"
                        :class="[
                            'flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all duration-200',
                            activeTab === 'schedule'
                                ? 'bg-white dark:bg-neutral-800 text-neutral-900 dark:text-white shadow-sm'
                                : 'text-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-300',
                        ]"
                    >
                        <Calendar class="w-3.5 h-3.5" />
                        Availability Schedule
                    </button>
                </div>

                <!-- Bottom Action Buttons: Edit Court & Rates, Photos, New Booking -->
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <button
                        type="button"
                        @click="showEditCourtModal = true"
                        class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-4 py-2.5 text-xs font-bold text-neutral-700 dark:text-neutral-200 shadow-sm transition-all hover:border-emerald-500 hover:text-emerald-600 cursor-pointer"
                    >
                        <Pencil class="w-4 h-4 text-emerald-600" /> Edit Court &amp; Rates
                    </button>
                    <button
                        type="button"
                        @click="showImageModal = true"
                        class="inline-flex items-center gap-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-4 py-2.5 text-xs font-bold text-neutral-700 dark:text-neutral-200 shadow-sm transition-all hover:border-emerald-500 hover:text-emerald-600 cursor-pointer"
                    >
                        <ImageIcon class="w-4 h-4 text-emerald-600" /> Photos ({{ court.images.length }})
                    </button>
                    <button
                        v-if="activeTab === 'bookings'"
                        type="button"
                        @click="isBookingModalOpen = true"
                        :disabled="!court.is_active"
                        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm transition-all hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
                    >
                        <CalendarPlus class="w-4 h-4" /> New Booking
                    </button>
                </div>
            </div>

            <!-- BOOKINGS TAB PANEL -->
            <div v-if="activeTab === 'bookings'" class="space-y-4">
                <!-- Filters -->
                <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
                    <div class="flex flex-col sm:flex-row gap-3">
                        <div class="relative flex-1">
                            <Search class="w-4 h-4 absolute left-3 top-2.5 text-neutral-400" />
                            <input
                                v-model="search"
                                @keyup.enter="applyFilters"
                                type="text"
                                placeholder="Search reservations by name, email, phone, or ref code..."
                                class="w-full pl-9 pr-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <select v-model="statusFilter" @change="applyFilters" class="px-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                            <option value="">All Statuses</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="completed">Completed</option>
                        </select>

                        <div class="flex gap-2">
                            <button @click="applyFilters" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700">Search</button>
                            <button @click="clearFilters" class="px-3 py-1.5 bg-neutral-200 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 rounded-lg text-xs font-semibold">Clear</button>
                        </div>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-semibold uppercase">
                                <th class="py-3 px-3">Ref</th>
                                <th class="py-3 px-3">Customer</th>
                                <th class="py-3 px-3">Date</th>
                                <th class="py-3 px-3">Time Slots</th>
                                <th class="py-3 px-3">Amount</th>
                                <th class="py-3 px-3">Status</th>
                                <th class="py-3 px-3">Receipt</th>
                                <th class="py-3 px-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                            <tr
                                v-for="b in bookings.data"
                                :key="b.id"
                                @click="openBookingDetails(b)"
                                class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40 transition-colors cursor-pointer"
                            >
                                <td class="py-3 px-3 font-mono font-bold text-neutral-900 dark:text-white whitespace-nowrap">{{ b.reference_code }}</td>
                                <td class="py-3 px-3">
                                    <div class="font-semibold text-neutral-900 dark:text-white">{{ b.name }}</div>
                                    <div class="text-neutral-400 text-[11px]">{{ b.email }}</div>
                                </td>
                                <td class="py-3 px-3 font-semibold text-emerald-600 dark:text-emerald-400">{{ b.date }}</td>
                                <td class="py-3 px-3 font-mono text-[11px] text-neutral-500">{{ b.time_slots ? b.time_slots.join(', ') : 'N/A' }}</td>
                                <td class="py-3 px-3 font-bold text-neutral-900 dark:text-white">₱{{ b.total_price }}</td>
                                <td class="py-3 px-3">
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded-full text-[10px] font-bold capitalize',
                                            b.status === 'approved' || b.status === 'confirmed' || b.status === 'completed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' :
                                            b.status === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                        ]"
                                    >
                                        {{ b.status }}
                                    </span>
                                </td>
                                <td class="py-3 px-3" @click.stop>
                                    <a
                                        v-if="b.receipt_url"
                                        :href="b.receipt_url"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline"
                                    >
                                        <FileText class="w-3.5 h-3.5" /> View
                                    </a>
                                    <span v-else class="text-neutral-400 text-[11px]">N/A</span>
                                </td>
                                <td class="py-3 px-3 text-right" @click.stop>
                                    <div class="flex items-center justify-end gap-1">
                                        <button
                                            v-if="b.status !== 'approved' && b.status !== 'confirmed'"
                                            @click="updateBookingStatus(b.id, 'approved')"
                                            class="p-1 text-emerald-600 hover:text-emerald-700"
                                            title="Approve"
                                        >
                                            <CheckCircle class="w-4 h-4" />
                                        </button>
                                        <button
                                            v-if="b.status !== 'rejected'"
                                            @click="updateBookingStatus(b.id, 'rejected')"
                                            class="p-1 text-rose-600 hover:text-rose-700"
                                            title="Reject"
                                        >
                                            <XCircle class="w-4 h-4" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="openBookingDetails(b)"
                                            class="p-1 text-neutral-400 hover:text-neutral-900 dark:hover:text-white"
                                            title="View Details"
                                        >
                                            <ArrowUpRight class="w-4 h-4" />
                                        </button>
                                        <button v-if="canDelete" @click="deleteBooking(b.id)" class="p-1 text-rose-400 hover:text-rose-600">
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="bookings.data.length === 0">
                                <td colspan="8" class="py-8 text-center text-xs text-neutral-400">No booking records found for {{ court.name }}.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="bookings.last_page > 1" class="flex justify-center gap-1 pt-2">
                    <template v-for="link in bookings.links" :key="link.label">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-xs font-semibold transition-colors',
                                link.active ? 'bg-emerald-600 text-white' : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-300 hover:bg-emerald-50 dark:hover:bg-neutral-700',
                            ]"
                            v-html="link.label"
                            preserve-scroll
                        />
                        <span
                            v-else
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold text-neutral-300 dark:text-neutral-600"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>

            <!-- AVAILABILITY SCHEDULE TAB PANEL -->
            <div v-if="activeTab === 'schedule'">
                <VenueScheduleTab
                    :courts="scheduleCourts"
                    :venue-name="court.venue?.name || court.name"
                    @book-slot="handleBookSlot"
                />
            </div>
        </section>

        <!-- Booking Detail Modal -->
        <BookingDetailModal
            :is-open="showDetailModal"
            :booking="selectedBooking"
            update-route-prefix="/admin/bookings"
            @close="showDetailModal = false"
        />

        <!-- Create Booking Modal -->
        <CreateBookingModal
            :open="isBookingModalOpen"
            :courts="courtOptionsForModal"
            :initial-date="modalInitialDate"
            :initial-court-id="modalInitialCourtId"
            :initial-slot="modalInitialSlot"
            action="/admin/bookings"
            @close="isBookingModalOpen = false"
        />

        <!-- Court Image Manager Modal -->
        <CourtImageManagerModal
            :is-open="showImageModal"
            :court="{ id: court.id, name: court.name, images: court.images }"
            :upload-route="`/admin/courts/${court.id}/images`"
            :primary-route-prefix="`/admin/courts/${court.id}/images`"
            :delete-route-prefix="`/admin/courts/${court.id}/images`"
            :can-delete="true"
            @close="showImageModal = false"
        />

        <!-- Edit Court & Rates Modal -->
        <EditCourtModal
            :is-open="showEditCourtModal"
            :court="court"
            @close="showEditCourtModal = false"
        />
    </div>
</template>
