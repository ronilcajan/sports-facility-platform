<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    Gift,
    Sparkles,
    Award,
    Ticket,
    CupSoda,
    Shirt,
    Crown,
    Percent,
    Dumbbell,
    Flame,
    Clock,
    CheckCircle,
    Copy,
    Check,
    ChevronRight,
    ArrowUpRight,
    ArrowDownLeft,
    TrendingUp,
    Info,
    X,
    QrCode,
} from '@lucide/vue';
import type { LoyaltySummary } from '@/components/customer/PointsSummaryCard.vue';

export interface RewardItem {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    category: string;
    points_cost: number;
    stock: number | null;
    badge_text: string | null;
    icon: string;
    terms: string | null;
    is_in_stock: boolean;
    can_afford: boolean;
    points_needed: number;
    progress_percent: number;
    venue: { id: number; name: string } | null;
}

export interface CategoryItem {
    id: string;
    label: string;
}

const props = withDefaults(
    defineProps<{
        loyaltySummary?: LoyaltySummary;
        rewards?: RewardItem[];
        categories?: CategoryItem[];
    }>(),
    {
        rewards: () => [],
        categories: () => [
            { id: 'all', label: 'All Rewards' },
            { id: 'drink', label: 'Drinks & Refreshments' },
            { id: 'gear', label: 'Equipment & Gear' },
            { id: 'discount', label: 'Booking Discounts' },
            { id: 'apparel', label: 'Apparel & Merch' },
        ],
        loyaltySummary: () => ({
            available_points: 0,
            lifetime_points: 0,
            tier: {
                name: 'Bronze Member',
                badge_color: 'from-amber-700 to-amber-800',
                next_tier_name: 'Silver Preferred',
                points_to_next: 400,
                progress_percentage: 0,
            },
            active_claims: [],
            recent_transactions: [],
            court_loyalty: [],
            total_claims_count: 0,
        }),
    }
);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Rewards & Points', href: '/customer/rewards' },
        ],
    },
});

const page = usePage();

// URL Query parameter tab initialization
const getInitialTab = () => {
    if (typeof window !== 'undefined') {
        const tab = new URLSearchParams(window.location.search).get('tab');
        if (tab && ['catalog', 'vouchers', 'loyalty', 'history'].includes(tab)) {
            return tab as 'catalog' | 'vouchers' | 'loyalty' | 'history';
        }
    }
    return 'catalog';
};

const activeTab = ref<'catalog' | 'vouchers' | 'loyalty' | 'history'>(getInitialTab());

const selectedCategory = ref<string>('all');
const searchQuery = ref<string>('');

// Claim Modal State
const selectedReward = ref<RewardItem | null>(null);
const isClaimModalOpen = ref(false);
const isClaiming = ref(false);
const claimedVoucher = ref<{
    voucher_code: string;
    reward_name: string;
    points_spent: number;
    expires_at: string;
    terms?: string;
} | null>(null);

const copiedCode = ref<string | null>(null);

// Selected Voucher View Modal
const viewingVoucher = ref<any | null>(null);

// Filtering rewards
const filteredRewards = computed(() => {
    return (props.rewards || []).filter(reward => {
        const matchesCategory = selectedCategory.value === 'all' || reward.category === selectedCategory.value;
        const matchesSearch = !searchQuery.value.trim() ||
            reward.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (reward.description && reward.description.toLowerCase().includes(searchQuery.value.toLowerCase()));
        return matchesCategory && matchesSearch;
    });
});

function getIconComponent(iconName: string) {
    switch (iconName) {
        case 'CupSoda': return CupSoda;
        case 'Shirt': return Shirt;
        case 'Crown': return Crown;
        case 'Percent': return Percent;
        case 'Dumbbell': return Dumbbell;
        case 'Ticket': return Ticket;
        case 'Sparkles': return Sparkles;
        default: return Gift;
    }
}

function openClaimModal(reward: RewardItem) {
    selectedReward.value = reward;
    claimedVoucher.value = null;
    isClaimModalOpen.value = true;
}

function closeClaimModal() {
    isClaimModalOpen.value = false;
    selectedReward.value = null;
    claimedVoucher.value = null;
}

const form = useForm({});

