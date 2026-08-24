<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Gift,
    Plus,
    Search,
    Pencil,
    Trash2,
    Check,
    X,
    Sparkles,
    Ticket,
    Award,
    TrendingUp,
    CupSoda,
    Shirt,
    Crown,
    Percent,
    Dumbbell,
    Building,
    Power,
} from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

interface RewardItem {
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
    is_active: boolean;
    claims_count: number;
    venue_id: number | null;
    venue: { id: number; name: string } | null;
    created_at: string;
}

interface PaginatedRewards {
    data: RewardItem[];
    current_page: number;
    last_page: number;
}

interface VenueOption {
    id: number;
    name: string;
}

interface CategoryOption {
    value: string;
    label: string;
}

const props = defineProps<{
    rewards: PaginatedRewards;
    venues: VenueOption[];
    metrics: {
        total_rewards: number;
        active_rewards: number;
        total_claims: number;
        total_points_redeemed: number;
    };
    filters: {
        search?: string;
        category?: string;
        status?: string;
    };
    categories: CategoryOption[];
    icons: string[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Super Admin Overview', href: '/admin/dashboard' },
            { title: 'Freebies & Rewards', href: '/admin/rewards' },
        ],
    },
});

const search = ref(props.filters.search || '');
const category = ref(props.filters.category || 'all');
const status = ref(props.filters.status || 'all');

const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingReward = ref<RewardItem | null>(null);
const deletingReward = ref<RewardItem | null>(null);

const createForm = useForm({
    name: '',
    description: '',
    category: 'drink',
    points_cost: 100,
    stock: null as number | null,
    is_unlimited_stock: true,
    badge_text: '',
    icon: 'Gift',
    terms: '',
    venue_id: null as number | null,
    is_active: true,
});

const editForm = useForm({
    name: '',
    description: '',
    category: 'drink',
    points_cost: 100,
    stock: null as number | null,
    is_unlimited_stock: true,
    badge_text: '',
    icon: 'Gift',
    terms: '',
    venue_id: null as number | null,
    is_active: true,
});

function applyFilters() {
    router.get('/admin/rewards', {
        search: search.value,
        category: category.value,
        status: status.value,
    }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    category.value = 'all';
    status.value = 'all';
    applyFilters();
}

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

function openCreateModal() {
    createForm.reset();
    createForm.is_unlimited_stock = true;
    createForm.stock = null;
    createForm.clearErrors();
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.stock = createForm.is_unlimited_stock ? null : createForm.stock;
    createForm.post('/admin/rewards', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
        preserveScroll: true,
    });
}

function openEditModal(reward: RewardItem) {
    editingReward.value = reward;
    editForm.name = reward.name;
    editForm.description = reward.description || '';
    editForm.category = reward.category;
    editForm.points_cost = reward.points_cost;
    editForm.stock = reward.stock;
    editForm.is_unlimited_stock = reward.stock === null;
    editForm.badge_text = reward.badge_text || '';
    editForm.icon = reward.icon || 'Gift';
    editForm.terms = reward.terms || '';
    editForm.venue_id = reward.venue_id;
    editForm.is_active = reward.is_active;
    editForm.clearErrors();
    showEditModal.value = true;
}

