<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    X,
    CheckCircle,
    XCircle,
    User,
    Mail,
    Phone,
    Calendar,
    Clock,
    DollarSign,
    Dumbbell,
    Building2,
    FileText,
    MessageSquare,
    ShieldCheck,
} from '@lucide/vue';

export interface BookingDetail {
    id: number;
    reference_code?: string;
    customer_name?: string;
    name?: string;
    email: string;
    phone: string;
    court_name?: string;
    court?: { id: number; name: string; sport_type?: string } | null;
    venue_name?: string;
    venue?: { id: number; name: string } | null;
    sport_type?: string;
    date: string;
    time_slots: string[];
    total_price: string;
    receipt_url?: string | null;
    receipt_path?: string | null;
    status: string;
    notes?: string | null;
    created_at?: string;
}

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        booking: BookingDetail | null;
        updateRoutePrefix?: string;
        canUpdate?: boolean;
    }>(),
    {
        updateRoutePrefix: '/admin/bookings',
        canUpdate: true,
    }
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const actionForm = useForm({
    status: '',
});

const displayName = computed(() => {
    if (!props.booking) return '';
    return props.booking.customer_name || props.booking.name || 'Anonymous Customer';
});

const displayCourt = computed(() => {
    if (!props.booking) return 'N/A';
    if (props.booking.court_name) return props.booking.court_name;
    if (props.booking.court?.name) return props.booking.court.name;
    return 'Unassigned Court';
});

const displayVenue = computed(() => {
    if (!props.booking) return '';
    if (props.booking.venue_name) return props.booking.venue_name;
    if (props.booking.venue?.name) return props.booking.venue.name;
    return '';
});

const displaySport = computed(() => {
    if (!props.booking) return '';
    if (props.booking.sport_type) return props.booking.sport_type;
    if (props.booking.court?.sport_type) return props.booking.court.sport_type;
    return '';
});

const receiptLink = computed(() => {
    if (!props.booking) return null;
    if (props.booking.receipt_url) return props.booking.receipt_url;
    if (props.booking.receipt_path) return `/storage/${props.booking.receipt_path}`;
    return null;
});

const formattedReference = computed(() => {
    if (!props.booking) return '';
    if (props.booking.reference_code) return props.booking.reference_code;
    return `DY-RESRV-${String(props.booking.id).padStart(6, '0')}`;
});

function updateStatus(newStatus: string) {
    if (!props.booking || !props.canUpdate) return;
    actionForm.status = newStatus;
    actionForm.patch(`${props.updateRoutePrefix}/${props.booking.id}/status`, {
        preserveScroll: true,
        onSuccess: () => {
            emit('close');
        },
    });
}

