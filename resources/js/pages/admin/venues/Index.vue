<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Search, Plus, Pencil, Trash2, X } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

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
    is_active: true,
});

const editForm = useForm({
    name: '',
    description: '',
    address: '',
    phone: '',
    email: '',
    is_active: true,
});

function applyFilters() {
    router.get('/admin/venues', { search: search.value }, { preserveState: true, replace: true });
}

function openCreateModal() {
    createForm.reset();
    createForm.clearErrors();
    showCreateModal.value = true;
}

function submitCreate() {
    createForm.post('/admin/venues', {
        onSuccess: () => {
            showCreateModal.value = false;
            createForm.reset();
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
    editForm.clearErrors();
    showEditModal.value = true;
}

function submitEdit() {
    if (!editingVenue.value) return;
    editForm.put(`/admin/venues/${editingVenue.value.id}`, {
        onSuccess: () => {
            showEditModal.value = false;
            editingVenue.value = null;
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

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Venues"
                description="Manage your sports facility venues."
            />
            <Button @click="openCreateModal">
                <Plus class="mr-1.5 h-4 w-4" />
                Add Venue
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
                    placeholder="Search venue name or address..."
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
                        <th class="px-4 py-3 font-medium">Name</th>
                        <th class="px-4 py-3 font-medium">Address</th>
                        <th class="px-4 py-3 font-medium">Contact</th>
                        <th class="px-4 py-3 font-medium">Courts</th>
                        <th class="px-4 py-3 font-medium">Status</th>
                        <th class="px-4 py-3 text-right font-medium">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="venue in venues.data"
                        :key="venue.id"
                        class="border-t border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <td class="px-4 py-3 font-medium">{{ venue.name }}</td>
                        <td class="px-4 py-3 text-muted-foreground">{{ venue.address || '—' }}</td>
                        <td class="px-4 py-3 text-muted-foreground">
                            <div v-if="venue.phone" class="text-xs">{{ venue.phone }}</div>
                            <div v-if="venue.email" class="text-xs">{{ venue.email }}</div>
                            <span v-if="!venue.phone && !venue.email">—</span>
                        </td>
                        <td class="px-4 py-3">{{ venue.courts_count }}</td>
                        <td class="px-4 py-3">
                            <Badge :variant="venue.is_active ? 'default' : 'secondary'">
                                {{ venue.is_active ? 'Active' : 'Inactive' }}
                            </Badge>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                <Button variant="outline" size="sm" @click="openEditModal(venue)">
                                    <Pencil class="mr-1 h-3.5 w-3.5" />
                                    Edit
                                </Button>
                                <Button
                                    v-if="canDelete"
                                    variant="ghost"
                                    size="sm"
                                    @click="destroy(venue)"
                                    class="text-destructive hover:text-destructive"
                                >
                                    <Trash2 class="mr-1 h-3.5 w-3.5" />
                                    Delete
                                </Button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="venues.data.length === 0">
                        <td colspan="6" class="px-4 py-10 text-center text-muted-foreground">
                            No venues yet. Add your first venue to get started.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Centered Create Venue Modal -->
    <Teleport to="body">
        <div
            v-if="showCreateModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
            @click.self="showCreateModal = false"
        >
            <div class="w-full max-w-lg rounded-xl border border-border bg-background p-6 shadow-xl space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">Add New Venue</h2>
                        <p class="text-xs text-muted-foreground">Create a new sports facility venue location.</p>
                    </div>
                    <button @click="showCreateModal = false" class="text-muted-foreground hover:text-foreground">
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
            <div class="w-full max-w-lg rounded-xl border border-border bg-background p-6 shadow-xl space-y-5">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-semibold">Edit Venue</h2>
                        <p class="text-xs text-muted-foreground">Update venue location and contact information.</p>
                    </div>
                    <button @click="showEditModal = false" class="text-muted-foreground hover:text-foreground">
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
