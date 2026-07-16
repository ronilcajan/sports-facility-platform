<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    siteName: string;
    logoUrl: string;
    contact: { email: string | null; phone: string | null; address: string | null };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Appearance', href: '/admin/appearance' }],
    },
});

// Branding (site name + logo)
const logoPreview = ref<string>(props.logoUrl);
const brandingForm = useForm({
    name: props.siteName,
    logo: null as File | null,
    contact_email: props.contact.email ?? '',
    contact_phone: props.contact.phone ?? '',
    contact_address: props.contact.address ?? '',
});

function onLogoChange(e: Event): void {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    brandingForm.logo = file;
    if (file) {
        logoPreview.value = URL.createObjectURL(file);
    }
}

function saveBranding(): void {
    brandingForm.post('/admin/appearance/branding', { preserveScroll: true });
}
</script>

<template>
    <div class="px-4 py-6">
        <Head title="Appearance" />

        <Heading
            title="Appearance"
            description="Manage the site branding, logo, and contact details. Applies site-wide."
        />

        <!-- Branding: site name + logo -->
        <div class="mt-6 max-w-xl rounded-xl border border-border p-5">
            <h3 class="text-base font-semibold">Branding</h3>
            <p class="mt-1 text-sm text-muted-foreground">
                Your site name and logo appear across the site, dashboard, favicon, and emails.
            </p>

            <div class="mt-4 space-y-4">
                <div class="space-y-2">
                    <Label for="site-name">Site Name</Label>
                    <Input id="site-name" v-model="brandingForm.name" type="text" maxlength="60" />
                    <InputError :message="brandingForm.errors.name" />
                </div>

                <div class="space-y-2">
                    <Label>Logo</Label>
                    <div class="flex items-center gap-4">
                        <img
                            :src="logoPreview"
                            alt="Current logo"
                            class="size-16 rounded-lg border border-border object-cover"
                        />
                        <input type="file" accept="image/*" class="text-sm" @change="onLogoChange" />
                    </div>
                    <p class="text-xs text-muted-foreground">PNG, JPG, WEBP or SVG up to 2MB.</p>
                    <InputError :message="brandingForm.errors.logo" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="space-y-2">
                        <Label for="contact-email">Contact Email</Label>
                        <Input id="contact-email" v-model="brandingForm.contact_email" type="email" />
                        <InputError :message="brandingForm.errors.contact_email" />
                    </div>
                    <div class="space-y-2">
                        <Label for="contact-phone">Contact Phone</Label>
                        <Input id="contact-phone" v-model="brandingForm.contact_phone" type="text" />
                        <InputError :message="brandingForm.errors.contact_phone" />
                    </div>
                </div>

                <div class="space-y-2">
                    <Label for="contact-address">Address</Label>
                    <Input id="contact-address" v-model="brandingForm.contact_address" type="text" />
                    <InputError :message="brandingForm.errors.contact_address" />
                </div>

                <Button :disabled="brandingForm.processing" @click="saveBranding">
                    {{ brandingForm.processing ? 'Saving...' : 'Save branding' }}
                </Button>
            </div>
        </div>

    </div>
</template>