function confirmClaim() {
    if (!selectedReward.value) return;

    isClaiming.value = true;
    form.post(`/customer/rewards/${selectedReward.value.id}/claim`, {
        preserveScroll: true,
        onSuccess: (pageData: any) => {
            isClaiming.value = false;
            const flash = pageData?.props?.flash as any;
            const voucher = pageData?.props?.claimed_voucher || flash?.claimed_voucher;
            if (voucher) {
                claimedVoucher.value = voucher;
            } else {
                closeClaimModal();
            }
        },
        onError: () => {
            isClaiming.value = false;
        },
    });
}

function copyToClipboard(text: string) {
    navigator.clipboard.writeText(text);
    copiedCode.value = text;
    setTimeout(() => {
        if (copiedCode.value === text) {
            copiedCode.value = null;
        }
    }, 2500);
}
</script>

<template>
    <Head title="Rewards & Points Center" />

    <div class="p-6 space-y-6 w-full pb-24 max-w-7xl mx-auto">
        <!-- Hero Loyalty Card -->
        <div class="relative overflow-hidden rounded-3xl border border-emerald-500/20 bg-gradient-to-br from-neutral-900 via-neutral-900 to-emerald-950/60 p-6 md:p-8 shadow-2xl text-white">
            <div class="absolute -right-20 -top-20 w-80 h-80 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -left-20 -bottom-20 w-64 h-64 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-8">
                <!-- Left Details -->
                <div class="space-y-4 max-w-2xl">
                    <div class="flex items-center gap-2.5 flex-wrap">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-xs font-black uppercase tracking-wider">
                            <Sparkles class="w-3.5 h-3.5 animate-pulse" />
                            Member Loyalty Club
                        </span>
                        <span
                            v-if="loyaltySummary.tier"
                            class="inline-flex items-center gap-1 px-3.5 py-1 rounded-full bg-gradient-to-r text-white text-xs font-black shadow-md"
                            :class="loyaltySummary.tier.badge_color"
                        >
                            <Award class="w-3.5 h-3.5" />
                            {{ loyaltySummary.tier.name }}
                        </span>
                    </div>

                    <div>
                        <h1 class="text-3xl md:text-4xl font-black tracking-tight text-white">
                            Points & Freebies Hub
                        </h1>
                        <p class="text-xs md:text-sm text-neutral-400 mt-1 max-w-xl">
                            Earn points every time you reserve a court or venue. Repeat bookings at your favorite courts earn exclusive loyalty multiplier bonuses!
                        </p>
                    </div>

                    <!-- Tier Progression Bar -->
                    <div v-if="loyaltySummary.tier?.next_tier_name" class="space-y-2 pt-2">
                        <div class="flex items-center justify-between text-xs font-bold">
                            <span class="text-neutral-300">
                                Current Tier: <span class="text-white">{{ loyaltySummary.tier.name }}</span>
                            </span>
                            <span class="text-emerald-400">
                                {{ loyaltySummary.tier.points_to_next }} pts to {{ loyaltySummary.tier.next_tier_name }}
                            </span>
                        </div>
                        <div class="w-full bg-neutral-800 rounded-full h-2.5 overflow-hidden border border-neutral-700">
                            <div
                                class="bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-300 h-full rounded-full transition-all duration-700 shadow-sm"
                                :style="{ width: `${loyaltySummary.tier.progress_percentage}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Right Stats Widget -->
                <div class="flex flex-row lg:flex-col items-center lg:items-end gap-6 shrink-0 w-full lg:w-auto justify-between border-t lg:border-t-0 border-neutral-800 pt-4 lg:pt-0">
                    <div class="text-left lg:text-right">
                        <span class="text-[11px] font-extrabold text-emerald-400 uppercase tracking-wider block">Available Balance</span>
                        <div class="flex items-baseline gap-1.5 mt-0.5">
                            <span class="text-4xl md:text-5xl font-black tracking-tight text-white">{{ loyaltySummary.available_points }}</span>
                            <span class="text-xs font-bold text-neutral-400">PTS</span>
                        </div>
                        <span class="text-[11px] text-neutral-400 block mt-0.5">
                            Lifetime Earned: <strong class="text-white">{{ loyaltySummary.lifetime_points }} pts</strong>
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="activeTab = 'vouchers'"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl bg-neutral-800/80 hover:bg-neutral-700 border border-neutral-700 text-xs font-bold text-neutral-200 transition-colors"
                        >
                            <Ticket class="w-4 h-4 text-emerald-400" />
                            <span>{{ loyaltySummary?.active_claims?.length ?? 0 }} Active Vouchers</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="flex items-center gap-2 border-b border-neutral-200 dark:border-neutral-800 pb-2 overflow-x-auto">
            <button
                type="button"
                @click="activeTab = 'catalog'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                :class="[
                    activeTab === 'catalog'
                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                        : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800',
                ]"
            >
                <Gift class="w-4 h-4" />
                <span>Rewards Catalog</span>
                <span class="px-1.5 py-0.5 rounded-full text-[10px] bg-white/20 text-white font-extrabold">
                    {{ (rewards || []).length }}
                </span>
            </button>

            <button
                type="button"
                @click="activeTab = 'vouchers'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                :class="[
                    activeTab === 'vouchers'
                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                        : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800',
                ]"
            >
                <Ticket class="w-4 h-4" />
                <span>My Vouchers</span>
                <span
                    v-if="(loyaltySummary?.active_claims?.length ?? 0) > 0"
                    class="px-1.5 py-0.5 rounded-full text-[10px] bg-amber-400 text-neutral-950 font-extrabold animate-pulse"
                >
                    {{ loyaltySummary?.active_claims?.length ?? 0 }}
                </span>
            </button>

            <button
                type="button"
                @click="activeTab = 'loyalty'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                :class="[
                    activeTab === 'loyalty'
                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                        : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800',
                ]"
            >
                <Flame class="w-4 h-4" />
                <span>Court Loyalty</span>
            </button>

            <button
                type="button"
                @click="activeTab = 'history'"
                class="px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 shrink-0 cursor-pointer"
                :class="[
                    activeTab === 'history'
                        ? 'bg-emerald-600 text-white shadow-md shadow-emerald-600/20'
                        : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800',
                ]"
            >
                <TrendingUp class="w-4 h-4" />
                <span>Points Ledger</span>
            </button>
        </div>

        <!-- ========================================== -->
        <!-- TAB 1: REWARDS CATALOG                    -->
        <!-- ========================================== -->
        <div v-if="activeTab === 'catalog'" class="space-y-6">
            <!-- Filter Bar -->
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4">
                <!-- Category Pills -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
                    <button
                        v-for="cat in categories"
                        :key="cat.id"
                        type="button"
                        @click="selectedCategory = cat.id"
                        class="px-3.5 py-1.5 rounded-full text-xs font-bold transition-all shrink-0 cursor-pointer border"
                        :class="[
                            selectedCategory === cat.id
                                ? 'bg-neutral-900 text-white border-neutral-900 dark:bg-white dark:text-neutral-900 dark:border-white shadow-sm'
                                : 'bg-white dark:bg-neutral-900 text-neutral-600 dark:text-neutral-400 border-neutral-200 dark:border-neutral-800 hover:border-neutral-300 dark:hover:border-neutral-700',
                        ]"
                    >
                        {{ cat.label }}
                    </button>
                </div>

                <!-- Search box -->
                <div class="relative min-w-[220px]">
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search rewards..."
                        class="w-full text-xs rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 px-3.5 py-2 text-neutral-900 dark:text-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500"
                    />
                </div>
            </div>

            <!-- Rewards Cards Grid -->
            <div v-if="filteredRewards.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="reward in filteredRewards"
                    :key="reward.id"
                    class="rounded-2xl border bg-white dark:bg-neutral-900 p-5 shadow-sm transition-all duration-300 flex flex-col justify-between group relative overflow-hidden"
                    :class="[
                        reward.can_afford
                            ? 'border-emerald-500/30 hover:border-emerald-500/60 hover:shadow-lg hover:shadow-emerald-500/5'
                            : 'border-neutral-200 dark:border-neutral-800 opacity-90 hover:opacity-100',
                    ]"
                >
                    <!-- Top Badge & Points requirement -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between gap-2">
                            <span
                                v-if="reward.badge_text"
                                class="px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20"
                            >
                                {{ reward.badge_text }}
                            </span>
                            <span v-else class="text-[10px] text-neutral-400 uppercase font-bold tracking-wider">
                                {{ reward.category }}
                            </span>

                            <div class="flex items-center gap-1 px-2.5 py-1 rounded-xl bg-neutral-100 dark:bg-neutral-800 text-neutral-900 dark:text-white text-xs font-black">
                                <Sparkles class="w-3.5 h-3.5 text-amber-500" />
                                <span>{{ reward.points_cost }}</span>
                                <span class="text-[10px] text-neutral-400">PTS</span>
                            </div>
                        </div>

                        <!-- Icon & Title -->
                        <div class="flex items-start gap-3.5">
                            <div
                                class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 transition-transform group-hover:scale-105"
                                :class="[
                                    reward.can_afford
                                        ? 'bg-gradient-to-br from-emerald-500/20 to-teal-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20'
                                        : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-500 border border-neutral-200 dark:border-neutral-700',
                                ]"
                            >
                                <component :is="getIconComponent(reward.icon)" class="w-6 h-6" />
                            </div>

                            <div class="space-y-1">
                                <h3 class="font-black text-neutral-900 dark:text-white text-sm tracking-tight leading-snug">
                                    {{ reward.name }}
                                </h3>
                                <p class="text-xs text-neutral-500 dark:text-neutral-400 line-clamp-2 leading-relaxed">
                                    {{ reward.description }}
                                </p>
                            </div>
                        </div>

                        <!-- Terms preview -->
                        <div v-if="reward.terms" class="text-[11px] text-neutral-400 flex items-start gap-1 bg-neutral-50 dark:bg-neutral-800/40 p-2.5 rounded-xl border border-neutral-100 dark:border-neutral-800/60">
                            <Info class="w-3.5 h-3.5 text-neutral-400 shrink-0 mt-0.5" />
                            <span class="line-clamp-2">{{ reward.terms }}</span>
                        </div>
                    </div>

                    <!-- Bottom Action & Progress -->
                    <div class="pt-4 border-t border-neutral-100 dark:border-neutral-800 mt-4 space-y-2">
                        <!-- Progress towards reward if user cannot afford -->
                        <div v-if="!reward.can_afford" class="space-y-1">
                            <div class="flex items-center justify-between text-[10px] font-bold text-neutral-400">
                                <span>{{ reward.progress_percent }}% earned</span>
                                <span>{{ reward.points_needed }} pts needed</span>
                            </div>
                            <div class="w-full bg-neutral-200 dark:bg-neutral-800 rounded-full h-1.5 overflow-hidden">
                                <div
                                    class="bg-emerald-500 h-full rounded-full"
                                    :style="{ width: `${reward.progress_percent}%` }"
                                ></div>
                            </div>
                        </div>

                        <!-- Claim Button -->
                        <button
                            type="button"
                            @click="openClaimModal(reward)"
                            :disabled="!reward.can_afford || !reward.is_in_stock"
                            class="w-full py-2.5 px-4 rounded-xl text-xs font-bold transition-all flex items-center justify-center gap-2 cursor-pointer disabled:cursor-not-allowed"
                            :class="[
                                reward.can_afford && reward.is_in_stock
                                    ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/20 hover:scale-[1.01]'
                                    : 'bg-neutral-100 dark:bg-neutral-800 text-neutral-400 border border-neutral-200 dark:border-neutral-700',
                            ]"
                        >
                            <Gift class="w-4 h-4" />
                            <span v-if="!reward.is_in_stock">Out of Stock</span>
                            <span v-else-if="reward.can_afford">Claim Reward</span>
                            <span v-else>Need {{ reward.points_needed }} More Pts</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-16 bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 p-8 space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mx-auto text-neutral-400">
                    <Gift class="w-6 h-6" />
                </div>
                <h4 class="font-black text-neutral-900 dark:text-white text-base">No rewards found</h4>
                <p class="text-xs text-neutral-500 max-w-sm mx-auto">Try selecting another category or clearing your search filter.</p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 2: MY VOUCHERS                        -->
        <!-- ========================================== -->
        <div v-if="activeTab === 'vouchers'" class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-black text-neutral-900 dark:text-white tracking-tight">Claimed Digital Vouchers</h2>
                    <p class="text-xs text-neutral-500">Present these vouchers at the reception counter or check-in desk to redeem your freebies.</p>
                </div>
            </div>

            <div v-if="(loyaltySummary?.active_claims?.length ?? 0) > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div
                    v-for="claim in (loyaltySummary?.active_claims ?? [])"
                    :key="claim.id"
                    class="rounded-3xl border border-emerald-500/20 bg-gradient-to-br from-neutral-900 via-neutral-900 to-emerald-950/40 p-5 shadow-lg text-white relative overflow-hidden flex flex-col justify-between"
                >
                    <!-- Ticket Header -->
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-500/20 border border-emerald-500/30 text-emerald-300 text-[10px] font-extrabold uppercase tracking-wider">
                                <CheckCircle class="w-3 h-3" />
                                Ready to Use
                            </span>
                            <span class="text-[11px] text-neutral-400 font-bold">
                                Expires: {{ claim.expires_at }}
                            </span>
                        </div>

                        <div>
                            <h3 class="text-base font-black text-white tracking-tight">{{ claim.reward_name }}</h3>
                            <p class="text-xs text-neutral-400 mt-0.5">{{ claim.terms || 'Show this voucher code to the staff at the facility desk.' }}</p>
                        </div>
                    </div>

                    <!-- Voucher Code Display & Copy Button -->
                    <div class="mt-5 pt-4 border-t border-dashed border-neutral-700/80 space-y-3">
                        <div class="bg-neutral-950/90 border border-neutral-800 rounded-2xl p-3.5 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] uppercase font-extrabold text-neutral-500 tracking-wider block">Voucher Code</span>
                                <span class="font-mono text-sm md:text-base font-black text-emerald-400 tracking-wider">
                                    {{ claim.voucher_code }}
                                </span>
                            </div>

                            <button
                                type="button"
                                @click="copyToClipboard(claim.voucher_code)"
                                class="px-3 py-1.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-xs font-bold text-neutral-200 transition-colors flex items-center gap-1 cursor-pointer"
                            >
                                <Check v-if="copiedCode === claim.voucher_code" class="w-3.5 h-3.5 text-emerald-400" />
                                <Copy v-else class="w-3.5 h-3.5" />
                                <span>{{ copiedCode === claim.voucher_code ? 'Copied!' : 'Copy' }}</span>
                            </button>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-neutral-400">
                            <span>Spent: <strong>{{ claim.points_spent }} pts</strong></span>
                            <span class="text-emerald-400 font-bold" v-if="claim.expires_in_days !== null">
                                {{ claim.expires_in_days }} days left
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty Vouchers -->
            <div v-else class="text-center py-16 bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 p-8 space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mx-auto text-neutral-400">
                    <Ticket class="w-6 h-6" />
                </div>
                <h4 class="font-black text-neutral-900 dark:text-white text-base">No active vouchers</h4>
                <p class="text-xs text-neutral-500 max-w-sm mx-auto">You have not claimed any vouchers yet. Explore our rewards catalog to redeem your points!</p>
                <button
                    type="button"
                    @click="activeTab = 'catalog'"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5 mt-2 cursor-pointer"
                >
                    <Gift class="w-4 h-4" /> Browse Rewards
                </button>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 3: COURT & LOCATION LOYALTY           -->
        <!-- ========================================== -->
        <div v-if="activeTab === 'loyalty'" class="space-y-6">
            <div>
                <h2 class="text-lg font-black text-neutral-900 dark:text-white tracking-tight">Court & Location Loyalty Multipliers</h2>
                <p class="text-xs text-neutral-500">Every repeated session at a specific court grants you extra bonus points!</p>
            </div>

            <!-- Courts loyalty breakdown -->
            <div v-if="(loyaltySummary.court_loyalty?.length ?? 0) > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div
                    v-for="court in (loyaltySummary.court_loyalty ?? [])"
                    :key="court.court_id"
                    class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm space-y-4"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="space-y-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                {{ court.loyalty_level }}
                            </span>
                            <h3 class="font-black text-neutral-900 dark:text-white text-base tracking-tight">{{ court.court_name }}</h3>
                            <p class="text-xs text-neutral-500">{{ court.venue_name }} • {{ court.sport_type }}</p>
                        </div>

                        <div class="p-2.5 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 shrink-0">
                            <Flame class="w-5 h-5" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3 pt-3 border-t border-neutral-100 dark:border-neutral-800">
                        <div class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/50">
                            <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider block">Reservations</span>
                            <span class="text-lg font-black text-neutral-900 dark:text-white">{{ court.bookings_count }}</span>
                        </div>
                        <div class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/50">
                            <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider block">Points Earned</span>
                            <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">+{{ court.points_earned }}</span>
                        </div>
                    </div>

                    <Link
                        href="/courts"
                        class="w-full py-2 px-3 rounded-xl bg-neutral-100 dark:bg-neutral-800 hover:bg-emerald-600 hover:text-white text-neutral-700 dark:text-neutral-300 text-xs font-bold transition-colors flex items-center justify-center gap-1.5"
                    >
                        <span>Book Again & Earn Bonus</span>
                        <ChevronRight class="w-3.5 h-3.5" />
                    </Link>
                </div>
            </div>

            <div v-else class="text-center py-16 bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 p-8 space-y-3">
                <div class="w-12 h-12 rounded-2xl bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mx-auto text-neutral-400">
                    <Flame class="w-6 h-6" />
                </div>
                <h4 class="font-black text-neutral-900 dark:text-white text-base">No court history yet</h4>
                <p class="text-xs text-neutral-500 max-w-sm mx-auto">Book your first court session to start building your court loyalty multipliers!</p>
                <Link
                    href="/courts"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold transition-colors inline-flex items-center gap-1.5 mt-2"
                >
                    Book a Court
                </Link>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- TAB 4: POINTS ACTIVITY HISTORY / LEDGER    -->
        <!-- ========================================== -->
        <div v-if="activeTab === 'history'" class="space-y-4">
            <div>
                <h2 class="text-lg font-black text-neutral-900 dark:text-white tracking-tight">Points Activity Ledger</h2>
                <p class="text-xs text-neutral-500">Full audit trail of points earned from court bookings and deducted for reward redemptions.</p>
            </div>

            <div v-if="(loyaltySummary?.recent_transactions?.length ?? 0) > 0" class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 overflow-hidden shadow-sm">
                <div class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    <div
                        v-for="tx in (loyaltySummary?.recent_transactions ?? [])"
                        :key="tx.id"
                        class="p-4 flex items-center justify-between gap-4 hover:bg-neutral-50/50 dark:hover:bg-neutral-800/30 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                :class="[
                                    tx.is_positive
                                        ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                        : 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
                                ]"
                            >
                                <ArrowDownLeft v-if="tx.is_positive" class="w-5 h-5" />
                                <ArrowUpRight v-else class="w-5 h-5" />
                            </div>

                            <div class="space-y-0.5">
                                <p class="text-xs font-bold text-neutral-900 dark:text-white">{{ tx.description }}</p>
                                <p class="text-[11px] text-neutral-400">{{ tx.date }}</p>
                            </div>
                        </div>

                        <div class="text-right shrink-0">
                            <span
                                class="text-sm font-black tracking-tight"
                                :class="[
                                    tx.is_positive
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-neutral-900 dark:text-white',
                                ]"
                            >
                                {{ tx.is_positive ? `+${tx.points}` : tx.points }} pts
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-16 bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 p-8 space-y-2">
                <p class="text-xs text-neutral-500">No point transactions recorded yet.</p>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- CLAIM MODAL / CELEBRATION MODAL            -->
        <!-- ========================================== -->
        <div
            v-if="isClaimModalOpen && selectedReward"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-950/75 backdrop-blur-sm animate-fade-in"
        >
            <div class="relative w-full max-w-md bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 shadow-2xl p-6 overflow-hidden">
                <!-- Close button -->
                <button
                    type="button"
                    @click="closeClaimModal"
                    class="absolute top-4 right-4 p-2 rounded-full text-neutral-400 hover:text-neutral-600 dark:hover:text-white hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors cursor-pointer"
                >
                    <X class="w-4 h-4" />
                </button>

                <!-- 1. Post-Claim Celebration State -->
                <div v-if="claimedVoucher" class="text-center space-y-5 py-2">
                    <div class="w-16 h-16 rounded-full bg-gradient-to-tr from-emerald-500 to-teal-400 text-white flex items-center justify-center mx-auto shadow-lg shadow-emerald-500/30 animate-bounce">
                        <Gift class="w-8 h-8" />
                    </div>

                    <div>
                        <span class="text-xs font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Reward Unlocked!</span>
                        <h3 class="text-xl font-black text-neutral-900 dark:text-white tracking-tight mt-1">
                            {{ claimedVoucher.reward_name }}
                        </h3>
                        <p class="text-xs text-neutral-500 mt-1">Your digital voucher is active and ready to use!</p>
                    </div>

                    <!-- Digital Voucher Box -->
                    <div class="p-4 rounded-2xl bg-neutral-950 border border-neutral-800 text-white space-y-3 text-left">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Redemption Code</span>
                            <span class="text-[10px] font-bold text-emerald-400">Valid for 30 Days</span>
                        </div>

                        <div class="flex items-center justify-between bg-neutral-900 p-3 rounded-xl border border-neutral-800">
                            <span class="font-mono text-base md:text-lg font-black text-emerald-400 tracking-wider">
                                {{ claimedVoucher.voucher_code }}
                            </span>
                            <button
                                type="button"
                                @click="copyToClipboard(claimedVoucher.voucher_code)"
                                class="px-3 py-1 rounded-lg bg-neutral-800 hover:bg-neutral-700 text-xs font-bold transition-colors flex items-center gap-1 cursor-pointer"
                            >
                                <Check v-if="copiedCode === claimedVoucher.voucher_code" class="w-3.5 h-3.5 text-emerald-400" />
                                <Copy v-else class="w-3.5 h-3.5" />
                                <span>{{ copiedCode === claimedVoucher.voucher_code ? 'Copied' : 'Copy' }}</span>
                            </button>
                        </div>

                        <p class="text-[11px] text-neutral-400">
                            {{ claimedVoucher.terms || 'Present this code at the facility counter before or after your court session.' }}
                        </p>
                    </div>

                    <div class="flex items-center gap-2 pt-2">
                        <button
                            type="button"
                            @click="closeClaimModal(); activeTab = 'vouchers'"
                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold transition-colors cursor-pointer shadow-md"
                        >
                            View My Vouchers
                        </button>
                    </div>
                </div>

                <!-- 2. Pre-Claim Confirmation State -->
                <div v-else class="space-y-5">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                            <component :is="getIconComponent(selectedReward.icon)" class="w-6 h-6" />
                        </div>
                        <div>
                            <span class="text-[10px] font-black text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">Confirm Claim</span>
                            <h3 class="text-base font-black text-neutral-900 dark:text-white tracking-tight">
                                {{ selectedReward.name }}
                            </h3>
                        </div>
                    </div>

                    <p class="text-xs text-neutral-500 dark:text-neutral-400 leading-relaxed">
                        {{ selectedReward.description }}
                    </p>

                    <!-- Points breakdown calculation -->
                    <div class="p-4 rounded-2xl bg-neutral-50 dark:bg-neutral-800/60 border border-neutral-100 dark:border-neutral-800 space-y-2 text-xs">
                        <div class="flex items-center justify-between text-neutral-600 dark:text-neutral-400">
                            <span>Your current balance:</span>
                            <span class="font-bold text-neutral-900 dark:text-white">{{ loyaltySummary.available_points }} pts</span>
                        </div>
                        <div class="flex items-center justify-between text-amber-600 dark:text-amber-400 font-medium">
                            <span>Points cost:</span>
                            <span class="font-bold">-{{ selectedReward.points_cost }} pts</span>
                        </div>
                        <div class="border-t border-neutral-200 dark:border-neutral-700 pt-2 flex items-center justify-between font-black text-neutral-900 dark:text-white">
                            <span>Remaining balance:</span>
                            <span class="text-emerald-600 dark:text-emerald-400">
                                {{ loyaltySummary.available_points - selectedReward.points_cost }} pts
                            </span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <button
                            type="button"
                            @click="closeClaimModal"
                            class="flex-1 py-2.5 px-4 rounded-xl border border-neutral-200 dark:border-neutral-700 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 text-xs font-bold transition-colors cursor-pointer"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            @click="confirmClaim"
                            :disabled="isClaiming"
                            class="flex-1 py-2.5 px-4 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-black shadow-md shadow-emerald-600/20 transition-all flex items-center justify-center gap-1.5 cursor-pointer disabled:opacity-50"
                        >
                            <Sparkles class="w-4 h-4" />
                            <span>{{ isClaiming ? 'Claiming...' : 'Confirm & Claim' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
