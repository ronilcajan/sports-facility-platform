<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    Receipt,
    Plus,
    Search,
    Pencil,
    Trash2,
    Calendar,
    Building,
    CreditCard,
    DollarSign,
    Filter,
    X,
    TrendingDown,
    PieChart,
    RotateCcw,
    FileText,
} from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

interface ExpenseItem {
    id: number;
    title: string;
    amount: number;
    date: string;
    date_formatted: string;
    category: string;
    category_label: string;
    venue_id: number | null;
    venue: { id: number; name: string } | null;
    user: { id: number; name: string } | null;
    payment_method: string | null;
    payment_method_label: string;
    reference_number: string | null;
    notes: string | null;
    created_at: string;
}

interface PaginatedExpenses {
    data: ExpenseItem[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface VenueOption {
    id: number;
    name: string;
}

interface OptionItem {
    value: string;
    label: string;
}

const props = defineProps<{
    expenses: PaginatedExpenses;
    venues: VenueOption[];
    categories: OptionItem[];
    paymentMethods: OptionItem[];
    metrics: {
        total_expenses: number;
        total_records: number;
        this_month_expenses: number;
        this_month_count: number;
        top_category: { key: string; label: string; amount: number } | null;
        average_expense: number;
    };
    filters: {
        search?: string;
        category?: string;
        venue_id?: string;
        payment_method?: string;
        start_date?: string;
        end_date?: string;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Super Admin Overview', href: '/admin/dashboard' },
            { title: 'Facility Expenses', href: '/admin/expenses' },
        ],
    },
});

// Filters State
const search = ref(props.filters.search || '');
const category = ref(props.filters.category || 'all');
const venue_id = ref(props.filters.venue_id || 'all');
const payment_method = ref(props.filters.payment_method || 'all');
const start_date = ref(props.filters.start_date || '');
const end_date = ref(props.filters.end_date || '');

function applyFilters() {
    router.get(
        '/admin/expenses',
        {
            search: search.value || undefined,
            category: category.value !== 'all' ? category.value : undefined,
            venue_id: venue_id.value !== 'all' ? venue_id.value : undefined,
            payment_method: payment_method.value !== 'all' ? payment_method.value : undefined,
            start_date: start_date.value || undefined,
            end_date: end_date.value || undefined,
        },
        { preserveState: true, replace: true }
    );
}

function resetFilters() {
    search.value = '';
    category.value = 'all';
    venue_id.value = 'all';
    payment_method.value = 'all';
    start_date.value = '';
    end_date.value = '';
    router.get('/admin/expenses', {}, { preserveState: true, replace: true });
}

// Modals State
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showDeleteModal = ref(false);
const editingExpense = ref<ExpenseItem | null>(null);
const deletingExpense = ref<ExpenseItem | null>(null);

// Today's date helper
const todayString = new Date().toISOString().split('T')[0];

// Create Form
const createForm = useForm({
    title: '',
    amount: '',
    date: todayString,
    category: 'utilities',
    venue_id: '' as string | number,
    payment_method: 'cash',
    reference_number: '',
    notes: '',
});

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    createForm.date = todayString;
    createForm.category = 'utilities';
    createForm.payment_method = 'cash';
    createForm.venue_id = '';
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.transform((data) => ({
        ...data,
        venue_id: data.venue_id ? Number(data.venue_id) : null,
        amount: Number(data.amount),
        reference_number: data.reference_number || null,
        notes: data.notes || null,
    })).post('/admin/expenses', {
        preserveScroll: true,
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
    });
}

// Edit Form
const editForm = useForm({
    title: '',
    amount: '',
    date: '',
    category: '',
    venue_id: '' as string | number,
    payment_method: '',
    reference_number: '',
    notes: '',
});

function openEditModal(expense: ExpenseItem) {
    editingExpense.value = expense;
    editForm.clearErrors();
    editForm.title = expense.title;
    editForm.amount = expense.amount.toString();
    editForm.date = expense.date;
    editForm.category = expense.category;
    editForm.venue_id = expense.venue_id !== null ? expense.venue_id : '';
    editForm.payment_method = expense.payment_method || 'cash';
    editForm.reference_number = expense.reference_number || '';
    editForm.notes = expense.notes || '';
    showEditModal.value = true;
}

