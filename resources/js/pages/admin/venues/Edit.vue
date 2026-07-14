<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

interface VenueData {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    is_active: boolean;
}

const props = defineProps<{
    venue: VenueData;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Venues', href: '/admin/venues' },
            { title: 'Edit Venue' },
        ],
    },
});

const form = useForm({
    name: props.venue.name,
    description: props.venue.description || '',
    address: props.venue.address || '',
    phone: props.venue.phone || '',
    email: props.venue.email || '',
    is_active: props.venue.is_active,
});

function submit() {
    form.put(`/admin/venues/${props.venue.id}`);
}
</script>

<template>
    <Head :title="`Edit ${venue.name}`" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            variant="small"
            :title="`Edit: ${venue.name}`"
            description="Update this venue's information."
        />

        <form @submit.prevent="submit" class="max-w-2xl space-y-6">
            <div class="space-y-2">
                <Label for="name">Venue Name *</Label>
                <Input id="name" v-model="form.name" type="text" required />
                <InputError :message="form.errors.name" />
            </div>

            <div class="space-y-2">
                <Label for="description">Description</Label>
                <textarea
                    id="description"
                    v-model="form.description"
                    rows="3"
                    class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring"
                />
                <InputError :message="form.errors.description" />
            </div>

            <div class="space-y-2">
                <Label for="address">Address</Label>
                <Input id="address" v-model="form.address" type="text" />
                <InputError :message="form.errors.address" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div class="space-y-2">
                    <Label for="phone">Phone</Label>
                    <Input id="phone" v-model="form.phone" type="text" />
                    <InputError :message="form.errors.phone" />
                </div>
                <div class="space-y-2">
                    <Label for="email">Email</Label>
                    <Input id="email" v-model="form.email" type="email" />
                    <InputError :message="form.errors.email" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <input
                    id="is_active"
                    v-model="form.is_active"
                    type="checkbox"
                    class="rounded border-input"
                />
                <Label for="is_active">Active</Label>
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="form.processing">
                    {{ form.processing ? 'Saving...' : 'Save Changes' }}
                </Button>
                <Button variant="outline" type="button" @click="router.visit('/admin/venues')">
                    Cancel
                </Button>
            </div>
        </form>
    </div>
</template>
