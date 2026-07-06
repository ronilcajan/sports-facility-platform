<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import ContactController from '@/actions/App/Http/Controllers/Site/ContactController';
import PageHero from '@/components/site/PageHero.vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useSite } from '@/composables/useSite';

interface ContactContent {
    title: string;
    lede: string;
}

defineProps<{ content: ContactContent }>();

const site = useSite();

const form = useForm({
    name: '',
    email: '',
    message: '',
});

function submit(): void {
    form.post(ContactController.store().url, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <Head title="Contact" />

    <PageHero
        eyebrow="Contact"
        :title="content.title"
        :lede="content.lede"
    />

    <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6 sm:py-24">
        <div class="grid gap-12 lg:grid-cols-[1.1fr_0.9fr]">
            <form class="space-y-6" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="name">Your name</Label>
                    <Input id="name" v-model="form.name" required autocomplete="name" />
                    <InputError :message="form.errors.name" />
                </div>
                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autocomplete="email"
                    />
                    <InputError :message="form.errors.email" />
                </div>
                <div class="grid gap-2">
                    <Label for="message">Message</Label>
                    <textarea
                        id="message"
                        v-model="form.message"
                        rows="5"
                        required
                        class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        placeholder="How can we help?"
                    />
                    <InputError :message="form.errors.message" />
                </div>
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-full bg-ink px-7 py-3 text-sm font-semibold text-chalk transition-transform hover:-translate-y-0.5 disabled:opacity-60"
                >
                    Send message
                </button>
            </form>

            <aside class="space-y-8">
                <div class="rounded-2xl border border-ink/10 bg-white p-7">
                    <h2 class="font-bold text-ink">Visit the yard</h2>
                    <address class="mt-4 space-y-3 text-sm not-italic text-fog">
                        <p>{{ site.contact.address_line }}</p>
                        <p>
                            <a
                                :href="`tel:${site.contact.phone}`"
                                class="text-court hover:text-ink"
                            >
                                {{ site.contact.phone }}
                            </a>
                        </p>
                        <p>
                            <a
                                :href="`mailto:${site.contact.email}`"
                                class="text-court hover:text-ink"
                            >
                                {{ site.contact.email }}
                            </a>
                        </p>
                    </address>
                </div>
                <div class="rounded-2xl border border-ink/10 bg-white p-7">
                    <h2 class="font-bold text-ink">Hours</h2>
                    <ul class="mt-4 space-y-2 text-sm">
                        <li
                            v-for="row in site.hours"
                            :key="row.day"
                            class="flex justify-between gap-4"
                        >
                            <span class="text-fog">{{ row.day }}</span>
                            <span class="font-medium text-ink">{{ row.value }}</span>
                        </li>
                    </ul>
                </div>
            </aside>
        </div>
    </div>
</template>
