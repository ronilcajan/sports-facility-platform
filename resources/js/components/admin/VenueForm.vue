<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';

export interface VenueData {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    address: string | null;
    phone: string | null;
    email: string | null;
    gcash_number: string | null;
    gcash_qr_url: string | null;
    maya_number: string | null;
    maya_qr_url: string | null;
    is_active: boolean;
}

const props = defineProps<{
    venue: VenueData;
    action: string;
    cancelUrl: string;
}>();

const form = useForm({
    name: props.venue.name,
    description: props.venue.description || '',
    address: props.venue.address || '',
    phone: props.venue.phone || '',
    email: props.venue.email || '',
    gcash_number: props.venue.gcash_number || '',
    gcash_qr: null as File | null,
    maya_number: props.venue.maya_number || '',
    maya_qr: null as File | null,
    is_active: props.venue.is_active,
});

function onQrChange(e: Event, key: 'gcash_qr' | 'maya_qr') {
    form[key] = (e.target as HTMLInputElement).files?.[0] ?? null;
}

function submit() {
    // Method-spoof to POST so the multipart QR uploads reach Laravel (PHP can't parse multipart PUT)
    form.transform((data) => ({ ...data, _method: 'put' })).post(props.action);
}
</script>

<template>
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
                <div class="flex items-center gap-3">
                    <img
                        v-if="venue.gcash_qr_url"
                        :src="venue.gcash_qr_url"
                        alt="Current GCash QR"
                        class="size-14 rounded border border-input object-cover"
                    />
                    <div class="flex items-center gap-2">
                        <input type="file" accept="image/*" @change="(e) => onQrChange(e, 'gcash_qr')" class="text-xs" />
                        <span class="text-xs text-muted-foreground">{{ venue.gcash_qr_url ? 'Replace QR (optional)' : 'GCash QR (optional)' }}</span>
                    </div>
                </div>
                <InputError :message="form.errors.gcash_qr" />
            </div>

            <div class="space-y-2">
                <Label for="maya_number">Maya Number</Label>
                <Input id="maya_number" v-model="form.maya_number" type="text" placeholder="0918 555 0000" />
                <InputError :message="form.errors.maya_number" />
                <div class="flex items-center gap-3">
                    <img
                        v-if="venue.maya_qr_url"
                        :src="venue.maya_qr_url"
                        alt="Current Maya QR"
                        class="size-14 rounded border border-input object-cover"
                    />
                    <div class="flex items-center gap-2">
                        <input type="file" accept="image/*" @change="(e) => onQrChange(e, 'maya_qr')" class="text-xs" />
                        <span class="text-xs text-muted-foreground">{{ venue.maya_qr_url ? 'Replace QR (optional)' : 'Maya QR (optional)' }}</span>
                    </div>
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
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </Button>
            <Button variant="outline" type="button" @click="router.visit(cancelUrl)">
                Cancel
            </Button>
        </div>
    </form>
</template>
