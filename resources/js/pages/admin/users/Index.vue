<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Search, Plus, Pencil, Trash2, X, ArrowUpRight, Sparkles, Gift } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

interface UserItem {
    id: number;
    name: string;
    email: string;
    roles: string[];
    points: number;
    claims_count: number;
    created_at: string;
}

interface PaginatedUsers {
    data: UserItem[];
    current_page: number;
    last_page: number;
}

interface RoleOption {
    value: string;
    label?: string;
}

const props = defineProps<{
    users: PaginatedUsers;
    filters: { search?: string; role?: string };
    roles: RoleOption[];
    canManageUsers: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'User Accounts', href: '/admin/users' },
        ],
    },
});

const search = ref(props.filters.search || '');
const role = ref(props.filters.role || '');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const showPointsModal = ref(false);
const editingUser = ref<UserItem | null>(null);
const pointsUser = ref<UserItem | null>(null);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    role: '',
});

const editForm = useForm({
    name: '',
    email: '',
    password: '',
    role: '',
});

const pointsForm = useForm({
    action: 'add' as 'add' | 'deduct',
    amount: 50,
    reason: '',
});

function applyFilters() {
    router.get('/admin/users', { search: search.value, role: role.value }, { preserveState: true, replace: true });
}

function clearFilters() {
    search.value = '';
    role.value = '';
    applyFilters();
}

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.post('/admin/users', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
        preserveScroll: true,
    });
}

function openEditModal(user: UserItem) {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.password = '';
    editForm.role = user.roles[0] || '';
    editForm.clearErrors();
    showEditModal.value = true;
}