function submitUpdate() {
    if (!editingExpense.value) return;

    editForm.transform((data) => ({
        ...data,
        venue_id: data.venue_id ? Number(data.venue_id) : null,
        amount: Number(data.amount),
        reference_number: data.reference_number || null,
        notes: data.notes || null,
    })).put(`/admin/expenses/${editingExpense.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
            editingExpense.value = null;
        },
    });
}

// Delete Form
const deleteForm = useForm({});

function confirmDelete(expense: ExpenseItem) {
    deletingExpense.value = expense;
    showDeleteModal.value = true;
}

function submitDelete() {
    if (!deletingExpense.value) return;

    deleteForm.delete(`/admin/expenses/${deletingExpense.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showDeleteModal.value = false;
            deletingExpense.value = null;
        },
    });
}

function getCategoryBadgeClass(cat: string): string {
    switch (cat) {
        case 'utilities':
            return 'bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300 border-blue-200 dark:border-blue-800';
        case 'maintenance':
            return 'bg-amber-50 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300 border-amber-200 dark:border-amber-800';
        case 'equipment':
            return 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800';
        case 'supplies':
            return 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800';
        case 'salaries':
            return 'bg-purple-50 text-purple-700 dark:bg-purple-950/50 dark:text-purple-300 border-purple-200 dark:border-purple-800';
        case 'marketing':
            return 'bg-pink-50 text-pink-700 dark:bg-pink-950/50 dark:text-pink-300 border-pink-200 dark:border-pink-800';
        case 'rent':
            return 'bg-cyan-50 text-cyan-700 dark:bg-cyan-950/50 dark:text-cyan-300 border-cyan-200 dark:border-cyan-800';
        default:
            return 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 border-neutral-200 dark:border-neutral-700';
    }
}
</script>

