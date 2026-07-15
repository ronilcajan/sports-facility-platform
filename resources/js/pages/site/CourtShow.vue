<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed, nextTick, onMounted, onUnmounted } from 'vue';
import SiteCourtCard from '@/components/site/SiteCourtCard.vue';
import BookingModal from '@/components/site/BookingModal.vue';
import { courts as courtsRoute } from '@/routes/site';
import type { PublicCourt } from '@/types';

const props = defineProps<{
    court: PublicCourt & { images?: string[] };
    relatedCourts: PublicCourt[];
}>();

const activeCourt = ref<PublicCourt | null>(null);
const isBookingOpen = ref(false);

const activeImageIndex = ref(0);
const isLightboxOpen = ref(false);
const lightboxIndex = ref(0);
const lightboxRef = ref<HTMLElement | null>(null);

// Get all images, or a fallback array if none exist
const allImages = computed(() => {
    const imagesList = props.court.images || [];
    const list = [...imagesList];

    // Ensure primary_image_url is the first element
    if (
        props.court.primary_image_url &&
        !list.includes(props.court.primary_image_url)
    ) {
        list.unshift(props.court.primary_image_url);
    }

    // Fallback if no images are defined
    if (list.length === 0) {
        return [
            '/images/court_pickleball.png',
            '/images/hero_pickleball.png',
            '/images/cta_pickleball.png',
        ];
    }
    return list;
});

const activeImage = computed(() => {
    return (
        allImages.value[activeImageIndex.value] ||
        '/images/court_pickleball.png'
    );
});

function handleBook(court: PublicCourt) {
    activeCourt.value = court;
    isBookingOpen.value = true;
}

function openLightbox(index: number) {
    lightboxIndex.value = index;
    isLightboxOpen.value = true;
    nextTick(() => {
        lightboxRef.value?.focus();
    });
}

function closeLightbox() {
    isLightboxOpen.value = false;
}

function nextImage() {
    if (allImages.value.length <= 1) {
        return;
    }
    lightboxIndex.value = (lightboxIndex.value + 1) % allImages.value.length;
}

function prevImage() {
    if (allImages.value.length <= 1) {
        return;
    }
    lightboxIndex.value =
        (lightboxIndex.value - 1 + allImages.value.length) %
        allImages.value.length;
}

