<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, X } from '@lucide/vue';
import CourtController from '@/actions/App/Http/Controllers/Admin/CourtController';
import Heading from '@/components/Heading.vue';
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
    buffer_minutes?: number;
    is_active?: boolean;
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
        breadcrumbs: [{ title: 'Courts', href: '/admin/courts' }],
    },
});

const statusVariant: Record<Court['status'], string> = {
    available: 'default',
    maintenance: 'secondary',
    closed: 'destructive',
};

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingCourt = ref<Court | null>(null);

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

const editForm = useForm({
    name: '',
    venue_id: '' as string | number,
    sport_type: 'pickleball',
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
    createForm.post(CourtController.store().url, {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
        },
        preserveScroll: true,
    });
}

function openEditModal(court: Court) {
    editingCourt.value = court;
    editForm.name = court.name;
    editForm.venue_id = court.venue_id || '';
    editForm.sport_type = court.sport_type;
    editForm.description = court.description || '';
    editForm.status = court.status;
    editForm.base_price = court.base_price;
    editForm.slot_duration_minutes = court.slot_duration_minutes || 60;
    editForm.buffer_minutes = court.buffer_minutes || 0;
    editForm.is_active = court.is_active ?? true;
    editForm.clearErrors();
    showEditModal.value = true;
}

function submitEdit() {
    if (!editingCourt.value) return;
    editForm.put(CourtController.update(editingCourt.value.id).url, {
        onSuccess: () => {
            showEditModal.value = false;
            editingCourt.value = null;
        },
        preserveScroll: true,
    });
}

