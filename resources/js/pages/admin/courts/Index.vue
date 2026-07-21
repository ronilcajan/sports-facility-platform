<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, X, Dumbbell, Users, Image as ImageIcon } from '@lucide/vue';
import CourtController from '@/actions/App/Http/Controllers/Admin/CourtController';
import CourtImageManagerModal from '@/components/admin/CourtImageManagerModal.vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

interface CourtImage {
    id: number;
    path: string;
    url: string;
    is_primary: boolean;
}

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
    primary_image?: CourtImage | null;
    images?: CourtImage[];
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

const statusPill: Record<Court['status'], string> = {
    available: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300',
    maintenance: 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300',
    closed: 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
};

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingCourt = ref<Court | null>(null);
const showImageModal = ref(false);
const selectedCourtForImages = ref<Court | null>(null);

function openImageModal(court: Court) {
    selectedCourtForImages.value = court;
    showImageModal.value = true;
}

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
    image: null as File | null,
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
    image: null as File | null,
    delete_image: false,
});

const createPreview = ref<string | null>(null);
const editPreview = ref<string | null>(null);

function onCreateImageChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit.');
            return;
        }
        createForm.image = file;
        if (createPreview.value) URL.revokeObjectURL(createPreview.value);
        createPreview.value = URL.createObjectURL(file);
    }
}

function onEditImageChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit.');
            return;
        }
        editForm.image = file;
        editForm.delete_image = false;
        if (editPreview.value) URL.revokeObjectURL(editPreview.value);
        editPreview.value = URL.createObjectURL(file);
    }
}