<template>
    <Head title="Facility Expenses - Super Admin" />

    <div class="p-6 space-y-6 w-full max-w-7xl mx-auto">
        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-neutral-900 dark:text-white">
                    Facility & Operational Expenses
                </h1>
                <p class="text-xs text-neutral-500 mt-1">
                    Track, categorize, and record operating costs, court maintenance, and facility overheads.
                </p>
            </div>

            <Button
                @click="openCreateModal"
                class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs px-4 py-2.5 rounded-xl shadow-xs inline-flex items-center gap-2 cursor-pointer transition-all"
            >
                <Plus class="w-4 h-4" />
                Record Expense
            </Button>
        </div>

        <!-- Metric KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Total Expenses -->
            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Total Expenses</span>
                    <div class="p-2 rounded-xl bg-rose-50 dark:bg-rose-950/40 text-rose-600">
                        <Receipt class="w-4 h-4" />
                    </div>
                </div>
                <div class="text-2xl font-extrabold text-rose-600 dark:text-rose-500">
                    ₱{{ metrics.total_expenses.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
                <p class="text-[11px] text-neutral-400 mt-1">{{ metrics.total_records }} total recorded entries</p>
            </div>

            <!-- This Month's Expenses -->
            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">This Month</span>
                    <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600">
                        <Calendar class="w-4 h-4" />
                    </div>
                </div>
                <div class="text-2xl font-extrabold text-neutral-900 dark:text-white">
                    ₱{{ metrics.this_month_expenses.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
                <p class="text-[11px] text-neutral-400 mt-1">{{ metrics.this_month_count }} entries logged this month</p>
            </div>

            <!-- Average Per Entry -->
            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Average Outlay</span>
                    <div class="p-2 rounded-xl bg-blue-50 dark:bg-blue-950/40 text-blue-600">
                        <TrendingDown class="w-4 h-4" />
                    </div>
                </div>
                <div class="text-2xl font-extrabold text-neutral-900 dark:text-white">
                    ₱{{ metrics.average_expense.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                </div>
                <p class="text-[11px] text-neutral-400 mt-1">Average cost per logged expense</p>
            </div>

            <!-- Top Spending Category -->
            <div class="p-5 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs relative overflow-hidden">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-neutral-500 uppercase tracking-wider">Top Category</span>
                    <div class="p-2 rounded-xl bg-indigo-50 dark:bg-indigo-950/40 text-indigo-600">
                        <PieChart class="w-4 h-4" />
                    </div>
                </div>
                <div class="text-lg font-bold text-neutral-900 dark:text-white truncate">
                    {{ metrics.top_category?.label ?? 'None' }}
                </div>
                <p class="text-[11px] text-neutral-400 mt-1">
                    <span v-if="metrics.top_category" class="font-semibold text-indigo-600 dark:text-indigo-400">
                        ₱{{ metrics.top_category.amount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}
                    </span>
                    <span v-else>No category data</span>
                </p>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <!-- Search -->
                <div class="lg:col-span-2 relative">
                    <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-neutral-400 pointer-events-none" />
                    <Input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        placeholder="Search title, reference #..."
                        class="pl-9 text-xs h-9 bg-neutral-50/50 dark:bg-neutral-800/40"
                    />
                </div>

                <!-- Category Filter -->
                <div>
                    <select
                        v-model="category"
                        @change="applyFilters"
                        class="w-full text-xs h-9 rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 text-neutral-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="all">All Categories</option>
                        <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                            {{ cat.label }}
                        </option>
                    </select>
                </div>

                <!-- Venue Filter -->
                <div>
                    <select
                        v-model="venue_id"
                        @change="applyFilters"
                        class="w-full text-xs h-9 rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 text-neutral-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="all">All Venues</option>
                        <option value="platform">Platform / Universal (No Venue)</option>
                        <option v-for="ven in venues" :key="ven.id" :value="ven.id">
                            {{ ven.name }}
                        </option>
                    </select>
                </div>

                <!-- Payment Method Filter -->
                <div>
                    <select
                        v-model="payment_method"
                        @change="applyFilters"
                        class="w-full text-xs h-9 rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 text-neutral-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
                    >
                        <option value="all">All Payment Methods</option>
                        <option v-for="pm in paymentMethods" :key="pm.value" :value="pm.value">
                            {{ pm.label }}
                        </option>
                    </select>
                </div>

                <!-- Actions: Apply & Reset -->
                <div class="flex items-center gap-1.5">
                    <Button
                        @click="applyFilters"
                        size="sm"
                        class="flex-1 h-9 bg-neutral-900 hover:bg-neutral-800 dark:bg-white dark:hover:bg-neutral-100 text-white dark:text-neutral-900 text-xs font-semibold cursor-pointer"
                    >
                        Filter
                    </Button>
                    <Button
                        @click="resetFilters"
                        variant="outline"
                        size="sm"
                        class="h-9 px-2.5 text-xs text-neutral-500 hover:text-neutral-900 dark:hover:text-white cursor-pointer"
                        title="Clear filters"
                    >
                        <RotateCcw class="w-3.5 h-3.5" />
                    </Button>
                </div>
            </div>

            <!-- Secondary Date Range Filter Row -->
            <div class="flex flex-wrap items-center gap-2 pt-2 border-t border-neutral-100 dark:border-neutral-800 text-xs text-neutral-500">
                <span class="font-medium text-[11px] text-neutral-400">Date Range:</span>
                <input
                    v-model="start_date"
                    type="date"
                    class="p-1 rounded-md border border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-900 dark:text-white text-xs"
                />
                <span>to</span>
                <input
                    v-model="end_date"
                    type="date"
                    class="p-1 rounded-md border border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-neutral-900 dark:text-white text-xs"
                />
                <button
                    @click="applyFilters"
                    class="px-2.5 py-1 bg-neutral-200 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-200 rounded-md font-medium hover:bg-neutral-300 dark:hover:bg-neutral-600 transition-colors text-xs cursor-pointer"
                >
                    Apply Range
                </button>
            </div>
        </div>

        <!-- Expenses Data Table -->
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xs overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-semibold uppercase bg-neutral-50/50 dark:bg-neutral-800/40">
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Expense Title</th>
                            <th class="py-3 px-4">Category</th>
                            <th class="py-3 px-4">Venue</th>
                            <th class="py-3 px-4">Payment Method</th>
                            <th class="py-3 px-4 text-right">Amount</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        <tr
                            v-for="expense in expenses.data"
                            :key="expense.id"
                            class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40 transition-colors"
                        >
                            <!-- Date -->
                            <td class="py-3.5 px-4 font-medium text-neutral-600 dark:text-neutral-300 whitespace-nowrap">
                                {{ expense.date_formatted }}
                            </td>

                            <!-- Title & Reference -->
                            <td class="py-3.5 px-4 max-w-xs">
                                <div class="font-bold text-neutral-900 dark:text-white truncate">
                                    {{ expense.title }}
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 text-[11px] text-neutral-400 truncate">
                                    <span v-if="expense.reference_number" class="font-mono bg-neutral-100 dark:bg-neutral-800 px-1.5 py-0.2 rounded text-[10px]">
                                        #{{ expense.reference_number }}
                                    </span>
                                    <span v-if="expense.notes" class="truncate max-w-[200px]" :title="expense.notes">
                                        {{ expense.notes }}
                                    </span>
                                </div>
                            </td>

                            <!-- Category -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span
                                    class="px-2.5 py-0.5 rounded-md border text-[11px] font-semibold inline-block"
                                    :class="getCategoryBadgeClass(expense.category)"
                                >
                                    {{ expense.category_label }}
                                </span>
                            </td>

                            <!-- Venue -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span
                                    v-if="expense.venue"
                                    class="inline-flex items-center gap-1 text-neutral-700 dark:text-neutral-300 font-medium"
                                >
                                    <Building class="w-3 h-3 text-neutral-400" />
                                    {{ expense.venue.name }}
                                </span>
                                <span v-else class="text-neutral-400 italic">
                                    Platform / Universal
                                </span>
                            </td>

                            <!-- Payment Method -->
                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md bg-neutral-100 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 text-[11px] font-medium">
                                    {{ expense.payment_method_label }}
                                </span>
                            </td>

                            <!-- Amount -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="font-extrabold text-sm text-rose-600 dark:text-rose-400">
                                    ₱{{ expense.amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }}
                                </div>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1">
                                    <button
                                        type="button"
                                        @click="openEditModal(expense)"
                                        class="p-1.5 rounded-lg text-neutral-500 hover:text-neutral-900 dark:hover:text-white hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors cursor-pointer"
                                        title="Edit Expense"
                                    >
                                        <Pencil class="w-3.5 h-3.5" />
                                    </button>

                                    <button
                                        type="button"
                                        @click="confirmDelete(expense)"
                                        class="p-1.5 rounded-lg text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors cursor-pointer"
                                        title="Delete Expense"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <!-- Empty state -->
                        <tr v-if="expenses.data.length === 0">
                            <td colspan="7" class="py-16 text-center text-xs text-neutral-400 space-y-3">
                                <div class="w-12 h-12 rounded-2xl bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center mx-auto text-neutral-400">
                                    <Receipt class="w-6 h-6" />
                                </div>
                                <div class="font-semibold text-neutral-700 dark:text-neutral-300 text-sm">
                                    No expenses found
                                </div>
                                <p class="text-[11px] text-neutral-400 max-w-sm mx-auto">
                                    No expense records match your current criteria. Record a new expense or clear your filters.
                                </p>
                                <Button
                                    @click="openCreateModal"
                                    size="sm"
                                    class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold cursor-pointer"
                                >
                                    <Plus class="w-3.5 h-3.5 mr-1" />
                                    Record Expense
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div
                v-if="expenses.last_page > 1"
                class="p-4 border-t border-neutral-100 dark:border-neutral-800 flex items-center justify-between text-xs text-neutral-500"
            >
                <span>
                    Showing {{ expenses.from ?? 0 }} to {{ expenses.to ?? 0 }} of {{ expenses.total }} expenses
                </span>
                <div class="flex items-center gap-1.5">
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="expenses.current_page <= 1"
                        @click="router.get(`/admin/expenses?page=${expenses.current_page - 1}`, {}, { preserveState: true })"
                        class="cursor-pointer"
                    >
                        Previous
                    </Button>
                    <span class="px-2 font-medium">Page {{ expenses.current_page }} / {{ expenses.last_page }}</span>
                    <Button
                        variant="outline"
                        size="sm"
                        :disabled="expenses.current_page >= expenses.last_page"
                        @click="router.get(`/admin/expenses?page=${expenses.current_page + 1}`, {}, { preserveState: true })"
                        class="cursor-pointer"
                    >
                        Next
                    </Button>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- CREATE EXPENSE MODAL                       -->
        <!-- ========================================== -->
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-950/75 backdrop-blur-xs">
            <div class="relative w-full max-w-lg bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 shadow-2xl p-6 overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 text-emerald-600">
                            <Receipt class="w-4 h-4" />
                        </div>
                        <h3 class="font-bold text-base text-neutral-900 dark:text-white">Record New Expense</h3>
                    </div>
                    <button
                        type="button"
                        @click="showCreateModal = false"
                        class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 dark:hover:text-white cursor-pointer"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4 pt-4">
                    <!-- Title -->
                    <div class="space-y-1.5">
                        <Label for="create-title">Expense Title <span class="text-rose-500">*</span></Label>
                        <Input
                            id="create-title"
                            v-model="createForm.title"
                            placeholder="e.g. Court 1 Light Bulb Replacements"
                            required
                        />
                        <InputError :message="createForm.errors.title" />
                    </div>

                    <!-- Amount & Date -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="create-amount">Amount (₱) <span class="text-rose-500">*</span></Label>
                            <Input
                                id="create-amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                v-model="createForm.amount"
                                placeholder="0.00"
                                required
                            />
                            <InputError :message="createForm.errors.amount" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="create-date">Date <span class="text-rose-500">*</span></Label>
                            <Input
                                id="create-date"
                                type="date"
                                v-model="createForm.date"
                                required
                            />
                            <InputError :message="createForm.errors.date" />
                        </div>
                    </div>

                    <!-- Category & Venue -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="create-category">Category <span class="text-rose-500">*</span></Label>
                            <select
                                id="create-category"
                                v-model="createForm.category"
                                class="w-full text-xs h-9 rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                required
                            >
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                            <InputError :message="createForm.errors.category" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="create-venue">Venue Scope</Label>
                            <select
                                id="create-venue"
                                v-model="createForm.venue_id"
                                class="w-full text-xs h-9 rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="">Universal / Platform (HQ)</option>
                                <option v-for="ven in venues" :key="ven.id" :value="ven.id">
                                    {{ ven.name }}
                                </option>
                            </select>
                            <InputError :message="createForm.errors.venue_id" />
                        </div>
                    </div>

                    <!-- Payment Method & Reference # -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="create-payment-method">Payment Method</Label>
                            <select
                                id="create-payment-method"
                                v-model="createForm.payment_method"
                                class="w-full text-xs h-9 rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            >
                                <option v-for="pm in paymentMethods" :key="pm.value" :value="pm.value">
                                    {{ pm.label }}
                                </option>
                            </select>
                            <InputError :message="createForm.errors.payment_method" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="create-ref">Reference / Invoice #</Label>
                            <Input
                                id="create-ref"
                                v-model="createForm.reference_number"
                                placeholder="e.g. INV-9042"
                            />
                            <InputError :message="createForm.errors.reference_number" />
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-1.5">
                        <Label for="create-notes">Notes / Remarks</Label>
                        <textarea
                            id="create-notes"
                            v-model="createForm.notes"
                            rows="2"
                            placeholder="Additional details regarding this operational expense..."
                            class="w-full text-xs rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-2.5 text-neutral-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
                        ></textarea>
                        <InputError :message="createForm.errors.notes" />
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100 dark:border-neutral-800">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showCreateModal = false"
                            class="cursor-pointer text-xs"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="createForm.processing"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs cursor-pointer"
                        >
                            {{ createForm.processing ? 'Saving...' : 'Save Expense' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- EDIT EXPENSE MODAL                         -->
        <!-- ========================================== -->
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-950/75 backdrop-blur-xs">
            <div class="relative w-full max-w-lg bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 shadow-2xl p-6 overflow-hidden max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600">
                            <Pencil class="w-4 h-4" />
                        </div>
                        <h3 class="font-bold text-base text-neutral-900 dark:text-white">Edit Expense</h3>
                    </div>
                    <button
                        type="button"
                        @click="showEditModal = false"
                        class="p-1.5 rounded-lg text-neutral-400 hover:text-neutral-700 dark:hover:text-white cursor-pointer"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <form @submit.prevent="submitUpdate" class="space-y-4 pt-4">
                    <!-- Title -->
                    <div class="space-y-1.5">
                        <Label for="edit-title">Expense Title <span class="text-rose-500">*</span></Label>
                        <Input
                            id="edit-title"
                            v-model="editForm.title"
                            required
                        />
                        <InputError :message="editForm.errors.title" />
                    </div>

                    <!-- Amount & Date -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="edit-amount">Amount (₱) <span class="text-rose-500">*</span></Label>
                            <Input
                                id="edit-amount"
                                type="number"
                                step="0.01"
                                min="0.01"
                                v-model="editForm.amount"
                                required
                            />
                            <InputError :message="editForm.errors.amount" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="edit-date">Date <span class="text-rose-500">*</span></Label>
                            <Input
                                id="edit-date"
                                type="date"
                                v-model="editForm.date"
                                required
                            />
                            <InputError :message="editForm.errors.date" />
                        </div>
                    </div>

                    <!-- Category & Venue -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="edit-category">Category <span class="text-rose-500">*</span></Label>
                            <select
                                id="edit-category"
                                v-model="editForm.category"
                                class="w-full text-xs h-9 rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                                required
                            >
                                <option v-for="cat in categories" :key="cat.value" :value="cat.value">
                                    {{ cat.label }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.category" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="edit-venue">Venue Scope</Label>
                            <select
                                id="edit-venue"
                                v-model="editForm.venue_id"
                                class="w-full text-xs h-9 rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="">Universal / Platform (HQ)</option>
                                <option v-for="ven in venues" :key="ven.id" :value="ven.id">
                                    {{ ven.name }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.venue_id" />
                        </div>
                    </div>

                    <!-- Payment Method & Reference # -->
                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <Label for="edit-payment-method">Payment Method</Label>
                            <select
                                id="edit-payment-method"
                                v-model="editForm.payment_method"
                                class="w-full text-xs h-9 rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                            >
                                <option v-for="pm in paymentMethods" :key="pm.value" :value="pm.value">
                                    {{ pm.label }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.payment_method" />
                        </div>

                        <div class="space-y-1.5">
                            <Label for="edit-ref">Reference / Invoice #</Label>
                            <Input
                                id="edit-ref"
                                v-model="editForm.reference_number"
                            />
                            <InputError :message="editForm.errors.reference_number" />
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="space-y-1.5">
                        <Label for="edit-notes">Notes / Remarks</Label>
                        <textarea
                            id="edit-notes"
                            v-model="editForm.notes"
                            rows="2"
                            class="w-full text-xs rounded-md border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 p-2.5 text-neutral-900 dark:text-white focus:outline-hidden focus:ring-2 focus:ring-emerald-500"
                        ></textarea>
                        <InputError :message="editForm.errors.notes" />
                    </div>

                    <!-- Buttons -->
                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-neutral-100 dark:border-neutral-800">
                        <Button
                            type="button"
                            variant="outline"
                            @click="showEditModal = false"
                            class="cursor-pointer text-xs"
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                            class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold text-xs cursor-pointer"
                        >
                            {{ editForm.processing ? 'Saving...' : 'Update Expense' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- DELETE CONFIRMATION MODAL                  -->
        <!-- ========================================== -->
        <div v-if="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-950/75 backdrop-blur-xs">
            <div class="relative w-full max-w-md bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 shadow-2xl p-6 overflow-hidden">
                <div class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/50 text-rose-600 flex items-center justify-center mx-auto mb-4">
                    <Trash2 class="w-6 h-6" />
                </div>

                <div class="text-center space-y-2">
                    <h3 class="font-bold text-lg text-neutral-900 dark:text-white">Delete Expense Record?</h3>
                    <p class="text-xs text-neutral-500">
                        Are you sure you want to delete <span class="font-semibold text-neutral-900 dark:text-white">"{{ deletingExpense?.title }}"</span> of
                        <span class="font-semibold text-rose-600">₱{{ deletingExpense?.amount.toLocaleString('en-US', { minimumFractionDigits: 2 }) }}</span>?
                        This action cannot be undone.
                    </p>
                </div>

                <div class="flex items-center justify-center gap-2 mt-6">
                    <Button
                        type="button"
                        variant="outline"
                        @click="showDeleteModal = false"
                        class="cursor-pointer text-xs"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        @click="submitDelete"
                        :disabled="deleteForm.processing"
                        class="bg-rose-600 hover:bg-rose-700 text-white font-semibold text-xs cursor-pointer"
                    >
                        {{ deleteForm.processing ? 'Deleting...' : 'Yes, Delete' }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
