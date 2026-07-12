<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import CourtController from '@/actions/App/Http/Controllers/Admin/CourtController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

interface Court {
    id: number;
    name: string;
    slug: string;
    sport_type: string;
    status: 'available' | 'maintenance' | 'closed';
    base_price: string;
    slot_duration_minutes: number;
    staff_count: number;
}

defineProps<{
    courts: Court[];
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
            <Button as-child>
                <Link :href="CourtController.create().url">Add court</Link>
            </Button>
        </div>

        <div
            class="overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <table class="w-full text-sm">
                <thead class="bg-muted/50 text-left text-muted-foreground">
                    <tr>
                        <th class="px-4 py-3 font-medium">Name</th>
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
                                <Button variant="outline" size="sm" as-child>
                                    <Link
                                        :href="
                                            CourtController.edit(court.id).url
                                        "
                                    >
                                        Edit
                                    </Link>
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
</template>
