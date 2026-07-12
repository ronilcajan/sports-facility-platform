<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import CourtController from '@/actions/App/Http/Controllers/Admin/CourtController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface StaffMember {
    id: number;
    name: string;
    email: string;
}

interface Court {
    id: number;
    name: string;
    sport_type: string;
    description: string | null;
    status: string;
    base_price: string;
    slot_duration_minutes: number;
    buffer_minutes: number;
    is_active: boolean;
    staff: StaffMember[];
}

const props = defineProps<{
    court: Court;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Courts', href: '/admin/courts' },
            { title: 'Details', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="props.court.name" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                :title="props.court.name"
                :description="
                    props.court.description ?? 'No description provided.'
                "
            />
            <Button as-child>
                <Link :href="CourtController.edit(props.court.id).url"
                    >Edit</Link
                >
            </Button>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>Details</CardTitle>
                </CardHeader>
                <CardContent class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Sport</span>
                        <span class="capitalize">
                            {{ props.court.sport_type.replace('-', ' ') }}
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Status</span>
                        <Badge>{{ props.court.status }}</Badge>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Base price</span>
                        <span>${{ props.court.base_price }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Slot duration</span>
                        <span>{{ props.court.slot_duration_minutes }} min</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-muted-foreground">Buffer</span>
                        <span>{{ props.court.buffer_minutes }} min</span>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Assigned staff</CardTitle>
                </CardHeader>
                <CardContent>
                    <ul
                        v-if="props.court.staff.length"
                        class="space-y-2 text-sm"
                    >
                        <li
                            v-for="member in props.court.staff"
                            :key="member.id"
                            class="flex justify-between"
                        >
                            <span>{{ member.name }}</span>
                            <span class="text-muted-foreground">
                                {{ member.email }}
                            </span>
                        </li>
                    </ul>
                    <p v-else class="text-sm text-muted-foreground">
                        No staff assigned to this court yet.
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
