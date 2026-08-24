<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import {
    Sparkles,
    Gift,
    Award,
    ChevronRight,
    MapPin,
    Flame,
    Ticket,
    TrendingUp,
} from '@lucide/vue';

export interface LoyaltyTier {
    name: string;
    badge_color: string;
    next_tier_name: string | null;
    points_to_next: number;
    progress_percentage: number;
    perks?: string[];
}

export interface LoyaltySummary {
    available_points: number;
    lifetime_points: number;
    tier: LoyaltyTier;
    court_loyalty?: Array<{
        court_id: number;
        court_name: string;
        sport_type: string;
        venue_name: string;
        bookings_count: number;
        points_earned: number;
        loyalty_level: string;
    }>;
    venue_loyalty?: Array<{
        venue_id: number;
        venue_name: string;
        venue_address?: string;
        bookings_count: number;
        points_earned: number;
    }>;
    active_claims: Array<{
        id: number;
        voucher_code: string;
        reward_name: string;
        reward_category: string;
        points_spent: number;
        expires_at: string;
        expires_in_days: number | null;
        terms?: string | null;
    }>;
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
    loyalty?: LoyaltySummary;
}>();

const availablePoints = computed(() => props.loyalty?.available_points ?? 0);
const tier = computed(() => props.loyalty?.tier);
const activeClaimsCount = computed(() => props.loyalty?.active_claims?.length ?? 0);
const topCourt = computed(() => props.loyalty?.court_loyalty?.[0]);
</script>

<template>
    <div class="relative overflow-hidden rounded-2xl border border-emerald-500/20 bg-gradient-to-br from-emerald-950/40 via-neutral-900 to-neutral-950 p-6 shadow-xl text-white">
        <!-- Ambient Decorative Glows -->
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 -bottom-16 w-48 h-48 bg-teal-500/10 rounded-full blur-2xl pointer-events-none"></div>

        <div class="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
            <!-- Left: Points & Tier Info -->
            <div class="space-y-4 max-w-xl">
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-black tracking-wide uppercase">
                        <Sparkles class="w-3.5 h-3.5 animate-pulse" />
                        Loyalty Rewards
                    </span>
                    <span
                        v-if="tier"
                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-gradient-to-r text-white text-xs font-black shadow-sm"
                        :class="tier.badge_color"
                    >
                        <Award class="w-3.5 h-3.5" />
                        {{ tier.name }}
                    </span>
                    <span
                        v-if="activeClaimsCount > 0"
                        class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-500/20 border border-amber-500/30 text-amber-300 text-[11px] font-bold"
                    >
                        <Ticket class="w-3 h-3" />
                        {{ activeClaimsCount }} Active {{ activeClaimsCount === 1 ? 'Voucher' : 'Vouchers' }}
                    </span>
                </div>

                <div>
                    <div class="flex items-baseline gap-2">
                        <span class="text-4xl lg:text-5xl font-black tracking-tight text-white">
                            {{ availablePoints }}
                        </span>
                        <span class="text-emerald-400 font-extrabold text-sm uppercase tracking-wider">
                            Points Available
                        </span>
                    </div>
                    <p class="text-xs text-neutral-400 mt-1">
                        Earn +50 pts per booking plus repeat court loyalty bonuses every time you play.
                    </p>
                </div>

                <!-- Tier Progression Bar -->
                <div v-if="tier && tier.next_tier_name" class="space-y-1.5 pt-1">
                    <div class="flex items-center justify-between text-[11px] font-bold">
                        <span class="text-neutral-400">
                            Progress to <span class="text-white">{{ tier.next_tier_name }}</span>
                        </span>
                        <span class="text-emerald-400">{{ tier.points_to_next }} pts to unlock</span>
                    </div>
                    <div class="w-full bg-neutral-800/80 rounded-full h-2 overflow-hidden border border-neutral-700/50">
                        <div
                            class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-700"
                            :style="{ width: `${tier.progress_percentage}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Middle/Right: Court Loyalty Spotlight & Actions -->
            <div class="flex flex-col sm:flex-row lg:flex-col items-stretch gap-3 w-full lg:w-auto shrink-0">
                <!-- Top Court Loyalty Badge -->
                <div
                    v-if="topCourt"
                    class="p-3.5 rounded-xl bg-neutral-800/60 border border-neutral-700/40 flex items-center gap-3 backdrop-blur-sm"
                >
                    <div class="w-9 h-9 rounded-lg bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0">
                        <Flame class="w-5 h-5" />
                    </div>
                    <div class="text-left overflow-hidden">
                        <div class="flex items-center gap-1.5">
                            <span class="text-[10px] font-bold text-neutral-400 uppercase tracking-wider">Favorite Venue</span>
                            <span class="text-[9px] px-1.5 py-0.2 rounded bg-emerald-500/20 text-emerald-300 font-extrabold">{{ topCourt.loyalty_level }}</span>
                        </div>
                        <p class="text-xs font-bold text-white truncate">{{ topCourt.court_name }}</p>
                        <p class="text-[10px] text-neutral-400">{{ topCourt.bookings_count }} bookings • +{{ topCourt.points_earned }} pts earned</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-2">
                    <Link
                        href="/customer/rewards"
                        class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-600 text-white text-xs font-black shadow-lg shadow-emerald-500/20 transition-all hover:scale-[1.02] active:scale-[0.98]"
                    >
                        <Gift class="w-4 h-4" />
                        <span>Claim Freebies</span>
                        <ChevronRight class="w-3.5 h-3.5" />
                    </Link>

                    <Link
                        href="/customer/rewards?tab=history"
                        class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-neutral-800 hover:bg-neutral-700 text-neutral-300 hover:text-white text-xs font-bold border border-neutral-700/50 transition-colors"
                        title="Points History"
                    >
                        <TrendingUp class="w-4 h-4 text-emerald-400" />
                        <span class="hidden sm:inline">Ledger</span>
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
