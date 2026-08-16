<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    Plus,
    Pencil,
    Trash2,
    X,
    Dumbbell,
    Users,
    Image as ImageIcon,
    Search,
    Eye,
    Building,
    Clock,
} from '@lucide/vue';
import CourtController from '@/actions/App/Http/Controllers/Admin/CourtController';
import CourtImageManagerModal from '@/components/admin/CourtImageManagerModal.vue';
import EditCourtModal from '@/components/admin/EditCourtModal.vue';
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

const searchQuery = ref('');
const sportFilter = ref('');
const statusFilter = ref('');

const filteredCourts = computed(() => {
    return props.courts.filter((c) => {
        const matchesSearch =
            !searchQuery.value ||
            c.name.toLowerCase().includes(searchQuery.value.toLowerCase()) ||
            (c.venue?.name && c.venue.name.toLowerCase().includes(searchQuery.value.toLowerCase()));

        const matchesSport = !sportFilter.value || c.sport_type === sportFilter.value;
        const matchesStatus = !statusFilter.value || c.status === statusFilter.value;

        return matchesSearch && matchesSport && matchesStatus;
    });
});

const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingCourt = ref<Court | null>(null);
const showImageModal = ref(false);
const selectedCourtIdForImages = ref<number | null>(null);

// Resolve from the live props so the gallery reflects each upload/delete round-trip.
const selectedCourtForImages = computed<Court | null>(
    () => props.courts.find((c) => c.id === selectedCourtIdForImages.value) ?? null,
);

function openImageModal(court: Court) {
    selectedCourtIdForImages.value = court.id;
    showImageModal.value = true;
}