function statusClasses(s: string): string {
    if (s === 'approved' || s === 'confirmed')
        return 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-800';
    if (s === 'pending')
        return 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950/60 dark:text-amber-300 dark:border-amber-800';
    if (s === 'completed')
        return 'bg-neutral-200 text-neutral-800 border-neutral-300 dark:bg-neutral-800 dark:text-neutral-300 dark:border-neutral-700';
    return 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950/60 dark:text-rose-300 dark:border-rose-800';
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen && booking"
                class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto bg-neutral-900/70 p-4 backdrop-blur-sm"
                @click.self="emit('close')"
            >
                <div
                    class="relative w-full max-w-lg overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-2xl transition-all"
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 px-6 py-4 bg-neutral-50/50 dark:bg-neutral-800/40"
                    >
                        <div class="space-y-0.5">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                    {{ formattedReference }}
                                </span>
                                <span
                                    :class="[
                                        'px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider border',
                                        statusClasses(booking.status),
                                    ]"
                                >
                                    {{ booking.status }}
                                </span>
                            </div>
                            <h3 class="text-lg font-black tracking-tight text-neutral-900 dark:text-white">
                                Customer Reservation Details
                            </h3>
                        </div>

                        <button
                            type="button"
                            @click="emit('close')"
                            class="rounded-full p-1.5 text-neutral-400 hover:bg-neutral-200 dark:hover:bg-neutral-800 hover:text-neutral-900 dark:hover:text-white transition-colors cursor-pointer"
                        >
                            <X class="size-5" />
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-5 max-h-[75vh] overflow-y-auto">
                        <!-- Customer Information Card -->
                        <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4 space-y-3 shadow-sm">
                            <div class="flex items-center gap-2 border-b border-neutral-100 dark:border-neutral-800 pb-2">
                                <User class="size-4 text-emerald-600" />
                                <h4 class="text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                    Customer Information
                                </h4>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                <div>
                                    <span class="block text-[10px] font-semibold text-neutral-400 uppercase">Full Name</span>
                                    <span class="font-bold text-neutral-900 dark:text-white text-sm">{{ displayName }}</span>
                                </div>
                                <div>
                                    <span class="block text-[10px] font-semibold text-neutral-400 uppercase">Phone Number</span>
                                    <span class="font-semibold text-neutral-800 dark:text-neutral-200 flex items-center gap-1">
                                        <Phone class="size-3 text-neutral-400" />
                                        {{ booking.phone || 'N/A' }}
                                    </span>
                                </div>
                                <div class="col-span-full">
                                    <span class="block text-[10px] font-semibold text-neutral-400 uppercase">Email Address</span>
                                    <span class="font-semibold text-neutral-800 dark:text-neutral-200 flex items-center gap-1 truncate">
                                        <Mail class="size-3 text-neutral-400 shrink-0" />
                                        {{ booking.email || 'N/A' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Reservation Details Card -->
                        <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4 space-y-3 shadow-sm">
                            <div class="flex items-center gap-2 border-b border-neutral-100 dark:border-neutral-800 pb-2">
                                <Calendar class="size-4 text-emerald-600" />
                                <h4 class="text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                    Facility & Schedule Reservation
                                </h4>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div v-if="displayVenue" class="col-span-2">
                                    <span class="block text-[10px] font-semibold text-neutral-400 uppercase">Venue Facility</span>
                                    <span class="font-bold text-neutral-900 dark:text-white flex items-center gap-1.5">
                                        <Building2 class="size-3.5 text-emerald-600" />
                                        {{ displayVenue }}
                                    </span>
                                </div>

                                <div>
                                    <span class="block text-[10px] font-semibold text-neutral-400 uppercase">Reserved Court</span>
                                    <span class="font-bold text-neutral-900 dark:text-white flex items-center gap-1.5">
                                        <Dumbbell class="size-3.5 text-emerald-600" />
                                        {{ displayCourt }}
                                        <span v-if="displaySport" class="text-[10px] text-neutral-400 font-normal">({{ displaySport }})</span>
                                    </span>
                                </div>

                                <div>
                                    <span class="block text-[10px] font-semibold text-neutral-400 uppercase">Booking Date</span>
                                    <span class="font-bold text-neutral-900 dark:text-white flex items-center gap-1.5">
                                        <Calendar class="size-3.5 text-emerald-600" />
                                        {{ booking.date }}
                                    </span>
                                </div>

                                <div class="col-span-2">
                                    <span class="block text-[10px] font-semibold text-neutral-400 uppercase">Reserved Time Slots</span>
                                    <div class="mt-1 flex flex-wrap gap-1.5">
                                        <span
                                            v-for="slot in booking.time_slots"
                                            :key="slot"
                                            class="rounded-lg bg-neutral-100 dark:bg-neutral-800 px-2.5 py-1 font-mono text-xs font-bold text-neutral-900 dark:text-white border border-neutral-200 dark:border-neutral-700"
                                        >
                                            {{ slot }}
                                        </span>
                                    </div>
                                </div>

                                <div class="col-span-2 flex items-center justify-between rounded-xl bg-emerald-50 dark:bg-emerald-950/40 p-3 border border-emerald-200 dark:border-emerald-900/60 mt-1">
                                    <div>
                                        <span class="block text-[10px] font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">Total Booking Amount</span>
                                        <span class="text-xs text-neutral-500 font-medium">{{ booking.time_slots ? booking.time_slots.length : 1 }} slot(s) reserved</span>
                                    </div>
                                    <span class="text-xl font-black text-emerald-600 dark:text-emerald-400">
                                        ₱{{ booking.total_price }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Special Requests / Customer Notes -->
                        <div v-if="booking.notes" class="rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4 space-y-1.5 shadow-sm">
                            <div class="flex items-center gap-2">
                                <MessageSquare class="size-4 text-emerald-600" />
                                <h4 class="text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                    Customer Notes / Requests
                                </h4>
                            </div>
                            <p class="text-xs leading-relaxed text-neutral-700 dark:text-neutral-300 bg-neutral-50 dark:bg-neutral-800/50 p-2.5 rounded-lg border border-neutral-100 dark:border-neutral-800 italic">
                                "{{ booking.notes }}"
                            </p>
                        </div>

                        <!-- Payment Receipt Upload -->
                        <div class="rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4 space-y-3 shadow-sm">
                            <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-2">
                                <div class="flex items-center gap-2">
                                    <FileText class="size-4 text-emerald-600" />
                                    <h4 class="text-xs font-bold text-neutral-500 uppercase tracking-wider">
                                        Payment Proof Receipt
                                    </h4>
                                </div>
                                <a
                                    v-if="receiptLink"
                                    :href="receiptLink"
                                    target="_blank"
                                    class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1"
                                >
                                    View Full Image &rarr;
                                </a>
                            </div>

                            <div v-if="receiptLink" class="relative overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800 bg-neutral-900 group aspect-[16/9] max-h-48">
                                <img
                                    :src="receiptLink"
                                    alt="Payment receipt proof"
                                    class="h-full w-full object-contain"
                                />
                            </div>
                            <div v-else class="rounded-xl border border-dashed border-neutral-300 dark:border-neutral-700 p-4 text-center text-xs text-neutral-400">
                                No payment receipt uploaded for this booking.
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Buttons (Confirm / Reject) -->
                    <div
                        class="flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-neutral-100 dark:border-neutral-800 px-6 py-4 bg-neutral-50/50 dark:bg-neutral-800/40"
                    >
                        <button
                            type="button"
                            @click="emit('close')"
                            class="w-full sm:w-auto rounded-xl border border-neutral-300 dark:border-neutral-700 px-4 py-2 text-xs font-bold text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors cursor-pointer"
                        >
                            Close Details
                        </button>

                        <div v-if="canUpdate !== false" class="flex w-full sm:w-auto items-center gap-2">
                            <!-- Confirm / Approve Button -->
                            <button
                                type="button"
                                @click="updateStatus('approved')"
                                :disabled="actionForm.processing"
                                class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-xl bg-emerald-600 px-5 py-2 text-xs font-extrabold text-white shadow-md shadow-emerald-600/20 hover:bg-emerald-700 active:scale-95 disabled:opacity-50 transition-all cursor-pointer"
                                title="Confirm this booking reservation"
                            >
                                <CheckCircle class="size-4" />
                                <span>Confirm Booking</span>
                            </button>

                            <!-- Reject Button -->
                            <button
                                type="button"
                                @click="updateStatus('rejected')"
                                :disabled="actionForm.processing"
                                class="flex-1 sm:flex-initial inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-300 dark:border-rose-900 bg-rose-50 dark:bg-rose-950/40 px-5 py-2 text-xs font-extrabold text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/60 active:scale-95 disabled:opacity-50 transition-all cursor-pointer"
                                title="Reject this booking reservation"
                            >
                                <XCircle class="size-4" />
                                <span>Reject Booking</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
