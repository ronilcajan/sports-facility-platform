<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Search, Plus, Pencil, Trash2, X, Building } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

interface Venue {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    is_active: boolean;
    courts_count: number;
    cover_image_url?: string | null;
    created_at: string;
}

interface PaginatedVenues {
    data: Venue[];
    current_page: number;
    last_page: number;
}

const props = defineProps<{
    venues: PaginatedVenues;
    filters: { search?: string };
    canDelete: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Venues', href: '/admin/venues' },
        ],
    },
});

const search = ref(props.filters.search || '');
const showCreateModal = ref(false);
const showEditModal = ref(false);
const editingVenue = ref<Venue | null>(null);

const createForm = useForm({
    name: '',
    description: '',
    address: '',
    phone: '',
    email: '',
    image: null as File | null,
    is_active: true,
});

const editForm = useForm({
    name: '',
    description: '',
    address: '',
    phone: '',
    email: '',
    image: null as File | null,
    delete_image: false,
    is_active: true,
});

const createImagePreview = ref<string | null>(null);
const editImagePreview = ref<string | null>(null);

function onCreateImageChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit.');
            return;
        }
        createForm.image = file;
        if (createImagePreview.value) URL.revokeObjectURL(createImagePreview.value);
        createImagePreview.value = URL.createObjectURL(file);
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
        if (editImagePreview.value) URL.revokeObjectURL(editImagePreview.value);
        editImagePreview.value = URL.createObjectURL(file);
    }
}

function removeEditImage() {
    editForm.image = null;
    editForm.delete_image = true;
    if (editImagePreview.value) {
        URL.revokeObjectURL(editImagePreview.value);
        editImagePreview.value = null;
    }
}

function applyFilters() {
    router.get('/admin/venues', { search: search.value }, { preserveState: true, replace: true });
}

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    if (createImagePreview.value) {
        URL.revokeObjectURL(createImagePreview.value);
        createImagePreview.value = null;
    }
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.post('/admin/venues', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
            if (createImagePreview.value) {
                URL.revokeObjectURL(createImagePreview.value);
                createImagePreview.value = null;
            }
        },
        preserveScroll: true,
    });
}

function openEditModal(venue: Venue) {
    editingVenue.value = venue;
    editForm.name = venue.name;
    editForm.description = venue.description || '';
    editForm.address = venue.address || '';
    editForm.phone = venue.phone || '';
    editForm.email = venue.email || '';
    editForm.is_active = venue.is_active;
    editForm.image = null;
    editForm.delete_image = false;
    if (editImagePreview.value) {
        URL.revokeObjectURL(editImagePreview.value);
        editImagePreview.value = null;
    }
    editForm.clearErrors();
    showEditModal.value = true;
}

