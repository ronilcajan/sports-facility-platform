<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import CourtController from '@/actions/App/Http/Controllers/Admin/CourtController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface Option {
    value: string;
    label: string;
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
}

const props = defineProps<{
    court?: Court;
    sportTypes: Option[];
    statuses: Option[];
}>();

const isEditing = Boolean(props.court);

const form = useForm({
    name: props.court?.name ?? '',
    sport_type: props.court?.sport_type ?? props.sportTypes[0]?.value ?? '',
    description: props.court?.description ?? '',
    status: props.court?.status ?? props.statuses[0]?.value ?? '',
    base_price: props.court?.base_price ?? '0',
    slot_duration_minutes: props.court?.slot_duration_minutes ?? 60,
    buffer_minutes: props.court?.buffer_minutes ?? 0,
    is_active: props.court?.is_active ?? true,
});

function submit(): void {
    if (isEditing && props.court) {
        form.put(CourtController.update(props.court.id).url, {
            preserveScroll: true,
        });
        return;
    }

    form.post(CourtController.store().url);
}
</script>

<template>
    <form class="max-w-2xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input id="name" v-model="form.name" required placeholder="Center Court" />
            <InputError :message="form.errors.name" />
        </div>

        <div class="grid gap-2 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="sport_type">Sport type</Label>
                <Select v-model="form.sport_type">
                    <SelectTrigger id="sport_type">
                        <SelectValue placeholder="Select a sport" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in sportTypes"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.sport_type" />
            </div>

            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <Select v-model="form.status">
                    <SelectTrigger id="status">
                        <SelectValue placeholder="Select a status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in statuses"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="form.errors.status" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>
            <textarea
                id="description"
                v-model="form.description"
                rows="3"
                class="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                placeholder="Optional details about this court"
            />
            <InputError :message="form.errors.description" />
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="grid gap-2">
                <Label for="base_price">Base price</Label>
                <Input
                    id="base_price"
                    v-model="form.base_price"
                    type="number"
                    step="0.01"
                    min="0"
                />
                <InputError :message="form.errors.base_price" />
            </div>
            <div class="grid gap-2">
                <Label for="slot_duration_minutes">Slot (min)</Label>
                <Input
                    id="slot_duration_minutes"
                    v-model="form.slot_duration_minutes"
                    type="number"
                    min="1"
                />
                <InputError :message="form.errors.slot_duration_minutes" />
            </div>
            <div class="grid gap-2">
                <Label for="buffer_minutes">Buffer (min)</Label>
                <Input
                    id="buffer_minutes"
                    v-model="form.buffer_minutes"
                    type="number"
                    min="0"
                />
                <InputError :message="form.errors.buffer_minutes" />
            </div>
        </div>

        <div class="flex items-center gap-2">
            <Checkbox id="is_active" v-model="form.is_active" />
            <Label for="is_active">Active (bookable)</Label>
        </div>
        <InputError :message="form.errors.is_active" />

        <div class="flex items-center gap-3">
            <Button type="submit" :disabled="form.processing">
                {{ isEditing ? 'Save changes' : 'Create court' }}
            </Button>
        </div>
    </form>
</template>
