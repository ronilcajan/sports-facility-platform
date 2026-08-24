<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    User,
    CalendarDays,
    Sparkles,
    Award,
    Ticket,
    ArrowDownLeft,
    ArrowUpRight,
    TrendingUp,
    Plus,
    X,
    CheckCircle,
} from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

interface UserDetail {
    id: number;
    name: string;
    email: string;
    roles: string[];
    created_at: string;
}

interface Booking {
    id: number;
    date: string;
    time_slots: string[];
    total_price: string;
    status: string;
    court?: { id: number; name: string };
}

interface PaginatedBookings {
    data: Booking[];
}

interface ClaimItem {
    id: number;
    voucher_code: string;
    reward_name: string;
    points_spent: number;
    status: string;
    expires_at: string | null;
    created_at: string;
}

interface LoyaltySummary {
    available_points: number;
    lifetime_points: number;
    tier: {
        name: string;
        badge_color: string;
        next_tier_name: string | null;
        points_to_next: number;
        progress_percentage: number;
    };
    recent_transactions: Array<{
        id: number;
        points: number;
        type: string;
        description: string;
        date: string;
        is_positive: boolean;
    }>;
    total_claims_count: number;
}

const props = defineProps<{
    user: UserDetail;
    bookings: PaginatedBookings;
    loyaltySummary?: LoyaltySummary;
    claims?: ClaimItem[];
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Super Admin Overview', href: '/admin/dashboard' },
        { title: 'User Accounts', href: '/admin/users' },
        { title: props.user.name, href: `/admin/users/${props.user.id}` },
    ],
});

const showPointsModal = ref(false);

const pointsForm = useForm({
    action: 'add' as 'add' | 'deduct',
    amount: 50,
    reason: '',
});

function openPointsModal() {
    pointsForm.reset();
    pointsForm.action = 'add';
    pointsForm.amount = 50;
    pointsForm.reason = '';
    pointsForm.clearErrors();
    showPointsModal.value = true;
}