// Add global key handlers for keyboard navigation when lightbox is open
function handleKeyDown(e: KeyboardEvent) {
    if (!isLightboxOpen.value) {
        return;
    }
    if (e.key === 'ArrowRight') {
        nextImage();
    } else if (e.key === 'ArrowLeft') {
        prevImage();
    } else if (e.key === 'Escape') {
        closeLightbox();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onUnmounted(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <Head :title="court.name">
        <meta
            name="description"
            :content="
                court.description ||
                'Austin Dinkyard premium pickleball court reservation details.'
            "
        />
    </Head>

    <div class="min-h-screen bg-surface pb-24 text-content">
        <!-- 1. Breadcrumbs Navigation Header -->
        <div
            class="border-b border-line bg-surface-elevated/45 py-4 backdrop-blur-sm"
        >
            <div
                class="mx-auto flex max-w-6xl items-center gap-2 px-4 text-xs font-semibold tracking-wider text-content-muted uppercase sm:px-6"
            >
                <Link :href="'/'" class="transition-colors hover:text-brand"
                    >Home</Link
                >
                <svg
                    class="size-3"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 5l7 7-7 7"
                    />
                </svg>
                <Link
                    :href="courtsRoute().url"
                    class="transition-colors hover:text-brand"
                    >Courts</Link
                >
                <svg
                    class="size-3"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 5l7 7-7 7"
                    />
                </svg>
                <span class="font-bold text-content">{{ court.name }}</span>
            </div>
        </div>

        <div class="mx-auto max-w-6xl px-4 pt-10 sm:px-6">
            <!-- 2. Main Page Grid Layout -->
            <div class="grid items-start gap-12 lg:grid-cols-[1.3fr_0.7fr]">
                <!-- Left Column: Image and Details -->
                <div class="space-y-10">
                    <!-- Court Image Showcase -->
                    <div class="space-y-4">
                        <div
                            class="group relative aspect-[16/9] cursor-zoom-in overflow-hidden rounded-2xl border border-line bg-surface-inverse shadow-lg"
                            @click="openLightbox(activeImageIndex)"
                        >
                            <img
                                :src="activeImage"
                                :alt="court.name"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-103"
                                loading="eager"
                            />
                            <div
                                class="pointer-events-none absolute inset-0 bg-gradient-to-t from-surface-inverse/70 via-transparent to-transparent"
                            ></div>

                            <span
                                class="absolute top-6 left-6 rounded-full bg-brand/90 px-4 py-1.5 text-xs font-bold tracking-widest text-brand-foreground uppercase shadow backdrop-blur-md"
                            >
                                {{ court.sport_type }}
                            </span>

                            <!-- View Fullscreen Button Overlay -->
                            <div
                                class="pointer-events-none absolute right-6 bottom-6 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
                            >
                                <span
                                    class="flex items-center gap-2 rounded-full border border-line bg-surface-elevated/95 px-4 py-2 text-xs font-bold text-content shadow-md backdrop-blur-md"
                                >
                                    <svg
                                        class="size-4 text-brand"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2.5"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                        />
                                    </svg>
                                    View Fullscreen
                                </span>
                            </div>
                        </div>

                        <!-- Gallery Thumbnails -->
                        <div
                            v-if="allImages.length > 1"
                            class="flex scrollbar-thin scrollbar-thumb-line scrollbar-track-transparent gap-3 overflow-x-auto pb-2"
                        >
                            <button
                                v-for="(img, idx) in allImages"
                                :key="idx"
                                type="button"
                                class="relative aspect-[16/10] w-24 shrink-0 overflow-hidden rounded-xl border-2 transition-all duration-200 focus:outline-none sm:w-28"
                                :class="
                                    activeImageIndex === idx
                                        ? 'scale-95 border-brand shadow-md shadow-brand/20'
                                        : 'border-line hover:scale-97 hover:border-brand/40'
                                "
                                @click="activeImageIndex = idx"
                            >
                                <img
                                    :src="img"
                                    class="h-full w-full object-cover"
                                    alt="Court Thumbnail"
                                />
                                <div
                                    v-if="activeImageIndex !== idx"
                                    class="absolute inset-0 bg-surface-inverse/10 transition-colors hover:bg-transparent"
                                />
                            </button>
                        </div>
                    </div>

                    <!-- Court Narrative & Specifications -->
                    <div class="space-y-6">
                        <div class="border-b border-line pb-6">
                            <span
                                class="text-xs font-bold tracking-[0.2em] text-brand uppercase"
                                >Premium Sport Facility</span
                            >
                            <h1
                                class="mt-2 font-display text-4xl leading-none font-black tracking-tight text-content"
                            >
                                {{ court.name }}
                            </h1>
                            <p
                                class="mt-2 text-xs font-semibold tracking-wider text-content-muted uppercase"
                            >
                                {{ court.venue ? court.venue.name + (court.venue.address ? ' • ' + court.venue.address : '') : 'Austin Main Yard' }}
                            </p>
                        </div>

                        <!-- Description -->
                        <div
                            class="prose max-w-none leading-relaxed text-content-muted"
                        >
                            <p v-if="court.description">
                                {{ court.description }}
                            </p>
                            <p v-else>
                                Welcome to Austin's premium playing environment.
                                This court features professional post-tensioned
                                concrete finished with a premium cushioned
                                acrylic surface. Engineered specifically to
                                reduce joint fatigue and supply a true,
                                consistent ball bounce under tournament
                                conditions.
                            </p>
                        </div>

                        <!-- Highlights Specs Grid -->
                        <div class="grid gap-4 pt-6 sm:grid-cols-2">
                            <div
                                class="flex items-start gap-3 rounded-xl border border-line bg-surface-elevated/40 p-4"
                            >
                                <div
                                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand"
                                >
                                    <svg
                                        class="size-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-content">
                                        Cushioned Acrylic
                                    </h4>
                                    <p
                                        class="mt-0.5 text-xs text-content-muted"
                                    >
                                        True bounce, softer response on knees.
                                    </p>
                                </div>
                            </div>
                            <div
                                class="flex items-start gap-3 rounded-xl border border-line bg-surface-elevated/40 p-4"
                            >
                                <div
                                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand"
                                >
                                    <svg
                                        class="size-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707m0-12.728l.707.707m12.728 12.728l.707-.707M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-content">
                                        Pro-Grade Net System
                                    </h4>
                                    <p
                                        class="mt-0.5 text-xs text-content-muted"
                                    >
                                        Perfect tournament-height steel cord
                                        nets.
                                    </p>
                                </div>
                            </div>
                            <div
                                class="flex items-start gap-3 rounded-xl border border-line bg-surface-elevated/40 p-4"
                            >
                                <div
                                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand"
                                >
                                    <svg
                                        class="size-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l.707-.707m2.818 2.818A4 4 0 1112 5.05v2.9M12 12v3"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-content">
                                        LED Stadium Lights
                                    </h4>
                                    <p
                                        class="mt-0.5 text-xs text-content-muted"
                                    >
                                        Glare-free lighting rated for night
                                        play.
                                    </p>
                                </div>
                            </div>
                            <div
                                class="flex items-start gap-3 rounded-xl border border-line bg-surface-elevated/40 p-4"
                            >
                                <div
                                    class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-brand/10 text-brand"
                                >
                                    <svg
                                        class="size-5"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="text-sm font-bold text-content">
                                        Private Players Lounge
                                    </h4>
                                    <p
                                        class="mt-0.5 text-xs text-content-muted"
                                    >
                                        Shaded spectator benches and hydration.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Permanent Sticky Booking Card -->
                <div class="lg:sticky lg:top-24">
                    <div
                        class="space-y-6 rounded-2xl border border-line bg-surface-elevated p-6 shadow-md"
                    >
                        <div>
                            <span
                                class="block text-xs font-bold tracking-wider text-content-muted uppercase"
                                >Hourly Rate</span
                            >
                            <div class="mt-1 flex items-baseline gap-1">
                                <span class="text-4xl font-black text-content"
                                    >${{ court.base_price }}</span
                                >
                                <span class="text-sm text-content-muted"
                                    >/
                                    {{ court.slot_duration_minutes }} min</span
                                >
                            </div>
                        </div>

                        <!-- Amenities list -->
                        <div
                            class="space-y-3 border-t border-b border-line py-4"
                        >
                            <div
                                class="flex items-center gap-3 text-sm text-content-muted"
                            >
                                <span
                                    class="size-2 shrink-0 rounded-full bg-brand"
                                ></span>
                                <span>No membership required</span>
                            </div>
                            <div
                                class="flex items-center gap-3 text-sm text-content-muted"
                            >
                                <span
                                    class="size-2 shrink-0 rounded-full bg-brand"
                                ></span>
                                <span
                                    >Free parking & equipment rental
                                    check-in</span
                                >
                            </div>
                            <div
                                class="flex items-center gap-3 text-sm text-content-muted"
                            >
                                <span
                                    class="size-2 shrink-0 rounded-full bg-brand"
                                ></span>
                                <span>Reschedule up to 12 hours prior</span>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="w-full rounded-full bg-brand py-4 text-base font-bold text-brand-foreground shadow-lg shadow-brand/20 transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand/95 hover:shadow-brand/35"
                            @click="handleBook(court)"
                        >
                            Book this Court Now
                        </button>
                    </div>
                </div>
            </div>

            <!-- 3. Related Courts Showcase -->
            <div class="mt-24 border-t border-line pt-12">
                <div
                    class="mb-10 flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <span
                            class="text-xs font-bold tracking-[0.2em] text-brand uppercase"
                            >EXPLORE SIMILAR</span
                        >
                        <h2
                            class="mt-2 font-display text-2xl font-black tracking-tight text-content sm:text-3xl"
                        >
                            Other Premium Courts
                        </h2>
                    </div>
                    <Link
                        :href="courtsRoute().url"
                        class="text-sm font-bold text-brand transition-colors hover:text-content"
                    >
                        See all courts →
                    </Link>
                </div>

                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <SiteCourtCard
                        v-for="rc in relatedCourts"
                        :key="rc.id"
                        :court="rc"
                        @book="handleBook"
                    />
                </div>
            </div>
        </div>
    </div>

    <!-- Booking Modal Component overlay -->
    <BookingModal
        :court="activeCourt"
        :is-open="isBookingOpen"
        @close="isBookingOpen = false"
    />

    <!-- Lightbox Modal overlay -->
    <Transition name="fade">
        <div
            v-if="isLightboxOpen"
            class="fixed inset-0 z-50 flex flex-col items-center justify-center bg-black/95 p-4 backdrop-blur-md outline-none sm:p-8"
            role="dialog"
            aria-modal="true"
            @keydown.esc="closeLightbox"
            @keydown.right="nextImage"
            @keydown.left="prevImage"
            tabindex="0"
            ref="lightboxRef"
        >
            <!-- Close Button -->
            <button
                type="button"
                class="absolute top-6 right-6 rounded-full p-2.5 text-white/70 transition-colors hover:bg-white/10 hover:text-white"
                @click="closeLightbox"
                aria-label="Close lightbox"
            >
                <svg
                    class="size-8"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12"
                    />
                </svg>
            </button>

            <!-- Main Lightbox Image Container -->
            <div
                class="relative flex w-full max-w-5xl flex-1 items-center justify-center"
            >
                <!-- Left Arrow -->
                <button
                    v-if="allImages.length > 1"
                    type="button"
                    class="absolute left-2 rounded-full p-3 text-white/70 transition-colors hover:bg-white/10 hover:text-white disabled:pointer-events-none disabled:opacity-30 sm:-left-20"
                    @click="prevImage"
                    aria-label="Previous image"
                >
                    <svg
                        class="size-10"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="3"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M15 19l-7-7 7-7"
                        />
                    </svg>
                </button>

                <!-- Image -->
                <img
                    :src="allImages[lightboxIndex]"
                    class="max-h-[80vh] max-w-full rounded-lg object-contain shadow-2xl transition-all duration-300"
                    :alt="`${court.name} detail view`"
                />

                <!-- Right Arrow -->
                <button
                    v-if="allImages.length > 1"
                    type="button"
                    class="absolute right-2 rounded-full p-3 text-white/70 transition-colors hover:bg-white/10 hover:text-white disabled:pointer-events-none disabled:opacity-30 sm:-right-20"
                    @click="nextImage"
                    aria-label="Next image"
                >
                    <svg
                        class="size-10"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        stroke-width="3"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M9 5l7 7-7 7"
                        />
                    </svg>
                </button>
            </div>

            <!-- Index Indicator & Caption -->
            <div
                class="mt-4 flex flex-col items-center gap-1.5 text-center text-white/90"
            >
                <p class="text-sm font-bold tracking-wider uppercase">
                    {{ court.name }}
                </p>
                <p
                    class="text-xs font-semibold tracking-widest text-white/60 uppercase"
                >
                    Image {{ lightboxIndex + 1 }} of {{ allImages.length }}
                </p>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition:
        opacity 0.25s ease,
        backdrop-filter 0.25s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
    backdrop-filter: blur(0px);
}
</style>
