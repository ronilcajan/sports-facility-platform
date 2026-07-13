<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Users, Search, ArrowUpRight } from '@lucide/vue';

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

const props = defineProps<{
    users: PaginatedUsers;
    filters: { search?: string; role?: string };
    roles: { value: string; label: string }[];
}>();

const breadcrumbs = [
    { title: 'Super Admin Overview', href: '/admin/dashboard' },
    { title: 'User Accounts', href: '/admin/users' },
];

const search = ref(props.filters.search || '');
const role = ref(props.filters.role || '');

function applyFilters() {
    router.get('/admin/users', { search: search.value, role: role.value }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="User Accounts - Super Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="p-6 space-y-6 max-w-7xl mx-auto">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Registered User Accounts</h1>
                <p class="text-xs text-neutral-500">View user credentials and inspect personal booking histories.</p>
            </div>

            <!-- Filters Bar -->
            <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm flex flex-col sm:flex-row items-center gap-3">
                <div class="relative flex-1 w-full">
                    <Search class="w-4 h-4 absolute left-3 top-2.5 text-neutral-400" />
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Search user name or email..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                    />
                </div>

                <button @click="applyFilters" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-semibold">Search</button>
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
                            <th class="py-3 px-3">Registered Date</th>
                            <th class="py-3 px-3 text-right">Booking History</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40 transition-colors">
                            <td class="py-3 px-3 font-mono font-bold text-neutral-900 dark:text-white">#{{ user.id }}</td>
                            <td class="py-3 px-3 font-semibold text-neutral-900 dark:text-white">{{ user.name }}</td>
                            <td class="py-3 px-3 text-neutral-600 dark:text-neutral-300">{{ user.email }}</td>
                            <td class="py-3 px-3">
                                <span v-for="r in user.roles" :key="r" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-neutral-100 dark:bg-neutral-800 text-neutral-800 dark:text-neutral-200 mr-1 capitalize">
                                    {{ r }}
                                </span>
                            </td>
                            <td class="py-3 px-3 text-neutral-500">{{ user.created_at }}</td>
                            <td class="py-3 px-3 text-right">
                                <Link :href="`/admin/users/${user.id}`" class="inline-flex items-center gap-1 text-emerald-600 hover:underline font-semibold">
                                    <span>Inspect History</span>
                                    <ArrowUpRight class="w-3.5 h-3.5" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
