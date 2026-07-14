<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import ReservationCalendar from '@/components/dashboard/ReservationCalendar.vue';
import { Calendar, Plus, Trash2, ShieldAlert } from '@lucide/vue';

interface CourtItem {
    id: number;
    name: string;
}

interface BookingSlot {
    id: number;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    status: string;
    total_price: string;
}

interface UnavailabilitySlot {
    id: number;
    date: string;
    start_time?: string;
    end_time?: string;
    all_day: boolean;
    reason?: string;
}

const props = defineProps<{
    assignedCourts: CourtItem[];
    selectedCourt: CourtItem | null;
    currentMonth: string;
    bookings: BookingSlot[];
    unavailabilities: UnavailabilitySlot[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Court Staff Dashboard', href: '/staff/dashboard' },
            { title: 'Court Schedule & Maintenance', href: '/staff/schedules' },
        ],
    },
});

const isAddModalOpen = ref(false);

const page = usePage();
const user = computed(() => page.props.auth?.user as any);
const canManageSchedule = computed(() => {
    return user.value?.is_super_admin || user.value?.is_admin;
});

const addForm = useForm({
    court_id: props.selectedCourt?.id || (props.assignedCourts[0]?.id ?? ''),
    date: '',
    start_time: '08:00',
    end_time: '12:00',
    all_day: true,
    reason: 'Maintenance',
});

function submitAdd() {
    if (!canManageSchedule.value) return;
    addForm.post('/staff/schedules', {
        onSuccess: () => {
            addForm.reset();
            isAddModalOpen.value = false;
        },
    });
}

const deleteForm = useForm({});

function deleteUnavailability(id: number) {
    if (!canManageSchedule.value) return;
    if (confirm('Remove this unavailable date entry?')) {
        deleteForm.delete(`/staff/schedules/${id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Court Schedules & Blackout Dates - Court Staff" />

    <div class="p-6 space-y-6 max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Court Schedule & Unavailable Dates</h1>
                    <p class="text-xs text-neutral-500">View reservations calendar and block out dates for court maintenance or holidays.</p>
                </div>

                <button
                    v-if="canManageSchedule"
                    @click="isAddModalOpen = true"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow transition-colors flex items-center gap-2"
                >
                    <Plus class="w-4 h-4" /> Block Unavailable Date/Slot
                </button>
            </div>

            <!-- Modal for Adding Blackout Slot -->
            <div v-if="isAddModalOpen" class="p-5 rounded-2xl border border-rose-200 dark:border-rose-900 bg-rose-50/50 dark:bg-rose-950/20 space-y-4">
                <h3 class="font-bold text-sm text-neutral-900 dark:text-white">Block Out Court Date for Maintenance / Closure</h3>

                <form @submit.prevent="submitAdd" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1">Target Court</label>
                            <select v-model="addForm.court_id" required class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2 dark:bg-neutral-800 text-neutral-900 dark:text-white">
                                <option v-for="c in assignedCourts" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1">Blackout Date</label>
                            <input v-model="addForm.date" type="date" required class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2 dark:bg-neutral-800 text-neutral-900 dark:text-white" />
                        </div>

                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1">Reason</label>
                            <input v-model="addForm.reason" type="text" placeholder="e.g. Resurfacing Maintenance" class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2 dark:bg-neutral-800 text-neutral-900 dark:text-white" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="isAddModalOpen = false" class="px-3 py-1.5 bg-neutral-200 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="addForm.processing" class="px-4 py-1.5 bg-rose-600 text-white rounded-lg font-semibold">Save Blackout Slot</button>
                    </div>
                </form>
            </div>

            <!-- Interactive Reservation Schedule Component -->
            <ReservationCalendar
                :bookings="bookings"
                :unavailabilities="unavailabilities"
            />

            <!-- Blackout Dates Management Table -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-4">
                <h3 class="font-bold text-sm text-neutral-900 dark:text-white flex items-center gap-2">
                    <ShieldAlert class="w-4 h-4 text-rose-600" /> Active Blackout & Maintenance Entries
                </h3>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-xs">
                        <thead>
                            <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-semibold uppercase">
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Duration</th>
                                <th class="py-2.5 px-3">Reason</th>
                                <th v-if="canManageSchedule" class="py-2.5 px-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                            <tr v-for="u in unavailabilities" :key="u.id" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40">
                                <td class="py-3 px-3 font-bold text-neutral-900 dark:text-white">{{ u.date }}</td>
                                <td class="py-3 px-3 text-neutral-600 dark:text-neutral-300">{{ u.all_day ? 'Full Day Closed' : u.start_time + ' - ' + u.end_time }}</td>
                                <td class="py-3 px-3 text-neutral-500">{{ u.reason || 'Maintenance' }}</td>
                                <td v-if="canManageSchedule" class="py-3 px-3 text-right">
                                    <button @click="deleteUnavailability(u.id)" class="p-1 text-rose-500 hover:text-rose-700">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="unavailabilities.length === 0">
                                <td :colSpan="canManageSchedule ? 4 : 3" class="py-6 text-center text-xs text-neutral-400">No active blackout slots recorded.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
</template>