function submitPoints() {
    pointsForm.post(`/admin/users/${props.user.id}/adjust-points`, {
        onSuccess: () => {
            showPointsModal.value = false;
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <Head :title="`${user.name} - User Profile & History`" />

    <div class="p-6 space-y-6 w-full pb-16">
        <Link href="/admin/users" class="text-xs text-neutral-500 hover:text-neutral-900 dark:hover:text-white flex items-center gap-1 cursor-pointer">
            <ArrowLeft class="w-4 h-4" /> Back to Users
        </Link>

        <!-- Profile Summary Card -->
        <div class="p-6 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span v-for="r in user.roles" :key="r" class="px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 text-xs font-bold capitalize">
                        {{ r }}
                    </span>
                    <span
                        v-if="loyaltySummary?.tier"
                        class="px-2.5 py-0.5 rounded-full text-white text-xs font-bold bg-gradient-to-r"
                        :class="loyaltySummary.tier.badge_color"
                    >
                        {{ loyaltySummary.tier.name }}
                    </span>
                </div>
                <h1 class="text-2xl font-black text-neutral-900 dark:text-white tracking-tight">{{ user.name }}</h1>
                <p class="text-xs text-neutral-500">{{ user.email }} &bull; Registered {{ user.created_at }}</p>
            </div>

            <button
                type="button"
                @click="openPointsModal"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md shadow-emerald-600/20 transition-all flex items-center gap-1.5 cursor-pointer shrink-0"
            >
                <Sparkles class="w-4 h-4" /> Adjust Loyalty Points
            </button>
        </div>

        <!-- Loyalty Points Overview Grid -->
        <div v-if="loyaltySummary" class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-5 rounded-2xl border border-emerald-500/20 bg-gradient-to-br from-emerald-500/10 to-teal-500/5 dark:bg-neutral-900 shadow-sm">
                <span class="text-[11px] font-bold text-neutral-500 dark:text-neutral-400 uppercase tracking-wider block">Available Balance</span>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl font-black text-emerald-600 dark:text-emerald-400">{{ loyaltySummary.available_points }}</span>
                    <span class="text-xs font-bold text-neutral-400">PTS</span>
                </div>
                <span class="text-[10px] text-neutral-500 mt-1 block">Current spendable points</span>
            </div>

            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Lifetime Earned</span>
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl font-black text-neutral-900 dark:text-white">{{ loyaltySummary.lifetime_points }}</span>
                    <span class="text-xs font-bold text-neutral-400">PTS</span>
                </div>
                <span class="text-[10px] text-neutral-500 mt-1 block">All-time booking points</span>
            </div>

            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Loyalty Rank</span>
                <span class="text-lg font-black text-neutral-900 dark:text-white mt-1 block">{{ loyaltySummary.tier.name }}</span>
                <span class="text-[10px] text-emerald-600 font-bold mt-1 block" v-if="loyaltySummary.tier.next_tier_name">
                    {{ loyaltySummary.tier.points_to_next }} pts to {{ loyaltySummary.tier.next_tier_name }}
                </span>
                <span class="text-[10px] text-neutral-400 mt-1 block" v-else>Top tier reached</span>
            </div>

            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Claimed Freebies</span>
                <span class="text-3xl font-black text-amber-600 mt-1 block">{{ loyaltySummary.total_claims_count }}</span>
                <span class="text-[10px] text-neutral-500 mt-1 block">Total vouchers claimed</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Points Transaction History Ledger -->
            <div v-if="loyaltySummary" class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                    <h3 class="font-bold text-sm text-neutral-900 dark:text-white flex items-center gap-2">
                        <TrendingUp class="w-4 h-4 text-emerald-600" /> Points Activity Ledger
                    </h3>
                    <button
                        type="button"
                        @click="openPointsModal"
                        class="text-[11px] font-bold text-emerald-600 hover:text-emerald-700 cursor-pointer"
                    >
                        + Adjust Points
                    </button>
                </div>

                <div class="divide-y divide-neutral-100 dark:divide-neutral-800 max-h-80 overflow-y-auto">
                    <div
                        v-for="tx in loyaltySummary.recent_transactions"
                        :key="tx.id"
                        class="py-2.5 flex items-center justify-between gap-3 text-xs"
                    >
                        <div class="flex items-center gap-2.5">
                            <div
                                class="w-7 h-7 rounded-lg flex items-center justify-center shrink-0"
                                :class="[
                                    tx.is_positive
                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                        : 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
                                ]"
                            >
                                <ArrowDownLeft v-if="tx.is_positive" class="w-4 h-4" />
                                <ArrowUpRight v-else class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="font-semibold text-neutral-900 dark:text-white leading-tight">{{ tx.description }}</p>
                                <p class="text-[10px] text-neutral-400">{{ tx.date }}</p>
                            </div>
                        </div>

                        <span
                            class="font-black shrink-0"
                            :class="[
                                tx.is_positive ? 'text-emerald-600 dark:text-emerald-400' : 'text-neutral-900 dark:text-white',
                            ]"
                        >
                            {{ tx.is_positive ? `+${tx.points}` : tx.points }} pts
                        </span>
                    </div>

                    <div v-if="loyaltySummary.recent_transactions.length === 0" class="py-6 text-center text-xs text-neutral-400">
                        No points history recorded yet.
                    </div>
                </div>
            </div>

            <!-- Claimed Vouchers -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-3">
                <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                    <h3 class="font-bold text-sm text-neutral-900 dark:text-white flex items-center gap-2">
                        <Ticket class="w-4 h-4 text-amber-500" /> Claimed Digital Vouchers
                    </h3>
                </div>

                <div class="divide-y divide-neutral-100 dark:divide-neutral-800 max-h-80 overflow-y-auto">
                    <div
                        v-for="c in claims"
                        :key="c.id"
                        class="py-2.5 flex items-center justify-between gap-3 text-xs"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-neutral-900 dark:text-white">{{ c.reward_name }}</span>
                                <span
                                    class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase"
                                    :class="[
                                        c.status === 'active'
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400',
                                    ]"
                                >
                                    {{ c.status }}
                                </span>
                            </div>
                            <p class="font-mono text-[11px] text-emerald-600 dark:text-emerald-400 font-bold mt-0.5">{{ c.voucher_code }}</p>
                        </div>

                        <div class="text-right shrink-0">
                            <span class="text-[11px] text-neutral-400 font-bold block">{{ c.points_spent }} pts</span>
                            <span class="text-[10px] text-neutral-400">{{ c.created_at }}</span>
                        </div>
                    </div>

                    <div v-if="!claims || claims.length === 0" class="py-6 text-center text-xs text-neutral-400">
                        No vouchers claimed yet.
                    </div>
                </div>
            </div>
        </div>

        <!-- Booking History Section -->
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                <h3 class="font-bold text-sm text-neutral-900 dark:text-white flex items-center gap-2">
                    <CalendarDays class="w-4 h-4 text-emerald-600" /> Customer Booking History
                </h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-semibold uppercase">
                            <th class="py-2.5 px-3">Booking ID</th>
                            <th class="py-2.5 px-3">Court</th>
                            <th class="py-2.5 px-3">Date</th>
                            <th class="py-2.5 px-3">Time Slots</th>
                            <th class="py-2.5 px-3">Total Amount</th>
                            <th class="py-2.5 px-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        <tr v-for="b in bookings.data" :key="b.id" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40">
                            <td class="py-3 px-3 font-mono font-bold">#{{ b.id }}</td>
                            <td class="py-3 px-3 font-medium text-neutral-900 dark:text-white">{{ b.court?.name || 'N/A' }}</td>
                            <td class="py-3 px-3 text-neutral-600 dark:text-neutral-300">{{ b.date }}</td>
                            <td class="py-3 px-3 font-mono text-[11px] text-neutral-500">{{ b.time_slots ? b.time_slots.join(', ') : '' }}</td>
                            <td class="py-3 px-3 font-bold text-emerald-600">₱{{ b.total_price }}</td>
                            <td class="py-3 px-3 font-semibold capitalize">{{ b.status }}</td>
                        </tr>

                        <tr v-if="bookings.data.length === 0">
                            <td colspan="6" class="py-8 text-center text-xs text-neutral-400">No booking history recorded for this user.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Adjust Points Modal -->
    <Teleport to="body">
        <div v-if="showPointsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @click.self="showPointsModal = false">
            <div class="w-full max-w-md rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600">
                            <Sparkles class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-base font-black text-neutral-900 dark:text-white">Adjust Loyalty Points</h2>
                            <p class="text-xs text-neutral-500">{{ user.name }} (Current: {{ loyaltySummary?.available_points ?? 0 }} pts)</p>
                        </div>
                    </div>
                    <button @click="showPointsModal = false" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-white cursor-pointer">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitPoints" class="space-y-4">
                    <div class="space-y-1.5">
                        <Label>Adjustment Action</Label>
                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="pointsForm.action = 'add'"
                                class="py-2 px-3 rounded-xl border text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1.5"
                                :class="[
                                    pointsForm.action === 'add'
                                        ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm'
                                        : 'bg-neutral-50 dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300',
                                ]"
                            >
                                <span>+ Add Bonus Points</span>
                            </button>
                            <button
                                type="button"
                                @click="pointsForm.action = 'deduct'"
                                class="py-2 px-3 rounded-xl border text-xs font-bold transition-all cursor-pointer flex items-center justify-center gap-1.5"
                                :class="[
                                    pointsForm.action === 'deduct'
                                        ? 'bg-rose-600 text-white border-rose-600 shadow-sm'
                                        : 'bg-neutral-50 dark:bg-neutral-800 border-neutral-200 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300',
                                ]"
                            >
                                <span>- Deduct Points</span>
                            </button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="points-amount-show">Points Amount</Label>
                        <Input id="points-amount-show" v-model.number="pointsForm.amount" type="number" min="1" max="50000" required />
                        <InputError :message="pointsForm.errors.amount" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="points-reason-show">Reason / Memo (Required for Audit Trail)</Label>
                        <Input id="points-reason-show" v-model="pointsForm.reason" placeholder="e.g. Loyalty promotion bonus, special event reward, etc." required />
                        <InputError :message="pointsForm.errors.reason" />
                    </div>

                    <div class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-100 dark:border-neutral-800 text-xs flex items-center justify-between font-bold">
                        <span class="text-neutral-500">Calculated New Balance:</span>
                        <span class="text-emerald-600 dark:text-emerald-400">
                            {{ pointsForm.action === 'add' ? ((loyaltySummary?.available_points ?? 0) + (pointsForm.amount || 0)) : ((loyaltySummary?.available_points ?? 0) - (pointsForm.amount || 0)) }} pts
                        </span>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t border-neutral-100 dark:border-neutral-800">
                        <Button variant="outline" type="button" @click="showPointsModal = false">Cancel</Button>
                        <Button type="submit" :disabled="pointsForm.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                            {{ pointsForm.processing ? 'Adjusting...' : 'Save Points' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
