<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router, usePage } from '@inertiajs/vue3';
import { CalendarDays, Search, CheckCircle, XCircle, ArrowUpRight, FileText, Plus, X } from '@lucide/vue';

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
    court?: { id: number; name: string };
}

interface PaginatedBookings {
    data: Booking[];
    links: any[];
}

const props = defineProps<{
    bookings: PaginatedBookings;
    assignedCourts: { id: number; name: string }[];
    filters: { search?: string; court_id?: string; status?: string; date?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Court Staff Dashboard', href: '/staff/dashboard' },
            { title: 'Court Bookings', href: '/staff/bookings' },
        ],
    },
});

const search = ref(props.filters.search || '');
const court_id = ref(props.filters.court_id || '');
const status = ref(props.filters.status || '');
const date = ref(props.filters.date || '');

function applyFilters() {
    router.get('/staff/bookings', {
        search: search.value,
        court_id: court_id.value,
        status: status.value,
        date: date.value,
    }, { preserveState: true, replace: true });
}

const page = usePage();
const user = computed(() => page.props.auth?.user as any);
const canUpdate = computed(() => {
    return user.value?.is_super_admin || user.value?.is_admin;
});

const actionForm = useForm({
    status: '',
});

function updateStatus(bookingId: number, newStatus: string) {
    if (!canUpdate.value) return;
    actionForm.status = newStatus;
    actionForm.patch(`/staff/bookings/${bookingId}/status`, {
        preserveScroll: true,
    });
}

const showCreateModal = ref(false);

const availableSlots = [
    '08:00 - 09:00',
    '09:00 - 10:00',
    '10:00 - 11:00',
    '11:00 - 12:00',
    '12:00 - 13:00',
    '13:00 - 14:00',
    '14:00 - 15:00',
    '15:00 - 16:00',
    '16:00 - 17:00',
    '17:00 - 18:00',
    '18:00 - 19:00',
    '19:00 - 20:00',
    '20:00 - 21:00',
];

const createForm = useForm({
    court_id: props.assignedCourts[0]?.id || '',
    name: '',
    email: '',
    phone: '',
    date: new Date().toISOString().slice(0, 10),
    time_slots: [] as string[],
    notes: '',
});