function submitEdit() {
    if (!editingUser.value) return;
    editForm.put(`/admin/users/${editingUser.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            editingUser.value = null;
        },
        preserveScroll: true,
    });
}

function openPointsModal(user: UserItem) {
    pointsUser.value = user;
    pointsForm.reset();
    pointsForm.action = 'add';
    pointsForm.amount = 50;
    pointsForm.reason = '';
    pointsForm.clearErrors();
    showPointsModal.value = true;
}

function submitPoints() {
    if (!pointsUser.value) return;
    pointsForm.post(`/admin/users/${pointsUser.value.id}/adjust-points`, {
        onSuccess: () => {
            showPointsModal.value = false;
            pointsUser.value = null;
        },
        preserveScroll: true,
    });
}

function destroyUser(user: UserItem) {
    if (confirm(`Delete "${user.name}"? This action cannot be undone.`)) {
        router.delete(`/admin/users/${user.id}`, { preserveScroll: true });
    }
}

function getRoleName(r: RoleOption): string {
    if (typeof r === 'string') return r;
    return r.label || r.value;
}

function getRoleValue(r: RoleOption): string {
    if (typeof r === 'string') return r;
    return r.value;
}

function initials(name: string): string {
    return name
        .split(' ')
        .map((p) => p[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
}

function rolePill(r: string): string {
    const key = r.toLowerCase();
    if (key.includes('admin')) return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300';
    if (key.includes('staff')) return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300';
    if (key.includes('customer')) return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300';
    return 'bg-neutral-100 text-neutral-700 dark:bg-neutral-800 dark:text-neutral-300';
}
</script>

<template>
    <Head title="User Accounts" />

    <div class="p-6 space-y-6 w-full">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">User Accounts</h1>
                <p class="text-xs text-neutral-500">Manage registered users, loyalty points balances, and administrative roles.</p>
            </div>
            <button
                v-if="canManageUsers"
                @click="openCreateModal"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow transition-colors hover:bg-emerald-700 cursor-pointer"
            >
                <Plus class="w-4 h-4" /> Create User
            </button>
        </div>

        <!-- Filters -->
        <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="relative sm:col-span-2">
                    <Search class="w-4 h-4 absolute left-3 top-2.5 text-neutral-400" />
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search user name or email..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                    />
                </div>
                <select v-model="role" @change="applyFilters" class="w-full px-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500">
                    <option value="">All Roles</option>
                    <option v-for="r in roles" :key="getRoleValue(r)" :value="getRoleValue(r)">{{ getRoleName(r) }}</option>
                </select>
            </div>

            <div class="flex items-center justify-end gap-2">
                <button @click="applyFilters" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700 cursor-pointer">Filter</button>
                <button @click="clearFilters" class="px-3 py-1.5 bg-neutral-200 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 rounded-lg text-xs font-semibold cursor-pointer">Clear</button>
            </div>
        </div>

        <!-- Table -->
        <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm overflow-x-auto">
            <table class="w-full text-left border-collapse text-xs">
                <thead>
                    <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-semibold uppercase">
                        <th class="py-3 px-3">ID</th>
                        <th class="py-3 px-3">Name</th>
                        <th class="py-3 px-3">Email</th>
                        <th class="py-3 px-3">Roles</th>
                        <th class="py-3 px-3">Loyalty Points</th>
                        <th class="py-3 px-3">Registered</th>
                        <th class="py-3 px-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40 transition-colors"
                    >
                        <td class="py-3 px-3 font-mono font-bold text-neutral-900 dark:text-white">#{{ user.id }}</td>
                        <td class="py-3 px-3">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                    {{ initials(user.name) }}
                                </span>
                                <span class="font-semibold text-neutral-900 dark:text-white">{{ user.name }}</span>
                            </div>
                        </td>
                        <td class="py-3 px-3 text-neutral-500">{{ user.email }}</td>
                        <td class="py-3 px-3">
                            <span
                                v-for="r in user.roles"
                                :key="r"
                                :class="['mr-1 inline-block rounded-full px-2 py-0.5 text-[10px] font-bold capitalize', rolePill(r)]"
                            >
                                {{ r }}
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            <button
                                type="button"
                                @click="openPointsModal(user)"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-emerald-500/10 hover:bg-emerald-500/20 text-emerald-600 dark:text-emerald-400 font-bold text-xs transition-colors cursor-pointer"
                                title="Click to adjust points"
                            >
                                <Sparkles class="w-3.5 h-3.5 text-amber-500" />
                                <span>{{ user.points }} pts</span>
                            </button>
                        </td>
                        <td class="py-3 px-3 text-neutral-500">{{ user.created_at }}</td>
                        <td class="py-3 px-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <button
                                    type="button"
                                    @click="openPointsModal(user)"
                                    class="p-1 text-amber-600 hover:text-amber-700 hover:bg-amber-50 dark:hover:bg-amber-950/40 rounded transition-colors cursor-pointer"
                                    title="Adjust Loyalty Points"
                                >
                                    <Sparkles class="w-4 h-4" />
                                </button>
                                <Link :href="`/admin/users/${user.id}`" class="p-1 text-neutral-400 hover:text-neutral-900 dark:hover:text-white rounded" title="View Profile">
                                    <ArrowUpRight class="w-4 h-4" />
                                </Link>
                                <template v-if="canManageUsers">
                                    <button @click="openEditModal(user)" class="p-1 text-emerald-600 hover:text-emerald-700 rounded cursor-pointer" title="Edit">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button @click="destroyUser(user)" class="p-1 text-rose-400 hover:text-rose-600 rounded cursor-pointer" title="Delete">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </template>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td colspan="7" class="py-8 text-center text-xs text-neutral-400">No users found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Adjust Points Modal -->
    <Teleport to="body">
        <div v-if="showPointsModal && pointsUser" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @click.self="showPointsModal = false">
            <div class="w-full max-w-md rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xl space-y-4">
                <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-emerald-500/10 text-emerald-600">
                            <Sparkles class="w-5 h-5" />
                        </div>
                        <div>
                            <h2 class="text-base font-black text-neutral-900 dark:text-white">Adjust Customer Points</h2>
                            <p class="text-xs text-neutral-500">{{ pointsUser.name }} (Current: {{ pointsUser.points }} pts)</p>
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
                        <Label for="points-amount">Points Amount</Label>
                        <Input id="points-amount" v-model.number="pointsForm.amount" type="number" min="1" max="50000" required />
                        <InputError :message="pointsForm.errors.amount" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="points-reason">Reason / Memo (Required for Audit Trail)</Label>
                        <Input id="points-reason" v-model="pointsForm.reason" placeholder="e.g. Loyalty promotion bonus, special event reward, etc." required />
                        <InputError :message="pointsForm.errors.reason" />
                    </div>

                    <div class="p-3 rounded-xl bg-neutral-50 dark:bg-neutral-800/50 border border-neutral-100 dark:border-neutral-800 text-xs flex items-center justify-between font-bold">
                        <span class="text-neutral-500">Calculated New Balance:</span>
                        <span class="text-emerald-600 dark:text-emerald-400">
                            {{ pointsForm.action === 'add' ? (pointsUser.points + (pointsForm.amount || 0)) : (pointsUser.points - (pointsForm.amount || 0)) }} pts
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

    <!-- Create User Modal -->
    <Teleport to="body">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @click.self="showCreateModal = false">
            <div class="w-full max-w-md rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Create User</h2>
                    <button @click="showCreateModal = false" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-white">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="create-name">Name *</Label>
                        <Input id="create-name" v-model="createForm.name" type="text" required />
                        <InputError :message="createForm.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="create-email">Email *</Label>
                        <Input id="create-email" v-model="createForm.email" type="email" required />
                        <InputError :message="createForm.errors.email" />
                    </div>
                    <div class="space-y-2">
                        <Label for="create-password">Password *</Label>
                        <Input id="create-password" v-model="createForm.password" type="password" required />
                        <InputError :message="createForm.errors.password" />
                    </div>
                    <div class="space-y-2">
                        <Label for="create-role">Role *</Label>
                        <select
                            id="create-role"
                            v-model="createForm.role"
                            required
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="" disabled>Select a role</option>
                            <option v-for="r in roles" :key="getRoleValue(r)" :value="getRoleValue(r)">
                                {{ getRoleName(r) }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.role" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <Button variant="outline" type="button" @click="showCreateModal = false">Cancel</Button>
                        <Button type="submit" :disabled="createForm.processing">
                            {{ createForm.processing ? 'Creating...' : 'Create User' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Edit User Modal -->
    <Teleport to="body">
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4" @click.self="showEditModal = false">
            <div class="w-full max-w-md rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Edit User</h2>
                    <button @click="showEditModal = false" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-white">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="edit-name">Name *</Label>
                        <Input id="edit-name" v-model="editForm.name" type="text" required />
                        <InputError :message="editForm.errors.name" />
                    </div>
                    <div class="space-y-2">
                        <Label for="edit-email">Email *</Label>
                        <Input id="edit-email" v-model="editForm.email" type="email" required />
                        <InputError :message="editForm.errors.email" />
                    </div>
                    <div class="space-y-2">
                        <Label for="edit-password">Password (leave blank to keep current)</Label>
                        <Input id="edit-password" v-model="editForm.password" type="password" />
                        <InputError :message="editForm.errors.password" />
                    </div>
                    <div class="space-y-2">
                        <Label for="edit-role">Role *</Label>
                        <select
                            id="edit-role"
                            v-model="editForm.role"
                            required
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="" disabled>Select a role</option>
                            <option v-for="r in roles" :key="getRoleValue(r)" :value="getRoleValue(r)">
                                {{ getRoleName(r) }}
                            </option>
                        </select>
                        <InputError :message="editForm.errors.role" />
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <Button variant="outline" type="button" @click="showEditModal = false">Cancel</Button>
                        <Button type="submit" :disabled="editForm.processing">
                            {{ editForm.processing ? 'Saving...' : 'Save Changes' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