function submitEdit() {
    if (!editingReward.value) return;

    editForm.stock = editForm.is_unlimited_stock ? null : editForm.stock;
    editForm.put(`/admin/rewards/${editingReward.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            editingReward.value = null;
        },
        preserveScroll: true,
    });
}

function toggleActiveStatus(reward: RewardItem) {
    router.patch(`/admin/rewards/${reward.id}/toggle-active`, {}, {
        preserveScroll: true,
    });
}

function confirmDelete(reward: RewardItem) {
    deletingReward.value = reward;
    showDeleteModal.value = true;
}

function executeDelete() {
    if (!deletingReward.value) return;

    router.delete(`/admin/rewards/${deletingReward.value.id}`, {
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingReward.value = null;
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Freebies & Rewards Management" />

    <div class="p-6 space-y-6 w-full pb-16">
        <!-- Header & Action -->
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4 bg-gradient-to-r from-emerald-600/10 via-teal-600/5 to-transparent p-6 rounded-2xl border border-emerald-500/10 dark:border-emerald-500/5">
            <div>
                <h1 class="text-2xl font-black text-neutral-900 dark:text-white tracking-tight">Freebies & Rewards Management</h1>
                <p class="text-xs text-neutral-500 mt-1">Create, update, and manage customer loyalty freebies, vouchers, stock levels, and point costs.</p>
            </div>
            <button
                type="button"
                @click="openCreateModal"
                class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-lg shadow-emerald-600/20 transition-all hover:translate-y-[-1px] flex items-center gap-1.5 cursor-pointer"
            >
                <Plus class="w-4 h-4" /> Add New Freebie / Reward
            </button>
        </div>

        <!-- Metric Stat Cards -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Total Freebies</span>
                <span class="text-2xl font-black text-neutral-900 dark:text-white mt-1 block">{{ metrics.total_rewards }}</span>
                <span class="text-[10px] text-neutral-500 mt-1 block">{{ metrics.active_rewards }} currently active</span>
            </div>

            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Active Catalog</span>
                <span class="text-2xl font-black text-emerald-600 mt-1 block">{{ metrics.active_rewards }}</span>
                <span class="text-[10px] text-emerald-600 font-medium mt-1 block">Available for customer claims</span>
            </div>

            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Total Vouchers Claimed</span>
                <span class="text-2xl font-black text-amber-600 mt-1 block">{{ metrics.total_claims }}</span>
                <span class="text-[10px] text-neutral-500 mt-1 block">Redemption passes generated</span>
            </div>

            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
                <span class="text-[11px] font-bold text-neutral-400 uppercase tracking-wider block">Points Redeemed</span>
                <span class="text-2xl font-black text-neutral-900 dark:text-white mt-1 block">{{ metrics.total_points_redeemed }}</span>
                <span class="text-[10px] text-neutral-500 mt-1 block">Total customer points spent</span>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">
            <div class="flex flex-1 flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <div class="relative flex-1 max-w-sm">
                    <Search class="w-4 h-4 text-neutral-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search freebies by name or description..."
                        class="w-full text-xs rounded-xl bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 pl-9 pr-3.5 py-2 text-neutral-900 dark:text-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-emerald-500"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <select
                    v-model="category"
                    @change="applyFilters"
                    class="text-xs rounded-xl bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 px-3.5 py-2 text-neutral-900 dark:text-white focus:outline-none"
                >
                    <option value="all">All Categories</option>
                    <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                        {{ cat.label }}
                    </option>
                </select>

                <select
                    v-model="status"
                    @change="applyFilters"
                    class="text-xs rounded-xl bg-neutral-50 dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 px-3.5 py-2 text-neutral-900 dark:text-white focus:outline-none"
                >
                    <option value="all">All Status</option>
                    <option value="active">Active Only</option>
                    <option value="inactive">Inactive Only</option>
                </select>

                <button
                    v-if="search || category !== 'all' || status !== 'all'"
                    type="button"
                    @click="clearFilters"
                    class="text-xs text-neutral-500 hover:text-neutral-900 dark:hover:text-white font-bold px-2 py-1 cursor-pointer"
                >
                    Clear
                </button>
            </div>
        </div>

        <!-- Rewards Data Table -->
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-bold uppercase tracking-wider bg-neutral-50/50 dark:bg-neutral-800/30">
                            <th class="py-3.5 px-4">Freebie / Reward</th>
                            <th class="py-3.5 px-4">Category</th>
                            <th class="py-3.5 px-4">Points Cost</th>
                            <th class="py-3.5 px-4">Stock</th>
                            <th class="py-3.5 px-4">Target Venue</th>
                            <th class="py-3.5 px-4">Claimed</th>
                            <th class="py-3.5 px-4">Status</th>
                            <th class="py-3.5 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        <tr
                            v-for="reward in rewards.data"
                            :key="reward.id"
                            class="hover:bg-neutral-50/50 dark:hover:bg-neutral-800/40 transition-colors"
                        >
                            <td class="py-3.5 px-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                                        <component :is="getIconComponent(reward.icon)" class="w-5 h-5" />
                                    </div>
                                    <div class="space-y-0.5">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-neutral-900 dark:text-white text-xs">{{ reward.name }}</span>
                                            <span v-if="reward.badge_text" class="px-1.5 py-0.2 rounded text-[9px] font-black uppercase bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                                {{ reward.badge_text }}
                                            </span>
                                        </div>
                                        <p class="text-[11px] text-neutral-400 line-clamp-1 max-w-xs">{{ reward.description || 'No description provided.' }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="py-3.5 px-4 capitalize font-semibold text-neutral-600 dark:text-neutral-300">
                                {{ reward.category }}
                            </td>

                            <td class="py-3.5 px-4">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-black text-xs">
                                    <Sparkles class="w-3.5 h-3.5 text-amber-500" />
                                    {{ reward.points_cost }} pts
                                </span>
                            </td>

                            <td class="py-3.5 px-4 font-medium text-neutral-600 dark:text-neutral-300">
                                <span v-if="reward.stock === null" class="text-neutral-400 font-bold">Unlimited</span>
                                <span v-else-if="reward.stock > 0" class="text-emerald-600 dark:text-emerald-400 font-bold">{{ reward.stock }} left</span>
                                <span v-else class="text-rose-600 font-bold">Out of stock</span>
                            </td>

                            <td class="py-3.5 px-4 text-neutral-500">
                                <span v-if="reward.venue" class="flex items-center gap-1">
                                    <Building class="w-3.5 h-3.5" /> {{ reward.venue.name }}
                                </span>
                                <span v-else class="text-neutral-400 font-semibold">Universal (All Venues)</span>
                            </td>

                            <td class="py-3.5 px-4 font-bold text-neutral-900 dark:text-white">
                                {{ reward.claims_count }} times
                            </td>

                            <td class="py-3.5 px-4">
                                <button
                                    type="button"
                                    @click="toggleActiveStatus(reward)"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider transition-colors cursor-pointer"
                                    :class="[
                                        reward.is_active
                                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 hover:bg-emerald-200'
                                            : 'bg-neutral-100 text-neutral-600 dark:bg-neutral-800 dark:text-neutral-400 hover:bg-neutral-200',
                                    ]"
                                    title="Click to toggle active status"
                                >
                                    <span class="w-1.5 h-1.5 rounded-full" :class="reward.is_active ? 'bg-emerald-500' : 'bg-neutral-400'"></span>
                                    <span>{{ reward.is_active ? 'Active' : 'Disabled' }}</span>
                                </button>
                            </td>

                            <td class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button
                                        type="button"
                                        @click="openEditModal(reward)"
                                        class="p-1.5 rounded-lg text-neutral-500 hover:text-neutral-900 dark:hover:text-white hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors cursor-pointer"
                                        title="Edit Freebie"
                                    >
                                        <Pencil class="w-4 h-4" />
                                    </button>

                                    <button
                                        type="button"
                                        @click="confirmDelete(reward)"
                                        class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                        title="Delete Freebie"
                                    >
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="rewards.data.length === 0">
                            <td colspan="8" class="py-12 text-center text-xs text-neutral-400">
                                No freebies or rewards found matching your criteria.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="rewards.last_page > 1" class="p-4 border-t border-neutral-100 dark:border-neutral-800 flex items-center justify-between text-xs text-neutral-500">
                <span>Page {{ rewards.current_page }} of {{ rewards.last_page }}</span>
                <div class="flex items-center gap-1">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="rewards.current_page <= 1"
                        @click="router.get(`/admin/rewards?page=${rewards.current_page - 1}`, {}, { preserveState: true })"
                    >
                        Previous
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="rewards.current_page >= rewards.last_page"
                        @click="router.get(`/admin/rewards?page=${rewards.current_page + 1}`, {}, { preserveState: true })"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- CREATE REWARD MODAL                       -->
        <!-- ========================================== -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-950/75 backdrop-blur-sm">
            <div class="relative w-full max-w-lg bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 shadow-2xl p-6 overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                    <h3 class="font-black text-lg text-neutral-900 dark:text-white">Create New Freebie / Reward</h3>
                    <button type="button" @click="showCreateModal = false" class="p-1 text-neutral-400 hover:text-neutral-700 dark:hover:text-white cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4 pt-4">
                    <div class="space-y-1.5">
                        <Label for="create-name">Reward Name <span class="text-rose-500">*</span></Label>
                        <Input id="create-name" v-model="createForm.name" placeholder="e.g. Ice-Cold Electrolyte Sports Drink" required />
                        <InputError :message="createForm.errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="create-category">Category</Label>
                            <select
                                id="create-category"
                                v-model="createForm.category"
                                class="w-full text-xs rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 px-3.5 py-2 text-neutral-900 dark:text-white focus:outline-none"
                            >
                                <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                            </select>
                            <InputError :message="createForm.errors.category" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="create-icon">Icon</Label>
                            <select
                                id="create-icon"
                                v-model="createForm.icon"
                                class="w-full text-xs rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 px-3.5 py-2 text-neutral-900 dark:text-white focus:outline-none"
                            >
                                <option v-for="ic in icons" :key="ic" :value="ic">{{ ic }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="create-points">Points Cost <span class="text-rose-500">*</span></Label>
                            <Input id="create-points" v-model.number="createForm.points_cost" type="number" min="1" required />
                            <InputError :message="createForm.errors.points_cost" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="create-badge">Badge Text (Optional)</Label>
                            <Input id="create-badge" v-model="createForm.badge_text" placeholder="e.g. Popular / VIP" />
                            <InputError :message="createForm.errors.badge_text" />
                        </div>
                    </div>

                    <!-- Stock Setting -->
                    <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-100 dark:border-neutral-800 space-y-2">
                        <div class="flex items-center justify-between">
                            <Label class="font-bold text-xs">Stock Availability</Label>
                            <label class="flex items-center gap-2 text-xs text-neutral-600 dark:text-neutral-300 cursor-pointer">
                                <input type="checkbox" v-model="createForm.is_unlimited_stock" class="rounded text-emerald-600" />
                                <span>Unlimited Stock</span>
                            </label>
                        </div>

                        <div v-if="!createForm.is_unlimited_stock" class="pt-1">
                            <Input :model-value="createForm.stock ?? undefined" @update:model-value="val => createForm.stock = (val !== undefined && val !== '' ? Number(val) : null)" type="number" min="0" placeholder="Available Quantity" />
                            <InputError :message="createForm.errors.stock" />
                        </div>
                    </div>

                    <!-- Venue Scope -->
                    <div v-if="venues.length > 1" class="space-y-1.5">
                        <Label for="create-venue">Target Venue Scope</Label>
                        <select
                            id="create-venue"
                            v-model="createForm.venue_id"
                            class="w-full text-xs rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 px-3.5 py-2 text-neutral-900 dark:text-white focus:outline-none"
                        >
                            <option :value="null">Universal (All Venues)</option>
                            <option v-for="v in venues" :key="v.id" :value="v.id">{{ v.name }}</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="create-description">Description</Label>
                        <textarea
                            id="create-description"
                            v-model="createForm.description"
                            rows="2"
                            placeholder="Briefly describe what the customer receives when claiming this reward..."
                            class="w-full text-xs rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 p-3 text-neutral-900 dark:text-white focus:outline-none"
                        ></textarea>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="create-terms">Terms & Redemption Instructions</Label>
                        <textarea
                            id="create-terms"
                            v-model="createForm.terms"
                            rows="2"
                            placeholder="e.g. Show voucher to front desk counter upon arrival. Valid for 30 days."
                            class="w-full text-xs rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 p-3 text-neutral-900 dark:text-white focus:outline-none"
                        ></textarea>
                    </div>

                    <label class="flex items-center gap-2 text-xs text-neutral-700 dark:text-neutral-300 cursor-pointer pt-1">
                        <input type="checkbox" v-model="createForm.is_active" class="rounded text-emerald-600" />
                        <span class="font-bold">Publish to customer catalog immediately</span>
                    </label>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-neutral-100 dark:border-neutral-800">
                        <Button type="button" variant="outline" @click="showCreateModal = false">Cancel</Button>
                        <Button type="submit" :disabled="createForm.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                            {{ createForm.processing ? 'Creating...' : 'Save & Publish' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- EDIT REWARD MODAL                         -->
        <!-- ========================================== -->
        <div v-if="showEditModal && editingReward" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-950/75 backdrop-blur-sm">
            <div class="relative w-full max-w-lg bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 shadow-2xl p-6 overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                    <h3 class="font-black text-lg text-neutral-900 dark:text-white">Edit Freebie / Reward</h3>
                    <button type="button" @click="showEditModal = false" class="p-1 text-neutral-400 hover:text-neutral-700 dark:hover:text-white cursor-pointer">
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4 pt-4">
                    <div class="space-y-1.5">
                        <Label for="edit-name">Reward Name <span class="text-rose-500">*</span></Label>
                        <Input id="edit-name" v-model="editForm.name" required />
                        <InputError :message="editForm.errors.name" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="edit-category">Category</Label>
                            <select
                                id="edit-category"
                                v-model="editForm.category"
                                class="w-full text-xs rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 px-3.5 py-2 text-neutral-900 dark:text-white focus:outline-none"
                            >
                                <option v-for="c in categories" :key="c.value" :value="c.value">{{ c.label }}</option>
                            </select>
                            <InputError :message="editForm.errors.category" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="edit-icon">Icon</Label>
                            <select
                                id="edit-icon"
                                v-model="editForm.icon"
                                class="w-full text-xs rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 px-3.5 py-2 text-neutral-900 dark:text-white focus:outline-none"
                            >
                                <option v-for="ic in icons" :key="ic" :value="ic">{{ ic }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="edit-points">Points Cost <span class="text-rose-500">*</span></Label>
                            <Input id="edit-points" v-model.number="editForm.points_cost" type="number" min="1" required />
                            <InputError :message="editForm.errors.points_cost" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="edit-badge">Badge Text</Label>
                            <Input id="edit-badge" v-model="editForm.badge_text" />
                            <InputError :message="editForm.errors.badge_text" />
                        </div>
                    </div>

                    <!-- Stock Setting -->
                    <div class="p-3.5 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-100 dark:border-neutral-800 space-y-2">
                        <div class="flex items-center justify-between">
                            <Label class="font-bold text-xs">Stock Availability</Label>
                            <label class="flex items-center gap-2 text-xs text-neutral-600 dark:text-neutral-300 cursor-pointer">
                                <input type="checkbox" v-model="editForm.is_unlimited_stock" class="rounded text-emerald-600" />
                                <span>Unlimited Stock</span>
                            </label>
                        </div>

                        <div v-if="!editForm.is_unlimited_stock" class="pt-1">
                            <Input :model-value="editForm.stock ?? undefined" @update:model-value="val => editForm.stock = (val !== undefined && val !== '' ? Number(val) : null)" type="number" min="0" />
                            <InputError :message="editForm.errors.stock" />
                        </div>
                    </div>

                    <!-- Venue Scope -->
                    <div v-if="venues.length > 1" class="space-y-1.5">
                        <Label for="edit-venue">Target Venue Scope</Label>
                        <select
                            id="edit-venue"
                            v-model="editForm.venue_id"
                            class="w-full text-xs rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 px-3.5 py-2 text-neutral-900 dark:text-white focus:outline-none"
                        >
                            <option :value="null">Universal (All Venues)</option>
                            <option v-for="v in venues" :key="v.id" :value="v.id">{{ v.name }}</option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit-description">Description</Label>
                        <textarea
                            id="edit-description"
                            v-model="editForm.description"
                            rows="2"
                            class="w-full text-xs rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 p-3 text-neutral-900 dark:text-white focus:outline-none"
                        ></textarea>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="edit-terms">Terms & Redemption Instructions</Label>
                        <textarea
                            id="edit-terms"
                            v-model="editForm.terms"
                            rows="2"
                            class="w-full text-xs rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-700 p-3 text-neutral-900 dark:text-white focus:outline-none"
                        ></textarea>
                    </div>

                    <label class="flex items-center gap-2 text-xs text-neutral-700 dark:text-neutral-300 cursor-pointer pt-1">
                        <input type="checkbox" v-model="editForm.is_active" class="rounded text-emerald-600" />
                        <span class="font-bold">Reward is active and claimable</span>
                    </label>

                    <div class="flex items-center justify-end gap-2 pt-4 border-t border-neutral-100 dark:border-neutral-800">
                        <Button type="button" variant="outline" @click="showEditModal = false">Cancel</Button>
                        <Button type="submit" :disabled="editForm.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                            {{ editForm.processing ? 'Saving...' : 'Update Freebie' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- DELETE CONFIRMATION MODAL                  -->
        <!-- ========================================== -->
        <div v-if="showDeleteModal && deletingReward" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-950/75 backdrop-blur-sm">
            <div class="w-full max-w-sm bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 shadow-2xl p-6 space-y-4">
                <div class="w-12 h-12 rounded-full bg-rose-100 dark:bg-rose-950/50 text-rose-600 flex items-center justify-center mx-auto">
                    <Trash2 class="w-6 h-6" />
                </div>

                <div class="text-center space-y-1">
                    <h3 class="font-black text-base text-neutral-900 dark:text-white">Delete Freebie?</h3>
                    <p class="text-xs text-neutral-500">
                        Are you sure you want to delete <strong class="text-neutral-900 dark:text-white">{{ deletingReward.name }}</strong>? This action cannot be undone.
                    </p>
                </div>

                <div class="flex items-center gap-2 pt-2">
                    <Button variant="outline" class="flex-1" @click="showDeleteModal = false">Cancel</Button>
                    <Button variant="destructive" class="flex-1 bg-rose-600 hover:bg-rose-700 text-white" @click="executeDelete">
                        Delete
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
