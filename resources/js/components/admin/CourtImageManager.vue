<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Upload, Star, Trash2, Image as ImageIcon, Check } from '@lucide/vue';

interface ImageItem {
    id: number;
    path: string;
    url: string;
    is_primary: boolean;
    sort_order: number;
}

const props = defineProps<{
    courtId: number;
    images: ImageItem[];
}>();

const uploadForm = useForm({
    image: null as File | null,
    is_primary: false,
});

const previewUrl = ref<string | null>(null);

function handleFileSelect(event: Event) {
    const input = event.target as HTMLInputElement;
    if (input.files && input.files[0]) {
        uploadForm.image = input.files[0];
        previewUrl.value = URL.createObjectURL(input.files[0]);
    }
}

function submitUpload() {
    if (!uploadForm.image) return;

    uploadForm.post(`/admin/courts/${props.courtId}/images`, {
        preserveScroll: true,
        onSuccess: () => {
            uploadForm.reset();
            previewUrl.value = null;
        },
    });
}

const actionForm = useForm({});

function setPrimary(image: ImageItem) {
    actionForm.patch(`/admin/courts/${props.courtId}/images/${image.id}/primary`, {
        preserveScroll: true,
    });
}

function deleteImage(image: ImageItem) {
    if (confirm('Are you sure you want to remove this image?')) {
        actionForm.delete(`/admin/courts/${props.courtId}/images/${image.id}`, {
            preserveScroll: true,
        });
    }
}
</script>

<template>
    <div class="space-y-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-neutral-900 dark:text-white">Court Display & Promotional Images</h3>
                <p class="text-sm text-neutral-500 dark:text-neutral-400">
                    Upload high-resolution hero display image and gallery photos. High resolution 16:9 images recommended.
                </p>
            </div>
        </div>

        <!-- Image Upload Form -->
        <form @submit.prevent="submitUpload" class="rounded-xl border border-neutral-200 bg-neutral-50/50 p-4 dark:border-neutral-800 dark:bg-neutral-900/50">
            <div class="flex flex-col sm:flex-row items-center gap-4">
                <label class="flex-1 w-full cursor-pointer flex items-center justify-center gap-2 border-2 border-dashed border-neutral-300 dark:border-neutral-700 rounded-lg p-4 text-sm text-neutral-600 dark:text-neutral-300 hover:border-emerald-500 hover:text-emerald-600 transition-colors">
                    <Upload class="w-5 h-5" />
                    <span>{{ uploadForm.image ? uploadForm.image.name : 'Choose Court Image File' }}</span>
                    <input type="file" accept="image/*" @change="handleFileSelect" class="hidden" />
                </label>

                <label class="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300 cursor-pointer select-none">
                    <input type="checkbox" v-model="uploadForm.is_primary" class="rounded border-neutral-300 text-emerald-600 focus:ring-emerald-500 dark:border-neutral-700" />
                    <span>Set as Main Hero Image</span>
                </label>

                <button
                    type="submit"
                    :disabled="!uploadForm.image || uploadForm.processing"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-medium text-sm hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center gap-2"
                >
                    <Upload class="w-4 h-4" />
                    <span>Upload</span>
                </button>
            </div>

            <!-- Preview box -->
            <div v-if="previewUrl" class="mt-4 flex items-center gap-3">
                <img :src="previewUrl" class="h-20 w-32 object-cover rounded-md border border-neutral-200 dark:border-neutral-800" />
                <span class="text-xs text-neutral-500">Selected file preview</span>
            </div>

            <div v-if="uploadForm.errors.image" class="mt-2 text-xs text-rose-500">
                {{ uploadForm.errors.image }}
            </div>
        </form>

        <!-- Current Images Gallery Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
            <div
                v-for="img in images"
                :key="img.id"
                class="group relative rounded-xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 overflow-hidden shadow-sm hover:shadow-md transition-all"
            >
                <div class="aspect-video w-full overflow-hidden bg-neutral-100 dark:bg-neutral-800">
                    <img :src="img.url" :alt="'Court image ' + img.id" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" />
                </div>

                <!-- Primary Badge -->
                <div v-if="img.is_primary" class="absolute top-2 left-2 px-2.5 py-1 bg-emerald-600 text-white text-xs font-bold rounded-md shadow flex items-center gap-1">
                    <Star class="w-3.5 h-3.5 fill-current" />
                    <span>Main Display Image</span>
                </div>

                <!-- Action Controls Overlay -->
                <div class="p-3 flex items-center justify-between border-t border-neutral-100 dark:border-neutral-800 bg-white/90 dark:bg-neutral-900/90 backdrop-blur-sm">
                    <button
                        v-if="!img.is_primary"
                        @click="setPrimary(img)"
                        class="text-xs text-neutral-600 dark:text-neutral-300 hover:text-emerald-600 dark:hover:text-emerald-400 font-medium flex items-center gap-1 transition-colors"
                    >
                        <Check class="w-3.5 h-3.5" />
                        <span>Set as Main Hero</span>
                    </button>
                    <span v-else class="text-xs font-medium text-emerald-600 dark:text-emerald-400 flex items-center gap-1">
                        <Check class="w-3.5 h-3.5" /> Active Hero
                    </span>

                    <button
                        @click="deleteImage(img)"
                        class="text-xs text-rose-500 hover:text-rose-700 dark:hover:text-rose-400 font-medium flex items-center gap-1 transition-colors p-1"
                        title="Delete Image"
                    >
                        <Trash2 class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            <div v-if="images.length === 0" class="col-span-full py-8 text-center text-neutral-500 dark:text-neutral-400 border border-dashed rounded-xl">
                <ImageIcon class="w-8 h-8 mx-auto text-neutral-400 mb-2" />
                <p class="text-sm">No promotional images uploaded yet for this court.</p>
            </div>
        </div>
    </div>
</template>