function submitCreate() {
    createForm.post('/staff/bookings', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Assigned Court Bookings - Court Staff" />

    <div class="p-6 space-y-6 max-w-7xl mx-auto">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Assigned Court Bookings</h1>
                <p class="text-xs text-neutral-500">Manage, view, or log new booking entries for your assigned court(s).</p>
            </div>

            <button
                @click="showCreateModal = true"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow transition-colors flex items-center gap-2 cursor-pointer"
            >
                <Plus class="w-4 h-4" /> Create New Booking
            </button>
        </div>

        <!-- Filters Bar -->
        <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                <div class="relative">
                    <Search class="w-4 h-4 absolute left-3 top-2.5 text-neutral-400" />
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search customer name..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <select
                    v-if="assignedCourts.length > 1"
                    v-model="court_id"
                    @change="applyFilters"
                    class="w-full px-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                >
                    <option value="">All Assigned Courts</option>
                    <option v-for="c in assignedCourts" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>

                <select
                    v-model="status"
                    @change="applyFilters"
                    class="w-full px-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                >
                    <option value="">All Statuses</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="confirmed">Confirmed</option>
                    <option value="rejected">Rejected</option>
                </select>

                <input
                    v-model="date"
                    @change="applyFilters"
                    type="date"
                    class="w-full px-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                />
            </div>
        </div>

        <!-- Bookings Table -->
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
                        <th class="py-3 px-3">Total</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3">Receipt</th>
                        <th class="py-3 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    <tr v-for="b in bookings.data" :key="b.id" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40">
                        <td class="py-3 px-3 font-mono font-bold">#{{ b.id }}</td>
                        <td class="py-3 px-3 font-semibold text-neutral-900 dark:text-white">{{ b.name }}</td>
                        <td class="py-3 px-3 text-neutral-500">
                            <div>{{ b.email }}</div>
                            <div>{{ b.phone }}</div>
                        </td>
                        <td class="py-3 px-3 text-neutral-800 dark:text-neutral-200">{{ b.court?.name }}</td>
                        <td class="py-3 px-3 text-neutral-700 dark:text-neutral-300">{{ b.date }}</td>
                        <td class="py-3 px-3 font-mono text-[11px] text-neutral-500">{{ b.time_slots ? b.time_slots.join(', ') : '' }}</td>
                        <td class="py-3 px-3 font-bold text-emerald-600">${{ b.total_price }}</td>
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
                        <td class="py-3 px-3">
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
                        <td class="py-3 px-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <template v-if="canUpdate">
                                    <button
                                        v-if="b.status !== 'approved' && b.status !== 'confirmed'"
                                        @click="updateStatus(b.id, 'approved')"
                                        class="p-1 text-emerald-600 hover:text-emerald-700"
                                        title="Approve Request"
                                    >
                                        <CheckCircle class="w-4 h-4" />
                                    </button>
                                    <button
                                        v-if="b.status !== 'rejected'"
                                        @click="updateStatus(b.id, 'rejected')"
                                        class="p-1 text-rose-600 hover:text-rose-700"
                                        title="Reject Request"
                                    >
                                        <XCircle class="w-4 h-4" />
                                    </button>
                                </template>
                                <Link :href="`/staff/bookings/${b.id}`" class="p-1 text-neutral-400 hover:text-neutral-900 dark:hover:text-white">
                                    <ArrowUpRight class="w-4 h-4" />
                                </Link>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="bookings.data.length === 0">
                        <td colSpan="10" class="py-8 text-center text-xs text-neutral-400">No bookings recorded for assigned court.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Centered Create Booking Modal -->
    <Teleport to="body">
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 overflow-y-auto"
            @click.self="showCreateModal = false"
        >
            <div class="w-full max-w-lg rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-2xl space-y-5 my-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-base text-neutral-900 dark:text-white">Log New Booking</h3>
                        <p class="text-xs text-neutral-500">Register a new reservation for an assigned court.</p>
                    </div>
                    <button @click="showCreateModal = false" class="text-neutral-400 hover:text-neutral-600 dark:hover:text-white">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                    <div>
                        <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1">Target Court *</label>
                        <select v-model="createForm.court_id" required class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                            <option v-for="c in assignedCourts" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1">Customer Full Name *</label>
                            <input v-model="createForm.name" type="text" required placeholder="John Doe" class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1">Customer Email *</label>
                            <input v-model="createForm.email" type="email" required placeholder="customer@example.com" class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500" />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1">Contact Phone *</label>
                            <input v-model="createForm.phone" type="text" required placeholder="(555) 000-0000" class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500" />
                        </div>
                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1">Booking Date *</label>
                            <input v-model="createForm.date" type="date" required class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1.5">Select Reserved Time Slots *</label>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-36 overflow-y-auto p-1 border border-neutral-200 dark:border-neutral-800 rounded-xl">
                            <label v-for="slot in availableSlots" :key="slot" class="flex items-center gap-1.5 cursor-pointer bg-neutral-50 dark:bg-neutral-800 px-2 py-1.5 rounded-lg border border-neutral-200 dark:border-neutral-700">
                                <input type="checkbox" :value="slot" v-model="createForm.time_slots" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span class="text-[11px] font-mono">{{ slot }}</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1">Notes / Internal Reference</label>
                        <input v-model="createForm.notes" type="text" placeholder="Walk-in payment, cash, phone reservation..." class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500" />
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-3">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-neutral-100 dark:bg-neutral-800 hover:bg-neutral-200 dark:hover:bg-neutral-700 text-neutral-700 dark:text-neutral-300 rounded-xl font-medium transition-colors">Cancel</button>
                        <button type="submit" :disabled="createForm.processing || createForm.time_slots.length === 0" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl font-semibold shadow transition-colors disabled:opacity-50">Create Booking</button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