function destroy(court: Court): void {
    if (confirm(`Delete "${court.name}"? This can be restored later.`)) {
        router.delete(CourtController.destroy(court.id).url, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Courts" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Courts"
                description="Manage courts, pricing, and staff assignments."
            />
            <Button @click="openCreateModal">
                <Plus class="mr-1.5 h-4 w-4" />
                Add court
            </Button>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Venue</th>
                        <th class="px-4 py-3 font-medium">Sport</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 font-medium">Price</th>
                        <th class="px-4 py-3 font-medium">Staff</th>
                        <th class="px-4 py-3 text-right font-medium">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="court in courts"
                        :key="court.id"
                        class="border-t border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <td class="px-4 py-3 font-medium">{{ court.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ court.venue?.name || 'Unassigned' }}</td>
                        <td class="px-4 py-3 capitalize">
                            {{ court.sport_type.replace('-', ' ') }}
                        </td>
                        <td class="px-4 py-3">
                            <Badge
                                :variant="statusVariant[court.status] as never"
                            >
                                {{ court.status }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">${{ court.base_price }}</td>
                        <td class="px-4 py-3">{{ court.staff_count }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <Button variant="outline" size="sm" @click="openEditModal(court)">
                                    <Pencil class="mr-1 h-3.5 w-3.5" />
                                    Edit
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="destroy(court)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="courts.length === 0">
                        <td
                            colspan="6"
                            class="px-4 py-10 text-center text-muted-foreground"
                        >
                            No courts yet. Add your first court to get started.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Centered Create Court Modal -->
    <Teleport to="body">
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 overflow-y-auto"
            @click.self="showCreateModal = false"
        >
            <div class="w-full max-w-lg rounded-xl border border-border bg-background p-6 shadow-xl space-y-5 my-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">Add New Court</h2>
                        <p class="text-xs text-muted-foreground">Create a new court and configure pricing & rules.</p>
                    </div>
                    <button @click="showCreateModal = false" class="text-muted-foreground hover:text-foreground">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="create-court-name">Court Name *</Label>
                        <Input id="create-court-name" v-model="createForm.name" type="text" placeholder="e.g. Center Court" required />
                        <InputError :message="createForm.errors.name" />
                    </div>

                    <div v-if="venues && venues.length > 0" class="space-y-2">
                        <Label for="create-court-venue">Assigned Venue</Label>
                        <select
                            id="create-court-venue"
                            v-model="createForm.venue_id"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">No Venue Assigned</option>
                            <option v-for="v in venues" :key="v.id" :value="v.id">
                                {{ v.name }}
                            </option>
                        </select>
                        <InputError :message="createForm.errors.venue_id" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <Label for="create-court-sport">Sport Type *</Label>
                            <select
                                id="create-court-sport"
                                v-model="createForm.sport_type"
                                required
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring capitalize"
                            >
                                <option v-for="st in (sportTypes || [{ value: 'pickleball', label: 'Pickleball' }])" :key="st.value" :value="st.value">
                                    {{ st.label }}
                                </option>
                            </select>
                            <InputError :message="createForm.errors.sport_type" />
                        </div>

                        <div class="space-y-2">
                            <Label for="create-court-status">Status *</Label>
                            <select
                                id="create-court-status"
                                v-model="createForm.status"
                                required
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring capitalize"
                            >
                                <option v-for="st in (statuses || [{ value: 'available', label: 'Available' }, { value: 'maintenance', label: 'Maintenance' }, { value: 'closed', label: 'Closed' }])" :key="st.value" :value="st.value">
                                    {{ st.label }}
                                </option>
                            </select>
                            <InputError :message="createForm.errors.status" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-2">
                            <Label for="create-court-price">Hourly Rate ($) *</Label>
                            <Input id="create-court-price" v-model="createForm.base_price" type="number" step="0.01" min="0" required />
                            <InputError :message="createForm.errors.base_price" />
                        </div>

                        <div class="space-y-2">
                            <Label for="create-court-duration">Slot Mins *</Label>
                            <Input id="create-court-duration" v-model.number="createForm.slot_duration_minutes" type="number" min="15" step="15" required />
                            <InputError :message="createForm.errors.slot_duration_minutes" />
                        </div>

                        <div class="space-y-2">
                            <Label for="create-court-buffer">Buffer Mins *</Label>
                            <Input id="create-court-buffer" v-model.number="createForm.buffer_minutes" type="number" min="0" step="5" required />
                            <InputError :message="createForm.errors.buffer_minutes" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="create-court-description">Description</Label>
                        <textarea
                            id="create-court-description"
                            v-model="createForm.description"
                            rows="2"
                            placeholder="Brief description of surface, lighting, or indoor/outdoor..."
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <InputError :message="createForm.errors.description" />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="create-court-active"
                            v-model="createForm.is_active"
                            type="checkbox"
                            class="rounded border-input text-primary focus:ring-primary"
                        />
                        <Label for="create-court-active" class="cursor-pointer">Active for online booking</Label>
                    </div>

                    <div class="flex justify-end gap-3 pt-3">
                        <Button variant="outline" type="button" @click="showCreateModal = false">Cancel</Button>
                        <Button type="submit" :disabled="createForm.processing">
                            {{ createForm.processing ? 'Creating...' : 'Create Court' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Centered Edit Court Modal -->
    <Teleport to="body">
        <div
            v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 overflow-y-auto"
            @click.self="showEditModal = false"
        >
            <div class="w-full max-w-lg rounded-xl border border-border bg-background p-6 shadow-xl space-y-5 my-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">Edit Court</h2>
                        <p class="text-xs text-muted-foreground">Update court details, pricing, and status.</p>
                    </div>
                    <button @click="showEditModal = false" class="text-muted-foreground hover:text-foreground">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="edit-court-name">Court Name *</Label>
                        <Input id="edit-court-name" v-model="editForm.name" type="text" required />
                        <InputError :message="editForm.errors.name" />
                    </div>

                    <div v-if="venues && venues.length > 0" class="space-y-2">
                        <Label for="edit-court-venue">Assigned Venue</Label>
                        <select
                            id="edit-court-venue"
                            v-model="editForm.venue_id"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option value="">No Venue Assigned</option>
                            <option v-for="v in venues" :key="v.id" :value="v.id">
                                {{ v.name }}
                            </option>
                        </select>
                        <InputError :message="editForm.errors.venue_id" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <Label for="edit-court-sport">Sport Type *</Label>
                            <select
                                id="edit-court-sport"
                                v-model="editForm.sport_type"
                                required
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring capitalize"
                            >
                                <option v-for="st in (sportTypes || [{ value: 'pickleball', label: 'Pickleball' }])" :key="st.value" :value="st.value">
                                    {{ st.label }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.sport_type" />
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-court-status">Status *</Label>
                            <select
                                id="edit-court-status"
                                v-model="editForm.status"
                                required
                                class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring capitalize"
                            >
                                <option v-for="st in (statuses || [{ value: 'available', label: 'Available' }, { value: 'maintenance', label: 'Maintenance' }, { value: 'closed', label: 'Closed' }])" :key="st.value" :value="st.value">
                                    {{ st.label }}
                                </option>
                            </select>
                            <InputError :message="editForm.errors.status" />
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="space-y-2">
                            <Label for="edit-court-price">Hourly Rate ($) *</Label>
                            <Input id="edit-court-price" v-model="editForm.base_price" type="number" step="0.01" min="0" required />
                            <InputError :message="editForm.errors.base_price" />
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-court-duration">Slot Mins *</Label>
                            <Input id="edit-court-duration" v-model.number="editForm.slot_duration_minutes" type="number" min="15" step="15" required />
                            <InputError :message="editForm.errors.slot_duration_minutes" />
                        </div>

                        <div class="space-y-2">
                            <Label for="edit-court-buffer">Buffer Mins *</Label>
                            <Input id="edit-court-buffer" v-model.number="editForm.buffer_minutes" type="number" min="0" step="5" required />
                            <InputError :message="editForm.errors.buffer_minutes" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-court-description">Description</Label>
                        <textarea
                            id="edit-court-description"
                            v-model="editForm.description"
                            rows="2"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <InputError :message="editForm.errors.description" />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="edit-court-active"
                            v-model="editForm.is_active"
                            type="checkbox"
                            class="rounded border-input text-primary focus:ring-primary"
                        />
                        <Label for="edit-court-active" class="cursor-pointer">Active for online booking</Label>
                    </div>

                    <div class="flex justify-end gap-3 pt-3">
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
