<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { FileText, Dumbbell, Edit2, AlertCircle } from '@lucide/vue';

export interface CourtItem {
    id: number;
    name: string;
    sport_type: string;
}

export interface BookingItem {
    id: number;
    reference_code: string;
    court: CourtItem | null;
    name: string;
    email: string;
    phone: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_url?: string | null;
    status: string;
    notes?: string | null;
}

defineProps<{
    bookings: BookingItem[];
}>();

// Edit Booking State
const selectedBooking = ref<BookingItem | null>(null);
const isEditModalOpen = ref(false);

const editForm = useForm({
    name: '',
    email: '',
    phone: '',
    notes: '',
});

function openEditModal(booking: BookingItem) {
    selectedBooking.value = booking;
    editForm.name = booking.name;
    editForm.email = booking.email;
    editForm.phone = booking.phone;
    editForm.notes = booking.notes || '';
    isEditModalOpen.value = true;
}

function submitEdit() {
    if (!selectedBooking.value) return;
    editForm.patch(`/bookings/${selectedBooking.value.id}`, {
        onSuccess: () => {
            isEditModalOpen.value = false;
            selectedBooking.value = null;
        },
    });
}

function cancelBooking(bookingId: number) {
    if (confirm('Are you sure you want to cancel this booking reservation?')) {
        const cancelForm = useForm({});
        cancelForm.delete(`/bookings/${bookingId}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div>
        <!-- Desktop Horizontal Table View (hidden md:block) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-bold uppercase tracking-wider">
                        <th class="py-3 px-3">Reference Code</th>
                        <th class="py-3 px-3">Court / Sport</th>
                        <th class="py-3 px-3">Schedule</th>
                        <th class="py-3 px-3">Amount</th>
                        <th class="py-3 px-3">Receipt</th>
                        <th class="py-3 px-3">Status</th>
                        <th class="py-3 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    <tr v-for="booking in bookings" :key="booking.id" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40 transition-colors">
                        <td class="py-4 px-3 font-mono font-bold text-emerald-600">{{ booking.reference_code }}</td>
                        <td class="py-4 px-3">
                            <div class="flex flex-col">
                                <span class="font-bold text-neutral-900 dark:text-white">{{ booking.court?.name || 'Deleted Court' }}</span>
                                <span class="text-[10px] text-neutral-400 font-medium flex items-center gap-1 mt-0.5">
                                    <Dumbbell class="w-3 h-3 text-emerald-600" /> {{ booking.court?.sport_type || 'Pickleball' }}
                                </span>
                            </div>
                        </td>
                        <td class="py-4 px-3">
                            <div class="flex flex-col">
                                <span class="font-bold text-neutral-800 dark:text-neutral-200">{{ booking.date }}</span>
                                <span class="text-[10px] text-neutral-500 font-mono mt-0.5">{{ booking.time_slots ? booking.time_slots.join(', ') : 'N/A' }}</span>
                            </div>
                        </td>
                        <td class="py-4 px-3 font-bold text-neutral-900 dark:text-white">₱{{ booking.total_price }}</td>
                        <td class="py-4 px-3">
                            <a
                                v-if="booking.receipt_url"
                                :href="booking.receipt_url"
                                target="_blank"
                                class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline"
                            >
                                <FileText class="w-3.5 h-3.5" /> View Uploaded
                            </a>
                            <span v-else class="text-neutral-400 italic">No receipt</span>
                        </td>
                        <td class="py-4 px-3">
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold capitalize inline-block"
                                :class="{
                                    'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400': booking.status === 'pending',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400': booking.status === 'approved' || booking.status === 'confirmed',
                                    'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-400': booking.status === 'rejected' || booking.status === 'cancelled',
                                    'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-300': booking.status === 'completed'
                                }"
                            >
                                {{ booking.status }}
                            </span>
                        </td>
                        <td class="py-4 px-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <template v-if="booking.status === 'pending'">
                                    <button
                                        @click="openEditModal(booking)"
                                        class="p-1.5 text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white bg-neutral-100 dark:bg-neutral-800 rounded-lg hover:shadow-sm transition-all"
                                        title="Edit Booking Details"
                                    >
                                        <Edit2 class="w-3.5 h-3.5" />
                                    </button>
                                    <button
                                        @click="cancelBooking(booking.id)"
                                        class="p-1.5 text-rose-600 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/20 rounded-lg transition-colors font-bold"
                                        title="Cancel Booking"
                                    >
                                        Cancel
                                    </button>
                                </template>
                                <span v-else class="text-[10px] text-neutral-400 italic">Locked</span>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="bookings.length === 0">
                        <td colspan="7" class="py-12 text-center text-xs text-neutral-400">
                            <div class="flex flex-col items-center justify-center space-y-2">
                                <AlertCircle class="w-8 h-8 text-neutral-300 dark:text-neutral-700" />
                                <span>You haven't made any court bookings yet.</span>
                                <Link href="/courts" class="text-emerald-600 font-bold hover:underline">Browse Courts and book &rarr;</Link>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Mobile Vertical Display Stack View (block md:hidden) -->
        <div class="md:hidden space-y-3">
            <div
                v-for="booking in bookings"
                :key="booking.id"
                class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-800/40 p-4 space-y-3 text-xs shadow-xs"
            >
                <div class="flex items-center justify-between border-b border-neutral-200/60 dark:border-neutral-700/60 pb-2.5">
                    <div class="flex flex-col">
                        <span class="text-[10px] uppercase font-bold text-neutral-400">Reference Code</span>
                        <span class="font-mono font-bold text-emerald-600 text-sm">{{ booking.reference_code }}</span>
                    </div>
                    <span
                        class="px-2.5 py-0.5 rounded-full text-[10px] font-bold capitalize"
                        :class="{
                            'bg-amber-100 text-amber-800 dark:bg-amber-950/40 dark:text-amber-400': booking.status === 'pending',
                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-400': booking.status === 'approved' || booking.status === 'confirmed',
                            'bg-rose-100 text-rose-800 dark:bg-rose-950/40 dark:text-rose-400': booking.status === 'rejected' || booking.status === 'cancelled',
                            'bg-neutral-100 text-neutral-800 dark:bg-neutral-800 dark:text-neutral-300': booking.status === 'completed'
                        }"
                    >
                        {{ booking.status }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-0.5">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-neutral-400 block">Court / Sport</span>
                        <span class="font-bold text-neutral-900 dark:text-white block mt-0.5">{{ booking.court?.name || 'Deleted Court' }}</span>
                        <span class="text-[10px] text-neutral-400 font-medium flex items-center gap-1 mt-0.5">
                            <Dumbbell class="w-3 h-3 text-emerald-600" /> {{ booking.court?.sport_type || 'Pickleball' }}
                        </span>
                    </div>

                    <div>
                        <span class="text-[10px] uppercase font-bold text-neutral-400 block">Total Amount</span>
                        <span class="font-black text-neutral-900 dark:text-white text-sm block mt-0.5">₱{{ booking.total_price }}</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-2 border-t border-neutral-200/40 dark:border-neutral-700/40">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-neutral-400 block">Schedule</span>
                        <span class="font-bold text-neutral-800 dark:text-neutral-200 block mt-0.5">{{ booking.date }}</span>
                        <span class="text-[10px] text-neutral-500 font-mono block mt-0.5">{{ booking.time_slots ? booking.time_slots.join(', ') : 'N/A' }}</span>
                    </div>

                    <div>
                        <span class="text-[10px] uppercase font-bold text-neutral-400 block">Receipt</span>
                        <a
                            v-if="booking.receipt_url"
                            :href="booking.receipt_url"
                            target="_blank"
                            class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600 hover:text-emerald-700 hover:underline mt-0.5"
                        >
                            <FileText class="w-3.5 h-3.5" /> View Uploaded
                        </a>
                        <span v-else class="text-neutral-400 italic block mt-0.5">No receipt</span>
                    </div>
                </div>

                <div v-if="booking.status === 'pending'" class="flex items-center justify-end gap-2 pt-2 border-t border-neutral-200/40 dark:border-neutral-700/40">
                    <button
                        @click="openEditModal(booking)"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-neutral-700 dark:text-neutral-300 bg-neutral-200/60 dark:bg-neutral-700/50 rounded-lg transition-colors"
                    >
                        <Edit2 class="w-3.5 h-3.5" /> Edit
                    </button>
                    <button
                        @click="cancelBooking(booking.id)"
                        class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/30 rounded-lg transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            </div>

            <div v-if="bookings.length === 0" class="py-10 text-center text-xs text-neutral-400 border border-dashed border-neutral-200 dark:border-neutral-800 rounded-2xl">
                <div class="flex flex-col items-center justify-center space-y-2">
                    <AlertCircle class="w-8 h-8 text-neutral-300 dark:text-neutral-700" />
                    <span>You haven't made any court bookings yet.</span>
                    <Link href="/courts" class="text-emerald-600 font-bold hover:underline">Browse Courts and book &rarr;</Link>
                </div>
            </div>
        </div>

        <!-- Edit Booking Details Modal -->
        <div v-if="isEditModalOpen" class="fixed inset-0 bg-neutral-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-850 rounded-2xl max-w-md w-full p-6 shadow-2xl space-y-4">
                <div>
                    <h3 class="font-black text-neutral-900 dark:text-white text-base tracking-tight">Edit Booking Information</h3>
                    <p class="text-[11px] text-neutral-500">Update details for pending booking: <strong class="font-mono">{{ selectedBooking?.reference_code }}</strong>.</p>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4 text-xs">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-bold mb-1">Customer Name</label>
                            <input
                                v-model="editForm.name"
                                type="text"
                                required
                                class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-bold mb-1">Email Address</label>
                            <input
                                v-model="editForm.email"
                                type="email"
                                required
                                class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-bold mb-1">Contact Phone</label>
                            <input
                                v-model="editForm.phone"
                                type="text"
                                required
                                class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>

                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-bold mb-1">Reservation Notes</label>
                            <textarea
                                v-model="editForm.notes"
                                rows="3"
                                class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                placeholder="Any special requests or instructions..."
                            ></textarea>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2">
                        <button
                            type="button"
                            @click="isEditModalOpen = false"
                            class="px-4 py-2 bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 rounded-xl hover:bg-neutral-200 dark:hover:bg-neutral-750 transition-colors font-bold"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="px-5 py-2 bg-emerald-600 text-white rounded-xl font-bold shadow-lg shadow-emerald-600/10 hover:bg-emerald-700 disabled:opacity-50 transition-colors"
                        >
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>