const createForm = useForm({
    name: '',
    // A court needs a venue to be reachable on the venue-first public site.
    venue_id: (props.venues?.[0]?.id ?? '') as string | number,
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
                <p class="text-xs text-neutral-500">Manage courts, view profiles, rates, and staff assignments.</p>
            </div>
            <button
                @click="openCreateModal"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow transition-all hover:bg-emerald-700 hover:-translate-y-0.5"
            >
                <Plus class="w-4 h-4" /> Add Court
            </button>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <Search class="w-4 h-4 absolute left-3 top-2.5 text-neutral-400" />
                    <input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Search by court name or assigned venue..."
                        class="w-full pl-9 pr-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none"
                    />
                </div>

                <select
                    v-model="sportFilter"
                    class="px-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none capitalize"
                >
                    <option value="">All Sport Types</option>
                    <option v-for="st in (sportTypes || [{ value: 'pickleball', label: 'Pickleball' }])" :key="st.value" :value="st.value">
                        {{ st.label }}
                    </option>
                </select>

                <select
                    v-model="statusFilter"
                    class="px-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500 focus:outline-none capitalize"
                >
                    <option value="">All Statuses</option>
                    <option value="available">Available</option>
                    <option value="maintenance">Maintenance</option>
                    <option value="closed">Closed</option>
                </select>

                <button
                    v-if="searchQuery || sportFilter || statusFilter"
                    @click="searchQuery = ''; sportFilter = ''; statusFilter = '';"
                    class="px-3 py-2 bg-neutral-200 dark:bg-neutral-800 text-neutral-700 dark:text-neutral-300 rounded-xl text-xs font-semibold hover:bg-neutral-300 dark:hover:bg-neutral-700"
                >
                    Reset
                </button>
            </div>
        </div>

        <!-- Card Grid -->
        <div
            v-if="filteredCourts.length"
            class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="court in filteredCourts"
                :key="court.id"
                class="group flex flex-col overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm transition-all duration-200 hover:shadow-lg hover:border-emerald-400/50"
            >
                <!-- Court Image Aspect Box -->
                <div class="relative aspect-[16/10] overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                    <img
                        v-if="court.primary_image?.url"
                        :src="court.primary_image.url"
                        :alt="court.name"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div v-else class="flex h-full w-full items-center justify-center text-neutral-300 dark:text-neutral-600">
                        <Dumbbell class="h-10 w-10" />
                    </div>

                    <!-- Overlay Status Badges -->
                    <div class="absolute right-2 top-2 flex flex-col gap-1 items-end">
                        <span :class="['rounded-full px-2.5 py-0.5 text-[10px] font-bold capitalize shadow-sm', statusPill[court.status]]">
                            {{ court.status }}
                        </span>
                        <span :class="['rounded-full px-2 py-0.5 text-[9px] font-bold shadow-sm', court.is_active !== false ? 'bg-emerald-600 text-white' : 'bg-neutral-600 text-white']">
                            {{ court.is_active !== false ? 'Online' : 'Offline' }}
                        </span>
                    </div>

                    <!-- Sport Type Pill -->
                    <span class="absolute left-2 bottom-2 rounded-full bg-black/60 backdrop-blur-md px-2.5 py-0.5 text-[10px] font-bold text-white capitalize">
                        {{ court.sport_type.replace('-', ' ') }}
                    </span>
                </div>

                <!-- Card Content -->
                <div class="flex flex-1 flex-col justify-between p-4 space-y-3">
                    <div class="space-y-1.5">
                        <div class="flex items-start justify-between gap-2">
                            <Link
                                :href="`/admin/courts/${court.id}`"
                                class="font-bold text-base leading-tight text-neutral-900 dark:text-white hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors line-clamp-1"
                            >
                                {{ court.name }}
                            </Link>
                            <span class="shrink-0 text-sm font-black text-emerald-600 dark:text-emerald-400">₱{{ court.base_price }}<span class="text-[10px] font-normal text-neutral-400">/hr</span></span>
                        </div>

                        <!-- Assigned Venue Info -->
                        <div class="flex items-center gap-1.5 text-xs text-neutral-500">
                            <Building class="w-3.5 h-3.5 text-neutral-400 shrink-0" />
                            <span class="truncate">{{ court.venue?.name || 'Unassigned Venue' }}</span>
                        </div>
                    </div>

                    <!-- Details Row -->
                    <div class="flex items-center justify-between text-[11px] text-neutral-500 pt-2 border-t border-neutral-100 dark:border-neutral-800">
                        <span class="inline-flex items-center gap-1">
                            <Clock class="h-3 w-3 text-neutral-400" /> {{ court.slot_duration_minutes }} min
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <Users class="h-3 w-3 text-neutral-400" /> {{ court.staff_count }} staff
                        </span>
                    </div>

                    <!-- Action Button Row -->
                    <div class="pt-2 flex items-center gap-1.5">
                        <!-- View Profile Button -->
                        <Link
                            :href="`/admin/courts/${court.id}`"
                            class="flex-1 inline-flex items-center justify-center gap-1 rounded-xl bg-emerald-600 hover:bg-emerald-700 px-3 py-1.5 text-xs font-bold text-white transition-colors shadow-sm"
                            title="View Court Profile"
                        >
                            <Eye class="h-3.5 w-3.5" /> View Profile
                        </Link>

                        <!-- Photos Button -->
                        <button
                            @click="openImageModal(court)"
                            class="inline-flex items-center justify-center gap-1 rounded-xl border border-neutral-200 dark:border-neutral-700 px-2.5 py-1.5 text-xs font-semibold text-neutral-700 dark:text-neutral-300 transition-colors hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400"
                            title="Manage Court Photos"
                        >
                            <ImageIcon class="h-3.5 w-3.5 text-emerald-600" />
                        </button>

                        <!-- Edit Button -->
                        <button
                            @click="openEditModal(court)"
                            class="inline-flex items-center justify-center gap-1 rounded-xl border border-neutral-200 dark:border-neutral-700 px-2.5 py-1.5 text-xs font-semibold text-neutral-700 dark:text-neutral-300 transition-colors hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400"
                            title="Edit Court Details"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                        </button>

                        <!-- Delete Button -->
                        <button
                            @click="destroy(court)"
                            class="rounded-xl p-1.5 text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
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
            No courts found matching your search.
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
                        <Label for="create-court-venue">Assigned Venue *</Label>
                        <select
                            id="create-court-venue"
                            v-model="createForm.venue_id"
                            required
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        >
                            <option v-for="v in venues" :key="v.id" :value="v.id">
                                {{ v.name }}
                            </option>
                        </select>
                        <p class="text-xs text-muted-foreground">Courts only appear on the public website through their venue.</p>
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
                            <Label for="create-court-price">Hourly Rate (₱) *</Label>
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

    <!-- Edit Court & Rates Modal -->
    <EditCourtModal
        :is-open="showEditModal"
        :court="editingCourt"
        :venues="venues"
        :sport-types="sportTypes"
        :statuses="statuses"
        @close="showEditModal = false"
    />

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
