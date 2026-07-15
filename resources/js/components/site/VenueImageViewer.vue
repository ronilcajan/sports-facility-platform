<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { ChevronLeft, ChevronRight, X, ZoomIn } from '@lucide/vue';

const props = withDefaults(
    defineProps<{
        isOpen: boolean;
        images: string[];
        initialIndex?: number;
        title?: string;
    }>(),
    {
        initialIndex: 0,
        title: 'Venue Image Preview',
    }
);

const emit = defineEmits<{
    (e: 'close'): void;
}>();

const currentIndex = ref(props.initialIndex);

watch(
    () => props.initialIndex,
    (newVal) => {
        currentIndex.value = newVal;
    }
);

watch(
    () => props.isOpen,
    (isOpen) => {
        if (isOpen) {
            currentIndex.value = props.initialIndex;
            if (typeof document !== 'undefined') {
                document.body.style.overflow = 'hidden';
            }
        } else {
            if (typeof document !== 'undefined') {
                document.body.style.overflow = '';
            }
        }
    }
);

const currentImage = computed(() => {
    if (props.images && props.images.length > 0) {
        return props.images[currentIndex.value] || props.images[0];
    }
    return '/images/hero_pickleball.png';
});

function prevImage() {
    if (props.images.length <= 1) return;
    currentIndex.value =
        (currentIndex.value - 1 + props.images.length) % props.images.length;
}

function nextImage() {
    if (props.images.length <= 1) return;
    currentIndex.value = (currentIndex.value + 1) % props.images.length;
}

function selectIndex(index: number) {
    currentIndex.value = index;
}

function handleKeydown(e: KeyboardEvent) {
    if (!props.isOpen) return;
    if (e.key === 'Escape') {
        emit('close');
    } else if (e.key === 'ArrowLeft') {
        prevImage();
    } else if (e.key === 'ArrowRight') {
        nextImage();
    }
}

onMounted(() => {
    if (typeof window !== 'undefined') {
        window.addEventListener('keydown', handleKeydown);
    }
});

onUnmounted(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('keydown', handleKeydown);
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4 backdrop-blur-lg select-none"
                @click.self="emit('close')"
            >
                <!-- Header Controls Bar -->
                <div
                    class="absolute top-4 right-4 left-4 z-20 flex items-center justify-between pointer-events-none"
                >
                    <div
                        class="flex items-center gap-2 rounded-full bg-black/50 px-4 py-1.5 backdrop-blur-md pointer-events-auto border border-white/10"
                    >
                        <ZoomIn class="size-4 text-brand" />
                        <span class="text-xs font-bold text-white tracking-wide truncate max-w-[200px] sm:max-w-md">
                            {{ title }}
                        </span>
                    </div>

                    <button
                        type="button"
                        @click="emit('close')"
                        class="flex size-10 items-center justify-center rounded-full border border-white/20 bg-white/10 text-white backdrop-blur-md transition-all hover:bg-white/20 hover:scale-105 pointer-events-auto cursor-pointer"
                        aria-label="Close image viewer"
                    >
                        <X class="size-5" />
                    </button>
                </div>

                <!-- Main Image Preview Container -->
                <div class="relative max-h-[80vh] max-w-5xl flex items-center justify-center overflow-hidden">
                    <img
                        :src="currentImage"
                        :alt="`${title} Preview`"
                        class="max-h-[75vh] max-w-full rounded-2xl object-contain shadow-2xl transition-transform duration-300 border border-white/10"
                    />

                    <!-- Previous Image Button -->
                    <button
                        v-if="images.length > 1"
                        type="button"
                        @click.stop="prevImage"
                        class="absolute left-2 top-1/2 -translate-y-1/2 flex size-12 items-center justify-center rounded-full border border-white/20 bg-black/60 text-white backdrop-blur-md transition-all hover:bg-black/80 hover:scale-110 cursor-pointer shadow-lg sm:left-4"
                        aria-label="Previous image"
                    >
                        <ChevronLeft class="size-6" />
                    </button>

                    <!-- Next Image Button -->
                    <button
                        v-if="images.length > 1"
                        type="button"
                        @click.stop="nextImage"
                        class="absolute right-2 top-1/2 -translate-y-1/2 flex size-12 items-center justify-center rounded-full border border-white/20 bg-black/60 text-white backdrop-blur-md transition-all hover:bg-black/80 hover:scale-110 cursor-pointer shadow-lg sm:right-4"
                        aria-label="Next image"
                    >
                        <ChevronRight class="size-6" />
                    </button>
                </div>

                <!-- Footer Carousel Strip & Counter -->
                <div
                    class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex flex-col items-center gap-2 max-w-full px-4"
                >
                    <!-- Counter Pill -->
                    <span
                        v-if="images.length > 1"
                        class="rounded-full bg-black/60 px-3 py-1 text-[11px] font-bold tracking-wider text-white/80 uppercase backdrop-blur-md border border-white/10"
                    >
                        {{ currentIndex + 1 }} / {{ images.length }}
                    </span>

                    <!-- Thumbnail Strip -->
                    <div
                        v-if="images.length > 1"
                        class="flex items-center gap-2 overflow-x-auto p-1 max-w-md"
                    >
                        <button
                            v-for="(img, idx) in images"
                            :key="idx"
                            type="button"
                            @click.stop="selectIndex(idx)"
                            class="relative size-12 shrink-0 overflow-hidden rounded-lg border-2 transition-all cursor-pointer"
                            :class="[
                                currentIndex === idx
                                    ? 'border-brand scale-105 shadow-md shadow-brand/40'
                                    : 'border-white/20 opacity-50 hover:opacity-100'
                            ]"
                        >
                            <img :src="img" :alt="`Thumbnail ${idx + 1}`" class="h-full w-full object-cover" />
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