function removeEditImage() {
    editForm.image = null;
    editForm.delete_image = true;
    if (editPreview.value) {
        URL.revokeObjectURL(editPreview.value);
        editPreview.value = null;
    }
}

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    if (createPreview.value) {
        URL.revokeObjectURL(createPreview.value);
        createPreview.value = null;
    }
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.post(CourtController.store().url, {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            if (createPreview.value) {
                URL.revokeObjectURL(createPreview.value);
                createPreview.value = null;
            }
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
    editForm.image = null;
    editForm.delete_image = false;
    if (editPreview.value) {
        URL.revokeObjectURL(editPreview.value);
        editPreview.value = null;
    }
    editForm.clearErrors();
    showEditModal.value = true;
}

function submitEdit() {
    if (!editingCourt.value) return;
    editForm.transform((data) => ({ ...data, _method: 'put' })).post(CourtController.update(editingCourt.value.id).url, {
        onSuccess: () => {
            showEditModal.value = false;
            editingCourt.value = null;
            if (editPreview.value) {
                URL.revokeObjectURL(editPreview.value);
                editPreview.value = null;
            }
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

    <div class="p-6 space-y-6 w-full">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Courts</h1>
                <p class="text-xs text-neutral-500">Manage courts, pricing, and staff assignments.</p>
            </div>
            <button
                @click="openCreateModal"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow transition-colors hover:bg-emerald-700"
            >
                <Plus class="w-4 h-4" /> Add Court
            </button>
        </div>

        <!-- Card grid -->
        <div
            v-if="courts.length"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="court in courts"
                :key="court.id"
                class="group flex flex-col overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="relative aspect-video overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                    <img
                        v-if="court.primary_image?.url"
                        :src="court.primary_image.url"
                        :alt="court.name"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div v-else class="flex h-full w-full items-center justify-center text-neutral-300 dark:text-neutral-600">
                        <Dumbbell class="h-10 w-10" />
                    </div>
                    <span
                        :class="['absolute right-2 top-2 rounded-full px-2 py-0.5 text-[10px] font-bold capitalize shadow-sm', statusPill[court.status]]"
                    >
                        {{ court.status }}
                    </span>
                </div>

                <div class="flex flex-1 flex-col gap-2 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <h3 class="truncate font-semibold leading-tight text-neutral-900 dark:text-white">{{ court.name }}</h3>
                            <p class="truncate text-xs text-neutral-500">{{ court.venue?.name || 'Unassigned' }}</p>
                        </div>
                        <span class="shrink-0 text-sm font-bold text-emerald-600 dark:text-emerald-400">₱{{ court.base_price }}</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-[11px] text-neutral-500">
                        <span class="rounded-full bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 capitalize">{{ court.sport_type.replace('-', ' ') }}</span>
                        <span class="inline-flex items-center gap-1"><Users class="h-3 w-3" /> {{ court.staff_count }} staff</span>
                    </div>

                    <div class="mt-auto flex items-center gap-2 pt-3">
                        <button
                            @click="openImageModal(court)"
                            class="inline-flex items-center justify-center gap-1 rounded-lg border border-neutral-200 dark:border-neutral-700 px-2.5 py-1.5 text-xs font-semibold text-neutral-700 dark:text-neutral-300 transition-colors hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400"
                            title="Manage Court Images"
                        >
                            <ImageIcon class="h-3.5 w-3.5 text-emerald-600" /> Photos
                        </button>
                        <button
                            @click="openEditModal(court)"
                            class="inline-flex flex-1 items-center justify-center gap-1 rounded-lg border border-neutral-200 dark:border-neutral-700 px-3 py-1.5 text-xs font-semibold text-neutral-700 dark:text-neutral-300 transition-colors hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400"
                        >
                            <Pencil class="h-3.5 w-3.5" /> Edit
                        </button>
                        <button
                            @click="destroy(court)"
                            class="rounded-lg p-2 text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                            title="Delete court"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else
            class="rounded-2xl border border-dashed border-neutral-300 dark:border-neutral-700 py-16 text-center text-sm text-neutral-500"
        >
            No courts yet. Add your first court to get started.
        </div>
    </div>

    <!-- Centered Create Court Modal -->
    <Teleport to="body">
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4 overflow-y-auto"
            @click.self="showCreateModal = false"
        >
            <div class="w-full max-w-lg rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xl space-y-5 my-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Add New Court</h2>
                        <p class="text-xs text-neutral-500">Create a new court and configure pricing &amp; rules.</p>
                    </div>
                    <button @click="showCreateModal = false" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-white">
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

                    <!-- Court Photo Upload -->
                    <div class="space-y-3 rounded-xl border border-input p-3">
                        <Label class="text-xs font-semibold">Court Photo</Label>
                        <div class="flex items-center gap-3">
                            <div v-if="createPreview" class="relative">
                                <img :src="createPreview" alt="Preview" class="h-16 w-24 rounded-lg object-cover border border-input shadow-sm" />
                            </div>
                            <div v-else class="h-16 w-24 rounded-lg border border-dashed border-input flex items-center justify-center text-xs text-muted-foreground">
                                No Photo
                            </div>
                            <input type="file" accept="image/jpeg,image/png,image/jpg,image/webp,image/avif" @change="onCreateImageChange" class="text-xs" />
                        </div>
                        <InputError :message="createForm.errors.image" />
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
            <div class="w-full max-w-lg rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xl space-y-5 my-8">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Edit Court</h2>
                        <p class="text-xs text-neutral-500">Update court details, pricing, and status.</p>
                    </div>
                    <button @click="showEditModal = false" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-white">
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

                    <!-- Court Photo Upload -->
                    <div class="space-y-3 rounded-xl border border-input p-3">
                        <Label class="text-xs font-semibold">Court Photo</Label>
                        <div class="flex items-center gap-3">
                            <div v-if="editPreview" class="relative">
                                <img :src="editPreview" alt="Preview" class="h-16 w-24 rounded-lg object-cover border border-input shadow-sm" />
                                <span class="absolute -top-1.5 -right-1.5 bg-emerald-600 text-white text-[8px] font-bold px-1 rounded-full">New</span>
                            </div>
                            <div v-else-if="editingCourt?.primary_image?.url && !editForm.delete_image" class="relative">
                                <img :src="editingCourt.primary_image.url" alt="Current Photo" class="h-16 w-24 rounded-lg object-cover border border-input shadow-sm" />
                            </div>
                            <div v-else class="h-16 w-24 rounded-lg border border-dashed border-input flex items-center justify-center text-xs text-muted-foreground">
                                No Photo
                            </div>

                            <div class="space-y-1">
                                <input type="file" accept="image/jpeg,image/png,image/jpg,image/webp,image/avif" @change="onEditImageChange" class="text-xs" />
                                <div v-if="editingCourt?.primary_image?.url && !editForm.delete_image" class="pt-0.5">
                                    <button type="button" @click="removeEditImage" class="text-[11px] font-semibold text-rose-600 hover:underline">
                                        Remove photo
                                    </button>
                                </div>
                            </div>
                        </div>
                        <InputError :message="editForm.errors.image" />
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

    <!-- Court Image Manager Modal -->
    <CourtImageManagerModal
        :is-open="showImageModal"
        :court="selectedCourtForImages"
        :upload-route="`/admin/courts/${selectedCourtForImages?.id}/images`"
        :primary-route-prefix="`/admin/courts/${selectedCourtForImages?.id}/images`"
        :delete-route-prefix="`/admin/courts/${selectedCourtForImages?.id}/images`"
        :can-delete="true"
        @close="showImageModal = false"
    />
</template>
