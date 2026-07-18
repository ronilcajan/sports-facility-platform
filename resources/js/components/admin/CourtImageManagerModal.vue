<script setup lang="ts">
import { ref, watch, onUnmounted } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { UploadCloud, Star, Trash2, X, Image as ImageIcon, CheckCircle2 } from '@lucide/vue';
import InputError from '@/components/InputError.vue';

export interface CourtImageItem {
    id: number;
    path: string;
    url: string;
    is_primary: boolean;
    sort_order?: number;
}

export interface CourtTarget {
    id: number;
    name: string;
    images?: CourtImageItem[];
    primary_image?: CourtImageItem | null;
}

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        court: CourtTarget | null;
        uploadRoute: string;
        primaryRoutePrefix: string;
        deleteRoutePrefix?: string;
        canDelete?: boolean;
    }>(),
    {
        canDelete: true,
    }
);

const emit = defineEmits(['close']);

// File upload form
const uploadForm = useForm({
    image: null as File | null,
    is_primary: false,
});

const previewUrl = ref<string | null>(null);
const fileInput = ref<HTMLInputElement | null>(null);

function onFileSelect(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        setFile(target.files[0]);
    }
}

function setFile(file: File) {
    // Validate client side size (5MB max)
    if (file.size > 5 * 1024 * 1024) {
        alert('File size exceeds 5MB limit. Please choose a smaller image.');
        return;
    }
    uploadForm.image = file;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
    previewUrl.value = URL.createObjectURL(file);
}

function handleDrop(event: DragEvent) {
    if (event.dataTransfer?.files && event.dataTransfer.files[0]) {
        setFile(event.dataTransfer.files[0]);
    }
}

function clearSelectedFile() {
    uploadForm.image = null;
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
    if (fileInput.value) {
        fileInput.value.value = '';
    }
}

function submitUpload() {
    if (!uploadForm.image || !props.court) return;
    uploadForm.post(props.uploadRoute, {
        preserveScroll: true,
        onSuccess: () => {
            clearSelectedFile();
        },
    });
}

function setPrimaryImage(image: CourtImageItem) {
    if (!props.court) return;
    const actionForm = useForm({});
    actionForm.patch(`${props.primaryRoutePrefix}/${image.id}/primary`, {
        preserveScroll: true,
    });
}

function deleteImage(image: CourtImageItem) {
    if (!props.court || !props.canDelete || !props.deleteRoutePrefix) return;
    if (!confirm('Are you sure you want to delete this court image?')) return;
    const actionForm = useForm({});
    actionForm.delete(`${props.deleteRoutePrefix}/${image.id}`, {
        preserveScroll: true,
    });
}

watch(
    () => props.isOpen,
    (val) => {
        if (!val) {
            clearSelectedFile();
        }
    }
);

