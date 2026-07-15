<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Dumbbell, X } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

interface Court {
    id: number;
    venue_id?: number | null;
    venue?: { id: number; name: string } | null;
    name: string;
    slug: string;
    sport_type: string;
    description?: string | null;
    status: 'available' | 'maintenance' | 'closed';
    base_price: string;
    slot_duration_minutes: number;
    staff_count: number;
}

interface SelectOption {
    value: string;
    label: string;
}

interface VenueOption {
    id: number;
    name: string;
}

const props = defineProps<{
    courts: Court[];
    sportTypes?: SelectOption[];
    statuses?: SelectOption[];
    venues?: VenueOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Court Staff Dashboard', href: '/staff/dashboard' },
            { title: 'Assigned Courts', href: '/staff/courts' },
        ],
    },
});

const statusVariant: Record<Court['status'], string> = {
    available: 'default',
    maintenance: 'secondary',
    closed: 'destructive',
};

const showCreateModal = ref(false);

const createForm = useForm({
    name: '',
    venue_id: '' as string | number,
    sport_type: props.sportTypes?.[0]?.value || 'pickleball',
    description: '',
    status: 'available',
    base_price: '25.00',
    slot_duration_minutes: 60,
    buffer_minutes: 0,
    is_active: true,
});

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.post('/staff/courts', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Assigned Courts - Staff" />

    <div class="flex h-full flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Assigned Courts</h1>
                <p class="text-xs text-neutral-500">View your assigned courts or register a new court to your schedule.</p>
            </div>

            <Button @click="openCreateModal" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                <Plus class="mr-1.5 h-4 w-4" />
                Add Court
            </Button>
        </div>

        <div class="overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Court Name</th>
                        <th class="px-4 py-3 font-medium">Venue</th>
                        <th class="px-4 py-3 font-medium">Sport Type</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Hourly Rate</th>
                        <th class="px-4 py-3 font-medium">Slot Duration</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-100 dark:divide-neutral-800">
                    <tr
                        v-for="court in courts"
                        :key="court.id"
                        class="hover:bg-neutral-50 dark:hover:bg-neutral-800/40"
                    >
                        <td class="px-4 py-3 font-medium text-neutral-900 dark:text-white">{{ court.name }}</td>
                        <td class="px-4 py-3 text-neutral-600 dark:text-neutral-300">{{ court.venue?.name || 'Unassigned' }}</td>
                        <td class="px-4 py-3 capitalize text-neutral-600 dark:text-neutral-300">
                            {{ court.sport_type.replace('-', ' ') }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge :variant="statusVariant[court.status] as never">
                                {{ court.status }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3 font-medium">₱{{ court.base_price }}</td>
                        <td class="px-4 py-3 text-neutral-600 dark:text-neutral-300">{{ court.slot_duration_minutes }} mins</td>
                    </tr>
                    <tr v-if="courts.length === 0">
                        <td colspan="5" class="px-4 py-10 text-center text-muted-foreground">
                            No assigned courts found. Click "Add Court" to register a court.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Centered Add Court Modal for Staff -->
    <Teleport to="body">
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 overflow-y-auto"
            @click.self="showCreateModal = false"
        >
            <div class="w-full max-w-lg rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-2xl space-y-5 my-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Add Court</h2>
                        <p class="text-xs text-neutral-500">Create a court entry for your assigned facility.</p>
                    </div>
                    <button @click="showCreateModal = false" class="text-neutral-400 hover:text-neutral-600 dark:hover:text-white">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4 text-xs">
                    <div class="space-y-2">
                        <Label for="staff-court-name">Court Name *</Label>
                        <Input id="staff-court-name" v-model="createForm.name" type="text" placeholder="e.g. Court 4 - Indoor" required />
                        <InputError :message="createForm.errors.name" />
                    </div>

                    <div v-if="venues && venues.length > 0" class="space-y-2">
                        <Label for="staff-court-venue">Assigned Venue</Label>
                        <select
                            id="staff-court-venue"
                            v-model="createForm.venue_id"
                            class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white"
                        >
                            <option value="">No Venue Selected</option>
                            <option v-for="v in venues" :key="v.id" :value="v.id">
                                {{ v.name }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.venue_id" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <Label for="staff-court-sport">Sport Type *</Label>
                            <select
                                id="staff-court-sport"
                                v-model="createForm.sport_type"
                                required
                                class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white capitalize"
                            >
                                <option v-for="st in (sportTypes || [{ value: 'pickleball', label: 'Pickleball' }])" :key="st.value" :value="st.value">
                                    {{ st.label }}
                                </option>
                            </select>
                            <InputError :message="createForm.errors.sport_type" />
                        </div>

                        <div class="space-y-2">
                            <Label for="staff-court-status">Status *</Label>
                            <select
                                id="staff-court-status"
                                v-model="createForm.status"
                                required
                                class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white capitalize"
                            >
                                <option v-for="st in (statuses || [{ value: 'available', label: 'Available' }, { value: 'maintenance', label: 'Maintenance' }])" :key="st.value" :value="st.value">
                                    {{ st.label }}
                                </option>
                            </select>
                            <InputError :message="createForm.errors.status" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-2">
                            <Label for="staff-court-price">Hourly Rate ($) *</Label>
                            <Input id="staff-court-price" v-model="createForm.base_price" type="number" step="0.01" min="0" required />
                            <InputError :message="createForm.errors.base_price" />
                        </div>

                        <div class="space-y-2">
                            <Label for="staff-court-duration">Slot Mins *</Label>
                            <Input id="staff-court-duration" v-model.number="createForm.slot_duration_minutes" type="number" min="15" step="15" required />
                            <InputError :message="createForm.errors.slot_duration_minutes" />
                        </div>

                        <div class="space-y-2">
                            <Label for="staff-court-buffer">Buffer Mins *</Label>
                            <Input id="staff-court-buffer" v-model.number="createForm.buffer_minutes" type="number" min="0" step="5" required />
                            <InputError :message="createForm.errors.buffer_minutes" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="staff-court-description">Description</Label>
                        <textarea
                            id="staff-court-description"
                            v-model="createForm.description"
                            rows="2"
                            placeholder="Court details or surface info..."
                            class="w-full rounded-xl border border-neutral-300 dark:border-neutral-700 p-2.5 dark:bg-neutral-800 text-neutral-900 dark:text-white"
                        />
                        <InputError :message="createForm.errors.description" />
                    </div>

                    <div class="flex justify-end gap-3 pt-3">
                        <Button variant="outline" type="button" @click="showCreateModal = false">Cancel</Button>
                        <Button type="submit" :disabled="createForm.processing" class="bg-emerald-600 hover:bg-emerald-700 text-white">
                            {{ createForm.processing ? 'Creating...' : 'Create Court' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
