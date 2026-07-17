<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import { CalendarDays, LayoutList, Search, CheckCircle, XCircle, Trash2, ArrowUpRight, FileText, Plus } from '@lucide/vue';
import BookingsCalendar from '@/components/admin/BookingsCalendar.vue';
import CreateBookingModal from '@/components/admin/CreateBookingModal.vue';

import BookingDetailModal, { type BookingDetail } from '@/components/admin/BookingDetailModal.vue';

interface Booking {
    id: number;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_path?: string | null;
    receipt_url?: string | null;
    status: string;
    notes?: string;
    court?: { id: number; name: string };
    user?: { id: number; name: string };
}

interface PaginatedBookings {
    data: Booking[];
    links: any[];
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    view: 'calendar' | 'list';
    days?: any[];
    window?: any;
    bookings?: PaginatedBookings;
    courts: { id: number; name: string }[];
    venues?: { id: number; name: string }[] | null;
    filters: { search?: string; court_id?: string; status?: string; date?: string; venue_id?: string };
    basePath: string;
    canDelete: boolean;
    showVenueFilter: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/admin/dashboard' },
            { title: 'Bookings', href: '/admin/bookings' },
        ],
    },
});

function switchView(view: 'calendar' | 'list') {
    router.get(props.basePath, { view }, { preserveState: false });
}

const showCreateModal = ref(false);
const showDetailModal = ref(false);
const selectedBookingForModal = ref<BookingDetail | null>(null);

function openBookingDetails(booking: Booking) {
    const detail: BookingDetail = {
        id: booking.id,
        reference_code: `DY-RESRV-${String(booking.id).padStart(6, '0')}`,
        customer_name: booking.name,
        email: booking.email,
        phone: booking.phone,
        date: booking.date,
        time_slots: booking.time_slots,
        total_price: booking.total_price,
        receipt_url: booking.receipt_url || (booking.receipt_path ? `/storage/${booking.receipt_path}` : null),
        status: booking.status,
        notes: booking.notes,
        court_name: booking.court?.name || 'Assigned Court',
    };
    selectedBookingForModal.value = detail;
    showDetailModal.value = true;
}

// --- List view state ---
const search = ref(props.filters.search || '');
const court_id = ref(props.filters.court_id || '');
const status = ref(props.filters.status || '');
const date = ref(props.filters.date || '');
const venue_id = ref(props.filters.venue_id || '');