onUnmounted(() => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
    }
});
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-neutral-900/60 backdrop-blur-sm">
        <div class="bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-3xl max-w-2xl w-full overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-neutral-100 dark:border-neutral-800 flex items-center justify-between bg-neutral-50/50 dark:bg-neutral-900/50">
                <div>
                    <h3 class="font-black text-lg text-neutral-900 dark:text-white tracking-tight flex items-center gap-2">
                        <ImageIcon class="w-5 h-5 text-emerald-600" />
                        Court Image Gallery: {{ court?.name }}
                    </h3>
                    <p class="text-xs text-neutral-500">Upload promotional court photos or set the primary hero card thumbnail.</p>
                </div>
                <button
                    @click="emit('close')"
                    class="p-1.5 rounded-full text-neutral-400 hover:text-neutral-900 dark:hover:text-white hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <div class="p-6 overflow-y-auto space-y-6 flex-1">
                <!-- Upload New Image Section -->
                <div class="rounded-2xl border border-dashed border-neutral-300 dark:border-neutral-700 p-5 bg-neutral-50/50 dark:bg-neutral-850 space-y-4">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-400">Upload New Photo</h4>

                    <div
                        @dragover.prevent
                        @drop.prevent="handleDrop"
                        class="border-2 border-dashed border-neutral-200 dark:border-neutral-700 rounded-xl p-4 text-center hover:border-emerald-500 transition-colors cursor-pointer bg-white dark:bg-neutral-900"
                        @click="fileInput?.click()"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/jpeg,image/png,image/jpg,image/webp"
                            class="hidden"
                            @change="onFileSelect"
                        />

                        <div v-if="!previewUrl" class="flex flex-col items-center justify-center space-y-1 py-3">
                            <UploadCloud class="w-8 h-8 text-neutral-400" />
                            <span class="text-xs font-bold text-neutral-700 dark:text-neutral-300">Click or drag & drop image to preview</span>
                            <span class="text-[10px] text-neutral-400">Supports JPG, PNG, WEBP up to 5MB</span>
                        </div>

                        <div v-else class="relative flex flex-col items-center justify-center">
                            <img :src="previewUrl" alt="Preview" class="max-h-48 rounded-lg object-contain border border-neutral-200 dark:border-neutral-700" />
                            <button
                                type="button"
                                @click.stop="clearSelectedFile"
                                class="absolute top-1 right-1 p-1 bg-neutral-900/80 text-white rounded-full hover:bg-rose-600 transition-colors"
                                title="Remove preview"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <div v-if="previewUrl" class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-1">
                        <label class="flex items-center gap-2 cursor-pointer text-xs font-semibold text-neutral-700 dark:text-neutral-300">
                            <input
                                v-model="uploadForm.is_primary"
                                type="checkbox"
                                class="rounded border-neutral-300 text-emerald-600 focus:ring-emerald-500"
                            />
                            <span>Set as primary hero display thumbnail</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="clearSelectedFile"
                                class="px-3 py-1.5 text-xs font-bold text-neutral-600 dark:text-neutral-400 hover:text-neutral-900 dark:hover:text-white"
                            >
                                Cancel
                            </button>
                            <button
                                type="button"
                                @click="submitUpload"
                                :disabled="uploadForm.processing"
                                class="px-4 py-1.5 bg-emerald-600 text-white rounded-xl text-xs font-bold shadow-md hover:bg-emerald-700 disabled:opacity-50 transition-colors"
                            >
                                {{ uploadForm.processing ? 'Uploading...' : 'Save & Upload Photo' }}
                            </button>
                        </div>
                    </div>

                    <InputError :message="uploadForm.errors.image" />
                </div>

                <!-- Existing Images Gallery -->
                <div class="space-y-3">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-neutral-400">Current Court Images</h4>

                    <div v-if="court?.images && court.images.length > 0" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div
                            v-for="img in court.images"
                            :key="img.id"
                            class="relative group rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 overflow-hidden shadow-sm flex flex-col"
                        >
                            <div class="aspect-video relative overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                                <img :src="img.url" :alt="court.name" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                                <span
                                    v-if="img.is_primary"
                                    class="absolute top-2 left-2 px-2 py-0.5 bg-emerald-600 text-white text-[10px] font-black rounded-full shadow-sm flex items-center gap-1"
                                >
                                    <Star class="w-3 h-3 fill-white" /> Primary Hero
                                </span>
                            </div>

                            <div class="p-3 flex items-center justify-between gap-2 border-t border-neutral-100 dark:border-neutral-800 bg-neutral-50/50 dark:bg-neutral-900/50">
                                <button
                                    v-if="!img.is_primary"
                                    @click="setPrimaryImage(img)"
                                    class="text-xs font-bold text-emerald-600 hover:text-emerald-700 hover:underline flex items-center gap-1"
                                >
                                    <CheckCircle2 class="w-3.5 h-3.5" /> Set as Primary
                                </button>
                                <span v-else class="text-[11px] font-bold text-emerald-600 flex items-center gap-1">
                                    <Star class="w-3.5 h-3.5 fill-emerald-600" /> Hero Display
                                </span>

                                <button
                                    v-if="canDelete && deleteRoutePrefix"
                                    @click="deleteImage(img)"
                                    class="p-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg transition-colors"
                                    title="Delete Image"
                                >
                                    <Trash2 class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="p-8 text-center text-xs text-neutral-400 border border-dashed rounded-2xl">
                        No images uploaded for this court yet. Upload your first court image above.
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
