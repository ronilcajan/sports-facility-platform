<script setup lang="ts">
import { ref, onUnmounted } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
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
    image_url?: string | null;
    gcash_number: string | null;
    gcash_qr_url: string | null;
    maya_number: string | null;
    maya_qr_url: string | null;
    is_active: boolean;
}

const props = withDefaults(
    defineProps<{
        venue: VenueData;
        action: string;
        cancelUrl: string;
        canManageVenueImages?: boolean;
    }>(),
    {
        canManageVenueImages: true,
    }
);

const form = useForm({
    name: props.venue.name,
    description: props.venue.description || '',
    address: props.venue.address || '',
    phone: props.venue.phone || '',
    email: props.venue.email || '',
    image: null as File | null,
    delete_image: false,
    gcash_number: props.venue.gcash_number || '',
    gcash_qr: null as File | null,
    maya_number: props.venue.maya_number || '',
    maya_qr: null as File | null,
    is_active: props.venue.is_active,
});

const imagePreview = ref<string | null>(null);

function onImageChange(e: Event) {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        if (file.size > 5 * 1024 * 1024) {
            alert('File size exceeds 5MB limit.');
            return;
        }
        form.image = file;
        form.delete_image = false;
        if (imagePreview.value) {
            URL.revokeObjectURL(imagePreview.value);
        }
        imagePreview.value = URL.createObjectURL(file);
    }
}

function removeImage() {
    form.image = null;
    form.delete_image = true;
    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
        imagePreview.value = null;
    }
}

function deleteImageNow() {
    if (confirm('Permanently remove this venue cover image?')) {
        router.delete(`/admin/venues/${props.venue.id}/image`, {
            preserveScroll: true,
            onSuccess: () => {
                form.image = null;
                form.delete_image = false;
                if (imagePreview.value) {
                    URL.revokeObjectURL(imagePreview.value);
                    imagePreview.value = null;
                }
            },
        });
    }
}

function onQrChange(e: Event, key: 'gcash_qr' | 'maya_qr') {
    form[key] = (e.target as HTMLInputElement).files?.[0] ?? null;
}

function submit() {
    // Method-spoof to POST so the multipart QR uploads reach Laravel (PHP can't parse multipart PUT)
    form.transform((data) => ({ ...data, _method: 'put' })).post(props.action);
}

onUnmounted(() => {
    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
    }
});
</script>

<template>
    <form @submit.prevent="submit" class="space-y-6">
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

        <!-- Venue Cover Photo -->
        <div v-if="canManageVenueImages !== false" class="space-y-3 rounded-xl border border-input p-4">
            <div>
                <h3 class="text-sm font-semibold">Venue Cover Photo</h3>
                <p class="text-xs text-muted-foreground">Upload or replace a hero cover photo for this venue (JPG, PNG, WEBP max 5MB).</p>
            </div>

            <div class="flex items-center gap-4 flex-wrap">
                <div v-if="imagePreview" class="relative">
                    <img :src="imagePreview" alt="Preview" class="h-20 w-32 rounded-lg object-cover border border-input shadow-sm" />
                    <span class="absolute -top-2 -right-2 bg-emerald-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full">New</span>
                </div>
                <div v-else-if="venue.image_url && !form.delete_image" class="relative">
                    <img :src="venue.image_url" alt="Current Cover" class="h-20 w-32 rounded-lg object-cover border border-input shadow-sm" />
                </div>
                <div v-else class="h-20 w-32 rounded-lg border border-dashed border-input flex items-center justify-center text-xs text-muted-foreground">
                    No Cover
                </div>

                <div class="space-y-2">
                    <input type="file" accept="image/jpeg,image/png,image/jpg,image/webp" @change="onImageChange" class="text-xs" />
                    <div v-if="venue.image_url && !form.delete_image" class="pt-1 flex items-center gap-3">
                        <button type="button" @click="removeImage" class="text-xs font-semibold text-amber-600 dark:text-amber-400 hover:underline cursor-pointer">
                            Mark for deletion on save
                        </button>
                        <span class="text-xs text-neutral-300 dark:text-neutral-700">|</span>
                        <button type="button" @click="deleteImageNow" class="text-xs font-bold text-rose-600 hover:text-rose-700 underline cursor-pointer">
                            Remove image now
                        </button>
                    </div>
                    <div v-if="form.delete_image" class="text-xs text-rose-500 font-medium">
                        Cover image marked for deletion on save.
                    </div>
                </div>
            </div>
            <InputError :message="form.errors.image" />
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
            <button
                type="submit"
                :disabled="form.processing"
                class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow transition-colors hover:bg-emerald-700 disabled:opacity-60"
            >
                {{ form.processing ? 'Saving...' : 'Save Changes' }}
            </button>
            <button
                type="button"
                @click="router.visit(cancelUrl)"
                class="rounded-xl bg-neutral-200 dark:bg-neutral-800 px-4 py-2 text-xs font-semibold text-neutral-700 dark:text-neutral-300 transition-colors hover:bg-neutral-300 dark:hover:bg-neutral-700"
            >
                Cancel
            </button>
        </div>
    </form>
</template>
