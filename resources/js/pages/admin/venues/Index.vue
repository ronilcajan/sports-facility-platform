<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Search, Plus, Pencil, Trash2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface Venue {
    id: number;
    name: string;
    slug: string;
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

function applyFilters() {
    router.get('/admin/venues', { search: search.value }, { preserveState: true, replace: true });
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
            <Button as-child>
                <Link href="/admin/venues/create">
                    <Plus class="mr-1.5 h-4 w-4" />
                    Add Venue
                </Link>
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
                                <Button variant="outline" size="sm" as-child>
                                    <Link :href="`/admin/venues/${venue.id}/edit`">
                                        <Pencil class="mr-1 h-3.5 w-3.5" />
                                        Edit
                                    </Link>
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
</template>
