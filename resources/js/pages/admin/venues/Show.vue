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
} from '@lucide/vue';
import BookingDetailModal, { type BookingDetail } from '@/components/admin/BookingDetailModal.vue';

interface VenueProfile {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    image_url?: string | null;
    cover_image_url?: string | null;
    gcash_number?: string | null;
    gcash_qr_url?: string | null;
    maya_number?: string | null;
    maya_qr_url?: string | null;
    is_active: boolean;
    courts_count: number;
    created_at: string;
}

interface CourtItem {
    id: number;
    name: string;
    slug: string;
    sport_type: string;
    description?: string | null;
    base_price: string;
    slot_duration_minutes: number;
    is_active: boolean;
    status: string;
    primary_image_url?: string | null;
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
    venue: VenueProfile;
    courts: CourtItem[];
    bookings: PaginatedBookings;
    filters: { search?: string; status?: string };
    canDelete: boolean;
    canManageVenue: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Venues', href: '/admin/venues' },
            { title: 'Venue Profile' },
        ],
    },
});

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

const showDetailModal = ref(false);
const selectedBooking = ref<BookingDetail | null>(null);

function applyFilters() {
    router.get(`/admin/venues/${props.venue.id}`, {
        search: search.value || undefined,
        status: status.value || undefined,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    status.value = '';
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

const activeCourtCount = computed(() => props.courts.filter(c => c.is_active).length);
const totalBookings = computed(() => props.bookings.data.length);
</script>

<template>
    <Head :title="`${venue.name} – Venue Profile`" />

    <div class="p-6 space-y-8 w-full">
        <!-- Back link -->
        <Link href="/admin/venues" class="inline-flex items-center gap-1.5 text-xs font-semibold text-neutral-500 hover:text-emerald-600 transition-colors">
            <ArrowLeft class="w-3.5 h-3.5" /> Back to Venues
        </Link>

        <!-- Venue Header Card -->
        <div class="relative overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
            <!-- Cover Image Banner -->
            <div class="relative h-44 sm:h-56 overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                <img
                    v-if="venue.cover_image_url"
                    :src="venue.cover_image_url"
                    :alt="venue.name"
                    class="h-full w-full object-cover"
                />
                <div v-else class="flex h-full w-full items-center justify-center text-neutral-300 dark:text-neutral-600">
                    <Building class="h-16 w-16" />
                </div>
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent" />
                <span
                    :class="[
                        'absolute right-4 top-4 rounded-full px-3 py-1 text-[11px] font-bold capitalize shadow-md',
                        venue.is_active ? 'bg-emerald-500 text-white' : 'bg-rose-500 text-white',
                    ]"
                >
                    {{ venue.is_active ? 'Active' : 'Inactive' }}
                </span>
            </div>

            <!-- Venue Info Below Cover -->
            <div class="p-6 sm:p-8">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">{{ venue.name }}</h1>
                        <p v-if="venue.description" class="mt-1 text-sm text-neutral-500 max-w-2xl">{{ venue.description }}</p>

                        <div class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-xs text-neutral-500">
                            <span v-if="venue.address" class="inline-flex items-center gap-1.5">
                                <MapPin class="w-3.5 h-3.5 text-neutral-400" />{{ venue.address }}
                            </span>
                            <span v-if="venue.phone" class="inline-flex items-center gap-1.5">
                                <Phone class="w-3.5 h-3.5 text-neutral-400" />{{ venue.phone }}
                            </span>
                            <span v-if="venue.email" class="inline-flex items-center gap-1.5">
                                <Mail class="w-3.5 h-3.5 text-neutral-400" />{{ venue.email }}
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 px-4 py-3 text-center">
                            <p class="text-xl font-black text-emerald-600">{{ venue.courts_count }}</p>
                            <p class="text-[10px] uppercase font-semibold text-neutral-400 tracking-wider">Courts</p>
                        </div>
                        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 px-4 py-3 text-center">
                            <p class="text-xl font-black text-blue-600">{{ activeCourtCount }}</p>
                            <p class="text-[10px] uppercase font-semibold text-neutral-400 tracking-wider">Active</p>
                        </div>
                    </div>
                </div>

                <!-- Payment methods badges -->
                <div v-if="venue.gcash_number || venue.maya_number" class="mt-5 flex flex-wrap gap-2">
                    <span v-if="venue.gcash_number" class="inline-flex items-center gap-1 rounded-full bg-blue-50 dark:bg-blue-950/40 px-3 py-1 text-[11px] font-semibold text-blue-700 dark:text-blue-300">
                        GCash: {{ venue.gcash_number }}
                    </span>
                    <span v-if="venue.maya_number" class="inline-flex items-center gap-1 rounded-full bg-green-50 dark:bg-green-950/40 px-3 py-1 text-[11px] font-semibold text-green-700 dark:text-green-300">
                        Maya: {{ venue.maya_number }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Courts Section -->
        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Courts</h2>
                <p class="text-xs text-neutral-500">All courts registered under this venue.</p>
            </div>

            <div
                v-if="courts.length"
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <Link
                    v-for="court in courts"
                    :key="court.id"
                    :href="`/admin/courts/${court.id}`"
                    class="group flex flex-col overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm transition-all hover:shadow-md hover:border-emerald-400/50"
                >
                    <div class="relative aspect-video overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                        <img
                            v-if="court.primary_image_url"
                            :src="court.primary_image_url"
                            :alt="court.name"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                        />
                        <div v-else class="flex h-full w-full items-center justify-center text-neutral-300 dark:text-neutral-600">
                            <Dumbbell class="h-8 w-8" />
                        </div>
                        <span
                            :class="[
                                'absolute right-2 top-2 rounded-full px-2 py-0.5 text-[10px] font-bold capitalize shadow-sm',
                                court.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
                            ]"
                        >
                            {{ court.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <div class="flex flex-1 flex-col gap-1.5 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <h3 class="text-sm font-semibold leading-tight text-neutral-900 dark:text-white">{{ court.name }}</h3>
                            <span class="shrink-0 rounded-full bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 text-[10px] font-medium text-neutral-500">
                                {{ court.sport_type }}
                            </span>
                        </div>
                        <p class="text-xs text-neutral-500 line-clamp-2">{{ court.description || 'No description' }}</p>
                        <div class="mt-auto pt-2 flex items-center justify-between border-t border-neutral-100 dark:border-neutral-800">
                            <span class="text-sm font-bold text-emerald-600">₱{{ court.base_price }}<span class="text-[10px] font-normal text-neutral-400">/hr</span></span>
                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-neutral-400 group-hover:text-emerald-500 transition-colors">
                                <Eye class="w-3.5 h-3.5" /> View
                            </span>
                        </div>
                    </div>
                </Link>
            </div>

            <div
                v-else
                class="rounded-2xl border border-dashed border-neutral-300 dark:border-neutral-700 py-12 text-center text-sm text-neutral-500"
            >
                No courts have been added to this venue yet.
            </div>
        </section>

        <!-- Bookings Section -->
        <section class="space-y-4">
            <div>
                <h2 class="text-lg font-bold text-neutral-900 dark:text-white">Bookings</h2>
                <p class="text-xs text-neutral-500">All reservations for courts at {{ venue.name }}.</p>
            </div>

            <!-- Filters -->
            <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <Search class="w-4 h-4 absolute left-3 top-2.5 text-neutral-400" />
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search by name, email, phone, or ID..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <select v-model="status" @change="applyFilters" class="px-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
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
                            <th class="py-3 px-3">Court</th>
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
                                <div class="text-neutral-400">{{ b.email }}</div>
                            </td>
                            <td class="py-3 px-3 font-medium text-neutral-800 dark:text-neutral-200">{{ b.court_name }}</td>
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
                            <td colspan="9" class="py-8 text-center text-xs text-neutral-400">No bookings found for this venue.</td>
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
        </section>

        <BookingDetailModal
            :is-open="showDetailModal"
            :booking="selectedBooking"
            update-route-prefix="/admin/bookings"
            @close="showDetailModal = false"
        />
    </div>
</template>
