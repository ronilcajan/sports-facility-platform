<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { UserCheck, Plus, Dumbbell, Trash2, CheckCircle2, Shield } from '@lucide/vue';

interface StaffUser {
    id: number;
    name: string;
    email: string;
    assigned_courts: { id: number; name: string }[];
}

interface CourtItem {
    id: number;
    name: string;
    sport_type: string;
}

const props = defineProps<{
    staffMembers: StaffUser[];
    courts: CourtItem[];
}>();

const breadcrumbs = [
    { title: 'Super Admin Overview', href: '/admin/dashboard' },
    { title: 'Court Staff Accounts', href: '/admin/staff' },
];

const isCreateModalOpen = ref(false);

const createForm = useForm({
    name: '',
    email: '',
    password: '',
    court_ids: [] as number[],
});

function submitCreate() {
    createForm.post('/admin/staff', {
        onSuccess: () => {
            createForm.reset();
            isCreateModalOpen.value = false;
        },
    });
}

const editForm = useForm({
    name: '',
    email: '',
    court_ids: [] as number[],
});

const editingUserId = ref<number | null>(null);

function startEditing(staff: StaffUser) {
    editingUserId.value = staff.id;
    editForm.name = staff.name;
    editForm.email = staff.email;
    editForm.court_ids = staff.assigned_courts.map(c => c.id);
}

function submitUpdate(staffId: number) {
    editForm.put(`/admin/staff/${staffId}`, {
        onSuccess: () => {
            editingUserId.value = null;
        },
    });
}

const deleteForm = useForm({});

function deleteStaff(staffId: number) {
    if (confirm('Are you sure you want to remove this staff account?')) {
        deleteForm.delete(`/admin/staff/${staffId}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Court Staff Management - Super Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 max-w-7xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Court Staff Accounts & Assignments</h1>
                    <p class="text-xs text-neutral-500">Create staff user accounts and assign them to specific courts.</p>
                </div>

                <button
                    @click="isCreateModalOpen = true"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow transition-colors flex items-center gap-2"
                >
                    <Plus class="w-4 h-4" /> Create New Staff Member
                </button>
            </div>

            <!-- Create Modal Form -->
            <div v-if="isCreateModalOpen" class="p-5 rounded-2xl border border-emerald-200 dark:border-emerald-900 bg-emerald-50/50 dark:bg-emerald-950/20 space-y-4">
                <h3 class="font-bold text-sm text-neutral-900 dark:text-white">New Court Staff Credentials</h3>

                <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1">Full Name</label>
                            <input v-model="createForm.name" type="text" required class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2 dark:bg-neutral-800 text-neutral-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1">Email Address</label>
                            <input v-model="createForm.email" type="email" required class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2 dark:bg-neutral-800 text-neutral-900 dark:text-white" />
                        </div>
                        <div>
                            <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1">Password</label>
                            <input v-model="createForm.password" type="password" required class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2 dark:bg-neutral-800 text-neutral-900 dark:text-white" />
                        </div>
                    </div>

                    <div>
                        <label class="block text-neutral-700 dark:text-neutral-300 font-medium mb-1.5">Assign Courts</label>
                        <div class="flex flex-wrap gap-3">
                            <label v-for="c in courts" :key="c.id" class="flex items-center gap-1.5 cursor-pointer bg-white dark:bg-neutral-800 px-3 py-1.5 rounded-lg border border-neutral-200 dark:border-neutral-700">
                                <input type="checkbox" :value="c.id" v-model="createForm.court_ids" class="rounded text-emerald-600 focus:ring-emerald-500" />
                                <span>{{ c.name }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button type="button" @click="isCreateModalOpen = false" class="px-3 py-1.5 bg-neutral-200 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 rounded-lg">Cancel</button>
                        <button type="submit" :disabled="createForm.processing" class="px-4 py-1.5 bg-emerald-600 text-white rounded-lg font-semibold">Save Staff Account</button>
                    </div>
                </form>
            </div>

            <!-- Staff List Table -->
            <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-5 shadow-sm overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="border-b border-neutral-100 dark:border-neutral-800 text-neutral-400 font-semibold uppercase">
                            <th class="py-3 px-3">Staff Name</th>
                            <th class="py-3 px-3">Email Address</th>
                            <th class="py-3 px-3">Assigned Courts</th>
                            <th class="py-3 px-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        <tr v-for="staff in staffMembers" :key="staff.id" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40 transition-colors">
                            <td class="py-3 px-3 font-bold text-neutral-900 dark:text-white">
                                <div v-if="editingUserId === staff.id" class="space-y-1">
                                    <input v-model="editForm.name" class="p-1 rounded border border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-xs" />
                                </div>
                                <span v-else>{{ staff.name }}</span>
                            </td>

                            <td class="py-3 px-3 text-neutral-600 dark:text-neutral-300">
                                <div v-if="editingUserId === staff.id">
                                    <input v-model="editForm.email" class="p-1 rounded border border-neutral-300 dark:border-neutral-700 dark:bg-neutral-800 text-xs" />
                                </div>
                                <span v-else>{{ staff.email }}</span>
                            </td>

                            <td class="py-3 px-3">
                                <div v-if="editingUserId === staff.id" class="flex flex-wrap gap-2">
                                    <label v-for="c in courts" :key="c.id" class="flex items-center gap-1">
                                        <input type="checkbox" :value="c.id" v-model="editForm.court_ids" />
                                        <span class="text-[11px]">{{ c.name }}</span>
                                    </label>
                                </div>

                                <div v-else class="flex flex-wrap gap-1">
                                    <span
                                        v-for="court in staff.assigned_courts"
                                        :key="court.id"
                                        class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800 font-medium text-[11px]"
                                    >
                                        {{ court.name }}
                                    </span>
                                    <span v-if="staff.assigned_courts.length === 0" class="text-neutral-400 italic">No court assigned</span>
                                </div>
                            </td>

                            <td class="py-3 px-3 text-right">
                                <div v-if="editingUserId === staff.id" class="flex items-center justify-end gap-2">
                                    <button @click="submitUpdate(staff.id)" class="px-2.5 py-1 bg-emerald-600 text-white rounded text-xs">Save</button>
                                    <button @click="editingUserId = null" class="px-2.5 py-1 bg-neutral-200 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 rounded text-xs">Cancel</button>
                                </div>

                                <div v-else class="flex items-center justify-end gap-2">
                                    <button @click="startEditing(staff)" class="text-xs text-emerald-600 hover:underline">Edit Courts</button>
                                    <button @click="deleteStaff(staff.id)" class="p-1 text-rose-500 hover:text-rose-700">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>

                        <tr v-if="staffMembers.length === 0">
                            <td colSpan="4" class="py-8 text-center text-xs text-neutral-400">No staff user accounts found. Click "Create New Staff Member" above.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