function applyFilters() {
    router.get(props.basePath, {
        view: 'list',
        search: search.value,
        court_id: court_id.value,
        status: status.value,
        date: date.value,
        venue_id: venue_id.value,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    court_id.value = '';
    status.value = '';
    date.value = '';
    venue_id.value = '';
    applyFilters();
}

const actionForm = useForm({ status: '' });

function updateStatus(bookingId: number, newStatus: string) {
    actionForm.status = newStatus;
    actionForm.patch(`${props.basePath}/${bookingId}/status`, { preserveScroll: true });
}

function deleteBooking(bookingId: number) {
    if (confirm('Are you sure you want to delete this booking entry?')) {
        actionForm.delete(`${props.basePath}/${bookingId}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Bookings" />

    <div class="p-6 space-y-6 w-full">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Facility Bookings</h1>
                <p class="text-xs text-neutral-500">Monitor, approve, and manage reservations by day or in a list.</p>
            </div>

            <div class="flex items-center gap-2">
                <!-- View toggle -->
                <div class="inline-flex rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-1 shadow-sm">
                    <button
                        @click="switchView('calendar')"
                        :class="['inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-colors', view === 'calendar' ? 'bg-emerald-600 text-white' : 'text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800']"
                    >
                        <CalendarDays class="w-4 h-4" /> Calendar
                    </button>
                    <button
                        @click="switchView('list')"
                        :class="['inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-bold transition-colors', view === 'list' ? 'bg-emerald-600 text-white' : 'text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800']"
                    >
                        <LayoutList class="w-4 h-4" /> List
                    </button>
                </div>

                <button
                    @click="showCreateModal = true"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow transition-colors hover:bg-emerald-700"
                >
                    <Plus class="w-4 h-4" /> New Booking
                </button>
            </div>
        </div>

        <CreateBookingModal
            :open="showCreateModal"
            :courts="courts"
            :action="basePath"
            @close="showCreateModal = false"
        />

        <!-- Calendar board -->
        <BookingsCalendar
            v-if="view === 'calendar'"
            :days="days || []"
            :courts="courts"
            :venues="venues"
            :filters="filters"
            :window="window"
            :base-path="basePath"
            :can-delete="canDelete"
            :show-venue-filter="showVenueFilter"
            :can-update="true"
        />

        <!-- List view -->
        <template v-else>
            <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-3">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div class="relative">
                        <Search class="w-4 h-4 absolute left-3 top-2.5 text-neutral-400" />
                        <input
                            v-model="search"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Search name, email, phone..."
                            class="w-full pl-9 pr-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                        />
                    </div>

                    <select v-if="showVenueFilter" v-model="venue_id" @change="applyFilters" class="w-full px-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        <option value="">All Venues</option>
                        <option v-for="v in venues || []" :key="v.id" :value="v.id">{{ v.name }}</option>
                    </select>

                    <select v-model="court_id" @change="applyFilters" class="w-full px-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        <option value="">All Courts</option>
                        <option v-for="c in courts" :key="c.id" :value="c.id">{{ c.name }}</option>
                    </select>

                    <select v-model="status" @change="applyFilters" class="w-full px-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="rejected">Rejected</option>
                        <option value="cancelled">Cancelled</option>
                    </select>

                    <input v-model="date" @change="applyFilters" type="date" class="w-full px-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500" />
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button @click="applyFilters" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700">Filter</button>
                    <button @click="clearFilters" class="px-3 py-1.5 bg-neutral-200 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 rounded-lg text-xs font-semibold">Clear</button>
                </div>
            </div>

            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-semibold uppercase">
                            <th class="py-3 px-3">ID</th>
                            <th class="py-3 px-3">Customer Name</th>
                            <th class="py-3 px-3">Contact</th>
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
                        <tr v-for="b in bookings?.data || []" :key="b.id" @click="openBookingDetails(b)" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40 transition-colors cursor-pointer">
                            <td class="py-3 px-3 font-mono font-bold text-neutral-900 dark:text-white">#{{ b.id }}</td>
                            <td class="py-3 px-3 font-semibold text-neutral-900 dark:text-white">{{ b.name }}</td>
                            <td class="py-3 px-3 text-neutral-500">
                                <div>{{ b.email }}</div>
                                <div>{{ b.phone }}</div>
                            </td>
                            <td class="py-3 px-3 font-medium text-neutral-800 dark:text-neutral-200">{{ b.court?.name || 'N/A' }}</td>
                            <td class="py-3 px-3 font-semibold text-emerald-600 dark:text-emerald-400 underline decoration-dotted">{{ b.date }}</td>
                            <td class="py-3 px-3 font-mono text-[11px] text-neutral-500">{{ b.time_slots ? b.time_slots.join(', ') : 'N/A' }}</td>
                            <td class="py-3 px-3 font-bold text-neutral-900 dark:text-white">₱{{ b.total_price }}</td>
                            <td class="py-3 px-3">
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded-full text-[10px] font-bold capitalize',
                                        b.status === 'approved' || b.status === 'confirmed' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' :
                                        b.status === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300'
                                    ]"
                                >
                                    {{ b.status }}
                                </span>
                            </td>
                            <td class="py-3 px-3" @click.stop>
                                <a
                                    v-if="b.receipt_url || b.receipt_path"
                                    :href="b.receipt_url || '/storage/' + b.receipt_path"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400 hover:underline"
                                >
                                    <FileText class="w-3.5 h-3.5" /> View Receipt
                                </a>
                                <span v-else class="text-neutral-400 text-[11px]">N/A</span>
                            </td>
                            <td class="py-3 px-3 text-right" @click.stop>
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        v-if="b.status !== 'approved' && b.status !== 'confirmed'"
                                        @click="updateStatus(b.id, 'approved')"
                                        class="p-1 text-emerald-600 hover:text-emerald-700"
                                        title="Approve"
                                    >
                                        <CheckCircle class="w-4 h-4" />
                                    </button>
                                    <button
                                        v-if="b.status !== 'rejected'"
                                        @click="updateStatus(b.id, 'rejected')"
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

                        <tr v-if="(bookings?.data.length || 0) === 0">
                            <td colSpan="10" class="py-8 text-center text-xs text-neutral-400">No bookings found matching filter criteria.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
        
        <BookingDetailModal
            :is-open="showDetailModal"
            :booking="selectedBookingForModal"
            :update-route-prefix="basePath"
            @close="showDetailModal = false"
        />
    </div>
</template>
