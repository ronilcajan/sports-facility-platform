<script setup lang="ts">
import { ref, onUnmounted, computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from '@lucide/vue';
import CourtController from '@/actions/App/Http/Controllers/Admin/CourtController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getMergedTimeSlots, isDefaultTimeSlot } from '@/utils/timeSlots';
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

interface CourtImage {
    id: number;
    path: string;
    url: string;
    is_primary: boolean;
}

interface Court {
    id: number;
    name: string;
    sport_type: string;
    description: string | null;
    status: string;
    base_price: string;
    slot_prices?: Record<string, number | string> | null;
    slot_duration_minutes: number;
    buffer_minutes: number;
    is_active: boolean;
    primary_image?: CourtImage | null;
}

const props = defineProps<{
    court?: Court;
    sportTypes: Option[];
    statuses: Option[];
}>();

const isEditing = Boolean(props.court);

const showAddSlotInput = ref(false);
const newSlotHour = ref('03');
const newSlotMinute = ref('00');
const newSlotPeriod = ref<'AM' | 'PM'>('AM');

const allTimeSlots = computed(() => {
    return getMergedTimeSlots(form.slot_prices);
});

function addCustomTimeSlot() {
    const formatted = `${newSlotHour.value.padStart(2, '0')}:${newSlotMinute.value.padStart(2, '0')} ${newSlotPeriod.value}`;
    if (form.slot_prices[formatted] === undefined) {
        form.slot_prices[formatted] = '';
    }
    showAddSlotInput.value = false;
}

function removeCustomTimeSlot(slot: string) {
    if (isDefaultTimeSlot(slot)) return;
    delete form.slot_prices[slot];
}

const form = useForm({
    name: props.court?.name ?? '',
    sport_type: props.court?.sport_type ?? props.sportTypes[0]?.value ?? '',
    description: props.court?.description ?? '',
    status: props.court?.status ?? props.statuses[0]?.value ?? '',
    base_price: props.court?.base_price ?? '0',
    slot_prices: (props.court?.slot_prices ? { ...props.court.slot_prices } : {}) as Record<string, string | number>,
    slot_duration_minutes: props.court?.slot_duration_minutes ?? 60,
    buffer_minutes: props.court?.buffer_minutes ?? 0,
    is_active: props.court?.is_active ?? true,
    image: null as File | null,
    delete_image: false,
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

function submit(): void {
    if (isEditing && props.court) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(CourtController.update(props.court.id).url, {
            preserveScroll: true,
        });
        return;
    }

    form.post(CourtController.store().url);
}

onUnmounted(() => {
    if (imagePreview.value) {
        URL.revokeObjectURL(imagePreview.value);
    }
});
</script>

<template>
    <form class="max-w-2xl space-y-6" @submit.prevent="submit">
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                v-model="form.name"
                required
                placeholder="Center Court"
            />
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

        <!-- Court Photo Upload -->
        <div class="space-y-3 rounded-xl border border-input p-4">
            <div>
                <h3 class="text-sm font-semibold">Court Photo</h3>
                <p class="text-xs text-muted-foreground">Upload or replace a primary photo for this court (JPG, PNG, WEBP, AVIF max 5MB).</p>
            </div>

            <div class="flex items-center gap-4 flex-wrap">
                <div v-if="imagePreview" class="relative">
                    <img :src="imagePreview" alt="Preview" class="h-20 w-32 rounded-lg object-cover border border-input shadow-sm" />
                    <span class="absolute -top-2 -right-2 bg-emerald-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-full">New</span>
                </div>
                <div v-else-if="court?.primary_image?.url && !form.delete_image" class="relative">
                    <img :src="court.primary_image.url" alt="Current Photo" class="h-20 w-32 rounded-lg object-cover border border-input shadow-sm" />
                </div>
                <div v-else class="h-20 w-32 rounded-lg border border-dashed border-input flex items-center justify-center text-xs text-muted-foreground">
                    No Photo
                </div>

                <div class="space-y-2">
                    <input type="file" accept="image/jpeg,image/png,image/jpg,image/webp,image/avif" @change="onImageChange" class="text-xs" />
                    <div v-if="court?.primary_image?.url && !form.delete_image" class="pt-1">
                        <button type="button" @click="removeImage" class="text-xs font-semibold text-rose-600 hover:underline">
                            Remove current photo
                        </button>
                    </div>
                    <div v-if="form.delete_image" class="text-xs text-rose-500 font-medium">
                        Photo marked for deletion on save.
                    </div>
                </div>
            </div>
            <InputError :message="form.errors.image" />
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

        <!-- Dynamic Time Slot Specific Pricing Section -->
        <div class="space-y-4 rounded-xl border border-input p-4 bg-muted/20">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-semibold">Time Slot Specific Pricing (Optional)</h3>
                    <p class="text-xs text-muted-foreground">
                        Set custom hourly prices for specific time slots. Slots left blank or 0 will default to the court's <strong>Base Price</strong> (₱{{ form.base_price || '0.00' }}).
                    </p>
                </div>
                <button
                    type="button"
                    @click="showAddSlotInput = !showAddSlotInput"
                    class="inline-flex items-center gap-1 shrink-0 rounded-lg bg-emerald-600/10 px-3 py-1.5 text-xs font-bold text-emerald-600 hover:bg-emerald-600/20 transition-all cursor-pointer border border-emerald-600/30"
                >
                    <Plus class="size-3.5" />
                    <span>Add Time Slot</span>
                </button>
            </div>

            <!-- Inline Add Custom Time Slot Form -->
            <div v-if="showAddSlotInput" class="flex flex-wrap items-center gap-2 p-3 rounded-lg border border-emerald-500/30 bg-background shadow-xs">
                <span class="text-xs font-bold">New Slot Time:</span>
                <div class="flex items-center gap-1 text-xs font-bold">
                    <select v-model="newSlotHour" class="rounded-md border border-input bg-background px-2 py-1 text-xs font-bold">
                        <option v-for="h in ['01','02','03','04','05','06','07','08','09','10','11','12']" :key="h" :value="h">{{ h }}</option>
                    </select>
                    <span class="text-muted-foreground">:</span>
                    <select v-model="newSlotMinute" class="rounded-md border border-input bg-background px-2 py-1 text-xs font-bold">
                        <option value="00">00</option>
                        <option value="15">15</option>
                        <option value="30">30</option>
                        <option value="45">45</option>
                    </select>
                    <select v-model="newSlotPeriod" class="rounded-md border border-input bg-background px-2 py-1 text-xs font-bold">
                        <option value="AM">AM</option>
                        <option value="PM">PM</option>
                    </select>
                </div>
                <button
                    type="button"
                    @click="addCustomTimeSlot"
                    class="rounded-md bg-emerald-600 px-3 py-1 text-xs font-bold text-white hover:bg-emerald-700 transition-colors cursor-pointer"
                >
                    Add Slot
                </button>
                <button
                    type="button"
                    @click="showAddSlotInput = false"
                    class="rounded-md bg-muted px-2.5 py-1 text-xs font-semibold text-muted-foreground hover:bg-muted/80 cursor-pointer"
                >
                    Cancel
                </button>
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                <div v-for="slot in allTimeSlots" :key="slot" class="space-y-1 rounded-lg border border-input bg-background p-2.5 shadow-2xs">
                    <div class="flex items-center justify-between text-[11px] font-bold text-foreground">
                        <span>{{ slot }}</span>
                        <div class="flex items-center gap-1">
                            <span class="text-[10px] font-normal text-muted-foreground">
                                {{ form.slot_prices[slot] && parseFloat(String(form.slot_prices[slot])) > 0 ? 'Custom' : 'Default' }}
                            </span>
                            <button
                                v-if="!isDefaultTimeSlot(slot)"
                                type="button"
                                @click="removeCustomTimeSlot(slot)"
                                class="text-rose-500 hover:text-rose-700 p-0.5 rounded cursor-pointer"
                                title="Remove custom slot"
                            >
                                <Trash2 class="size-3" />
                            </button>
                        </div>
                    </div>
                    <div class="relative">
                        <span class="absolute left-2.5 top-1.5 text-xs text-muted-foreground">₱</span>
                        <Input
                            v-model="form.slot_prices[slot]"
                            type="number"
                            step="0.01"
                            min="0"
                            :placeholder="form.base_price ? String(form.base_price) : '0.00'"
                            class="h-8 pl-6 text-xs font-semibold"
                        />
                    </div>
                </div>
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
