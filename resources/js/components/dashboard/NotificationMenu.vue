<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Bell, Check, CheckCheck } from '@lucide/vue';

interface NotificationItem {
    id: string;
    data: {
        booking_id: number;
        court_id: number;
        court_name: string;
        customer_name: string;
        action: string;
        status: string;
        message: string;
    };
    created_at: string;
}

const props = defineProps<{
    notifications: NotificationItem[];
}>();

const isOpen = ref(false);

const actionForm = useForm({});

function markAsRead(id: string) {
    actionForm.patch(`/staff/notifications/${id}/read`, {
        preserveScroll: true,
    });
}

function markAllAsRead() {
    actionForm.patch('/staff/notifications/read-all', {
        preserveScroll: true,
    });
}
</script>

<template>
    <div class="relative">
        <button
            @click="isOpen = !isOpen"
            class="relative p-2 rounded-lg text-neutral-600 dark:text-neutral-300 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors focus:outline-none"
            title="Notifications"
        >
            <Bell class="w-5 h-5" />
            <span
                v-if="notifications.length > 0"
                class="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-600 text-[10px] font-bold text-white shadow-sm"
            >
                {{ notifications.length }}
            </span>
        </button>

        <!-- Dropdown Menu -->
        <div
            v-if="isOpen"
            class="absolute right-0 mt-2 w-80 sm:w-96 rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-xl z-50 overflow-hidden"
        >
            <div class="p-3 border-b border-neutral-100 dark:border-neutral-800 flex items-center justify-between bg-neutral-50/50 dark:bg-neutral-900/50">
                <span class="font-semibold text-sm text-neutral-900 dark:text-white flex items-center gap-1.5">
                    <Bell class="w-4 h-4 text-emerald-600" />
                    Staff Alerts
                </span>

                <button
                    v-if="notifications.length > 0"
                    @click="markAllAsRead"
                    class="text-xs text-emerald-600 dark:text-emerald-400 hover:underline flex items-center gap-1 font-medium"
                >
                    <CheckCheck class="w-3.5 h-3.5" /> Mark all read
                </button>
            </div>

            <div class="max-h-80 overflow-y-auto divide-y divide-neutral-100 dark:divide-neutral-800">
                <div
                    v-for="item in notifications"
                    :key="item.id"
                    class="p-3 hover:bg-neutral-50 dark:hover:bg-neutral-800/50 transition-colors flex items-start justify-between gap-3"
                >
                    <div class="space-y-1 text-xs">
                        <p class="font-medium text-neutral-900 dark:text-white">
                            {{ item.data.message }}
                        </p>
                        <span class="text-[10px] text-neutral-400">
                            Court: {{ item.data.court_name }}
                        </span>
                    </div>

                    <button
                        @click="markAsRead(item.id)"
                        class="text-neutral-400 hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors p-1"
                        title="Mark read"
                    >
                        <Check class="w-4 h-4" />
                    </button>
                </div>

                <div v-if="notifications.length === 0" class="p-6 text-center text-xs text-neutral-400">
                    No new unread notifications.
                </div>
            </div>
        </div>
    </div>
</template>
