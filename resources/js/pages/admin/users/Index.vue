<script setup lang="ts">
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import { Users, Search, Plus, Pencil, Trash2, X } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

interface UserItem {
    id: number;
    name: string;
    email: string;
    roles: string[];
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
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingUser = ref<UserItem | null>(null);

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

function applyFilters() {
    router.get('/admin/users', { search: search.value }, { preserveState: true, replace: true });
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

function destroyUser(user: UserItem) {
    if (confirm(`Delete "${user.name}"? This action cannot be undone.`)) {
        router.delete(`/admin/users/${user.id}`, { preserveScroll: true });
    }
}

function getRoleName(role: RoleOption): string {
    if (typeof role === 'string') return role;
    return role.label || role.value;
}

function getRoleValue(role: RoleOption): string {
    if (typeof role === 'string') return role;
    return role.value;
}
</script>

<template>
    <Head title="User Accounts" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="User Accounts"
                description="Manage registered users and their roles."
            />
            <Button v-if="canManageUsers" @click="openCreateModal">
                <Plus class="mr-1.5 h-4 w-4" />
                Create User
            </Button>
        </div>

        <!-- Search Bar -->
        <div class="flex items-center gap-3">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                <input
                    v-model="search"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Search user name or email..."
                    class="w-full rounded-lg border border-input bg-background py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                />
            </div>
            <Button @click="applyFilters" size="sm">Search</Button>
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">ID</th>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Email</th>
                        <th class="px-4 py-3 font-medium">Roles</th>
                        <th class="px-4 py-3 font-medium">Registered</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="user in users.data"
                        :key="user.id"
                        class="border-t border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <td class="px-4 py-3 font-mono font-bold">#{{ user.id }}</td>
                        <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                        <td class="px-4 py-3">
                            <Badge v-for="r in user.roles" :key="r" variant="secondary" class="mr-1 capitalize">
                                {{ r }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 text-muted-foreground">{{ user.created_at }}</td>
                        <td class="px-4 py-3">
                            <div v-if="canManageUsers" class="flex justify-end gap-2">
                                <Button variant="outline" size="sm" @click="openEditModal(user)">
                                    <Pencil class="mr-1 h-3.5 w-3.5" />
                                    Edit
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="destroyUser(user)"
                                    class="text-destructive hover:text-destructive"
                                >
                                    <Trash2 class="mr-1 h-3.5 w-3.5" />
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="users.data.length === 0">
                        <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">
                            No users found.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create User Modal -->
    <Teleport to="body">
        <div v-if="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showCreateModal = false">
            <div class="w-full max-w-md rounded-xl border border-border bg-background p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Create User</h2>
                    <button @click="showCreateModal = false" class="text-muted-foreground hover:text-foreground">
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
                            <option v-for="role in roles" :key="getRoleValue(role)" :value="getRoleValue(role)">
                                {{ getRoleName(role) }}
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
        <div v-if="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showEditModal = false">
            <div class="w-full max-w-md rounded-xl border border-border bg-background p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Edit User</h2>
                    <button @click="showEditModal = false" class="text-muted-foreground hover:text-foreground">
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
                            <option v-for="role in roles" :key="getRoleValue(role)" :value="getRoleValue(role)">
                                {{ getRoleName(role) }}
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