function submitEdit() {
    if (!editingVenue.value) return;
    editForm.transform((data) => ({ ...data, _method: 'put' })).post(`/admin/venues/${editingVenue.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            editingVenue.value = null;
            if (editImagePreview.value) {
                URL.revokeObjectURL(editImagePreview.value);
                editImagePreview.value = null;
            }
        },
        preserveScroll: true,
    });
}

function destroy(venue: Venue): void {
    if (confirm(`Delete "${venue.name}"? This action cannot be undone.`)) {
        router.delete(`/admin/venues/${venue.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <Head title="Venues Management" />

    <div class="p-6 space-y-6 w-full">
        <!-- Header -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-neutral-900 dark:text-white">Venues</h1>
                <p class="text-xs text-neutral-500">Manage your sports facility venues.</p>
            </div>
            <button
                @click="openCreateModal"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow transition-colors hover:bg-emerald-700"
            >
                <Plus class="w-4 h-4" /> Add Venue
            </button>
        </div>

        <!-- Search bar -->
        <div class="p-4 rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm flex items-center gap-3">
            <div class="relative flex-1">
                <Search class="w-4 h-4 absolute left-3 top-2.5 text-neutral-400" />
                <input
                    v-model="search"
                    @keyup.enter="applyFilters"
                    type="text"
                    placeholder="Search venue name or address..."
                    class="w-full pl-9 pr-3 py-2 rounded-xl border border-neutral-300 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-800 text-xs text-neutral-900 dark:text-white focus:ring-2 focus:ring-emerald-500"
                />
            </div>
            <button @click="applyFilters" class="px-3 py-1.5 bg-emerald-600 text-white rounded-lg text-xs font-semibold hover:bg-emerald-700">Search</button>
        </div>

        <!-- Card grid -->
        <div
            v-if="venues.data.length"
            class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
        >
            <div
                v-for="venue in venues.data"
                :key="venue.id"
                class="group flex flex-col overflow-hidden rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="relative aspect-video overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                    <img
                        v-if="venue.cover_image_url"
                        :src="venue.cover_image_url"
                        :alt="venue.name"
                        class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                    />
                    <div v-else class="flex h-full w-full items-center justify-center text-neutral-300 dark:text-neutral-600">
                        <Building class="h-10 w-10" />
                    </div>
                    <span
                        :class="[
                            'absolute right-2 top-2 rounded-full px-2 py-0.5 text-[10px] font-bold capitalize shadow-sm',
                            venue.is_active ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950 dark:text-rose-300',
                        ]"
                    >
                        {{ venue.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div class="flex flex-1 flex-col gap-2 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-semibold leading-tight text-neutral-900 dark:text-white">{{ venue.name }}</h3>
                        <span class="shrink-0 rounded-full bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 text-[11px] font-medium text-neutral-500">
                            {{ venue.courts_count }} {{ venue.courts_count === 1 ? 'court' : 'courts' }}
                        </span>
                    </div>
                    <p class="line-clamp-1 text-xs text-neutral-500">{{ venue.address || 'No address set' }}</p>
                    <div v-if="venue.phone || venue.email" class="text-xs text-neutral-500">
                        <div v-if="venue.phone">{{ venue.phone }}</div>
                        <div v-if="venue.email" class="truncate">{{ venue.email }}</div>
                    </div>

                    <div class="mt-auto flex items-center gap-2 pt-3">
                        <button
                            @click="openEditModal(venue)"
                            class="inline-flex flex-1 items-center justify-center gap-1 rounded-lg border border-neutral-200 dark:border-neutral-700 px-3 py-1.5 text-xs font-semibold text-neutral-700 dark:text-neutral-300 transition-colors hover:border-emerald-500 hover:text-emerald-600 dark:hover:text-emerald-400"
                        >
                            <Pencil class="h-3.5 w-3.5" /> Edit
                        </button>
                        <button
                            v-if="canDelete"
                            @click="destroy(venue)"
                            class="rounded-lg p-2 text-rose-500 transition-colors hover:bg-rose-50 hover:text-rose-600 dark:hover:bg-rose-950/40"
                            title="Delete venue"
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
            No venues yet. Add your first venue to get started.
        </div>
    </div>

    <!-- Centered Create Venue Modal -->
    <Teleport to="body">
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            @click.self="showCreateModal = false"
        >
            <div class="w-full max-w-lg rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xl space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Add New Venue</h2>
                        <p class="text-xs text-neutral-500">Create a new sports facility venue location.</p>
                    </div>
                    <button @click="showCreateModal = false" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-white">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="create-venue-name">Venue Name *</Label>
                        <Input id="create-venue-name" v-model="createForm.name" type="text" placeholder="e.g. Metro Sports Center" required />
                        <InputError :message="createForm.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="create-venue-description">Description</Label>
                        <textarea
                            id="create-venue-description"
                            v-model="createForm.description"
                            rows="2"
                            placeholder="Brief facility summary or rules..."
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <InputError :message="createForm.errors.description" />
                    </div>

                    <div class="space-y-2">
                        <Label for="create-venue-address">Address</Label>
                        <Input id="create-venue-address" v-model="createForm.address" type="text" placeholder="e.g. 123 Sports Ave" />
                        <InputError :message="createForm.errors.address" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <Label for="create-venue-phone">Phone</Label>
                            <Input id="create-venue-phone" v-model="createForm.phone" type="text" placeholder="(555) 000-0000" />
                            <InputError :message="createForm.errors.phone" />
                        </div>
                        <div class="space-y-2">
                            <Label for="create-venue-email">Email</Label>
                            <Input id="create-venue-email" v-model="createForm.email" type="email" placeholder="contact@venue.com" />
                            <InputError :message="createForm.errors.email" />
                        </div>
                    </div>

                    <!-- Venue Cover Photo Upload -->
                    <div class="space-y-3 rounded-xl border border-input p-3">
                        <Label class="text-xs font-semibold">Venue Cover Photo</Label>
                        <div class="flex items-center gap-3">
                            <div v-if="createImagePreview" class="relative">
                                <img :src="createImagePreview" alt="Preview" class="h-16 w-24 rounded-lg object-cover border border-input shadow-sm" />
                            </div>
                            <div v-else class="h-16 w-24 rounded-lg border border-dashed border-input flex items-center justify-center text-xs text-muted-foreground">
                                No Cover
                            </div>
                            <input type="file" accept="image/jpeg,image/png,image/jpg,image/webp,image/avif" @change="onCreateImageChange" class="text-xs" />
                        </div>
                        <InputError :message="createForm.errors.image" />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="create-venue-active"
                            v-model="createForm.is_active"
                            type="checkbox"
                            class="rounded border-input text-primary focus:ring-primary"
                        />
                        <Label for="create-venue-active" class="cursor-pointer">Venue is active and open for booking</Label>
                    </div>

                    <div class="flex justify-end gap-3 pt-3">
                        <Button variant="outline" type="button" @click="showCreateModal = false">Cancel</Button>
                        <Button type="submit" :disabled="createForm.processing">
                            {{ createForm.processing ? 'Creating...' : 'Create Venue' }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Centered Edit Venue Modal -->
    <Teleport to="body">
        <div
            v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            @click.self="showEditModal = false"
        >
            <div class="w-full max-w-lg rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 shadow-xl space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-neutral-900 dark:text-white">Edit Venue</h2>
                        <p class="text-xs text-neutral-500">Update venue location and contact information.</p>
                    </div>
                    <button @click="showEditModal = false" class="text-neutral-400 hover:text-neutral-900 dark:hover:text-white">
                        <X class="h-5 w-5" />
                    </button>
                </div>

                <form @submit.prevent="submitEdit" class="space-y-4">
                    <div class="space-y-2">
                        <Label for="edit-venue-name">Venue Name *</Label>
                        <Input id="edit-venue-name" v-model="editForm.name" type="text" required />
                        <InputError :message="editForm.errors.name" />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-venue-description">Description</Label>
                        <textarea
                            id="edit-venue-description"
                            v-model="editForm.description"
                            rows="2"
                            class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                        />
                        <InputError :message="editForm.errors.description" />
                    </div>

                    <div class="space-y-2">
                        <Label for="edit-venue-address">Address</Label>
                        <Input id="edit-venue-address" v-model="editForm.address" type="text" />
                        <InputError :message="editForm.errors.address" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="space-y-2">
                            <Label for="edit-venue-phone">Phone</Label>
                            <Input id="edit-venue-phone" v-model="editForm.phone" type="text" />
                            <InputError :message="editForm.errors.phone" />
                        </div>
                        <div class="space-y-2">
                            <Label for="edit-venue-email">Email</Label>
                            <Input id="edit-venue-email" v-model="editForm.email" type="email" />
                            <InputError :message="editForm.errors.email" />
                        </div>
                    </div>

                    <!-- Venue Cover Photo Upload -->
                    <div class="space-y-3 rounded-xl border border-input p-3">
                        <Label class="text-xs font-semibold">Venue Cover Photo</Label>
                        <div class="flex items-center gap-3">
                            <div v-if="editImagePreview" class="relative">
                                <img :src="editImagePreview" alt="Preview" class="h-16 w-24 rounded-lg object-cover border border-input shadow-sm" />
                                <span class="absolute -top-1.5 -right-1.5 bg-emerald-600 text-white text-[8px] font-bold px-1 rounded-full">New</span>
                            </div>
                            <div v-else-if="editingVenue?.cover_image_url && !editForm.delete_image" class="relative">
                                <img :src="editingVenue.cover_image_url" alt="Current Cover" class="h-16 w-24 rounded-lg object-cover border border-input shadow-sm" />
                            </div>
                            <div v-else class="h-16 w-24 rounded-lg border border-dashed border-input flex items-center justify-center text-xs text-muted-foreground">
                                No Cover
                            </div>

                            <div class="space-y-1">
                                <input type="file" accept="image/jpeg,image/png,image/jpg,image/webp,image/avif" @change="onEditImageChange" class="text-xs" />
                                <div v-if="editingVenue?.cover_image_url && !editForm.delete_image" class="pt-0.5">
                                    <button type="button" @click="removeEditImage" class="text-[11px] font-semibold text-rose-600 hover:underline">
                                        Remove cover photo
                                    </button>
                                </div>
                            </div>
                        </div>
                        <InputError :message="editForm.errors.image" />
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input
                            id="edit-venue-active"
                            v-model="editForm.is_active"
                            type="checkbox"
                            class="rounded border-input text-primary focus:ring-primary"
                        />
                        <Label for="edit-venue-active" class="cursor-pointer">Venue is active and open for booking</Label>
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
