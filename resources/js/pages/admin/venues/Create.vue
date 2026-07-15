<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Venues', href: '/admin/venues' },
            { title: 'Add Venue', href: '/admin/venues/create' },
        ],
    },
});

const form = useForm({
    name: '',
    description: '',
    address: '',
    phone: '',
    email: '',
    gcash_number: '',
    gcash_qr: null as File | null,
    maya_number: '',
    maya_qr: null as File | null,
    is_active: true,
});

function onQrChange(e: Event, key: 'gcash_qr' | 'maya_qr') {
    form[key] = (e.target as HTMLInputElement).files?.[0] ?? null;
}

function submit() {
    form.post('/admin/venues', {
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Add Venue" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <Heading
            variant="small"
            title="Add Venue"
            description="Create a new sports facility venue."
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

            <!-- Payment Methods (Optional) -->
            <div class="space-y-4 rounded-xl border border-input p-4">
                <div>
                    <h3 class="text-sm font-semibold">Payment Methods</h3>
                    <p class="text-xs text-muted-foreground">
                        Add GCash / Maya details so customers can pay when booking. A method only appears in the booking modal if its number is set. QR image is optional.
                    </p>
                </div>

                <div class="space-y-2">
                    <Label for="gcash_number">GCash Number</Label>
                    <Input id="gcash_number" v-model="form.gcash_number" type="text" placeholder="0917 123 4567" />
                    <InputError :message="form.errors.gcash_number" />
                    <div class="flex items-center gap-2">
                        <input type="file" accept="image/*" @change="(e) => onQrChange(e, 'gcash_qr')" class="text-xs" />
                        <span class="text-xs text-muted-foreground">GCash QR (optional)</span>
                    </div>
                    <InputError :message="form.errors.gcash_qr" />
                </div>

                <div class="space-y-2">
                    <Label for="maya_number">Maya Number</Label>
                    <Input id="maya_number" v-model="form.maya_number" type="text" placeholder="0918 555 0000" />
                    <InputError :message="form.errors.maya_number" />
                    <div class="flex items-center gap-2">
                        <input type="file" accept="image/*" @change="(e) => onQrChange(e, 'maya_qr')" class="text-xs" />
                        <span class="text-xs text-muted-foreground">Maya QR (optional)</span>
                    </div>
                    <InputError :message="form.errors.maya_qr" />
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
                    {{ form.processing ? 'Creating...' : 'Create Venue' }}
                </Button>
                <Button variant="outline" type="button" @click="router.visit('/admin/venues')">
                    Cancel
                </Button>
            </div>
        </form>
    </div>
</template>
