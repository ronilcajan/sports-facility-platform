<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    MapPin,
    Phone,
    Mail,
    Dumbbell,
    CalendarCheck,
    ChevronLeft,
    ShieldCheck,
    Clock,
    Sparkles,
    CheckCircle,
    ZoomIn,
} from '@lucide/vue';
import BookingModal from '@/components/site/BookingModal.vue';
import VenueImageViewer from '@/components/site/VenueImageViewer.vue';
import type { CatalogVenue } from '@/components/site/SiteVenueCard.vue';
import type { PublicCourt } from '@/types';

const props = defineProps<{
    venue: CatalogVenue & { images?: string[] };
    venues: CatalogVenue[];
}>();

const isBookingModalOpen = ref(false);
const selectedCourtForBooking = ref<PublicCourt | null>(null);

const isImageViewerOpen = ref(false);
const previewImageIndex = ref(0);

const venueImages = computed(() => {
    const list = [...(props.venue.images || [])];
    const cover = props.venue.cover_image_url || '/images/hero_pickleball.png';
    if (!list.includes(cover)) {
        list.unshift(cover);
    }
    return list;
});

function openBookingForCourt(court?: PublicCourt) {
    selectedCourtForBooking.value = court || null;
    isBookingModalOpen.value = true;
}

function openImageViewer(index = 0) {
    previewImageIndex.value = index;
    isImageViewerOpen.value = true;
}
</script>

<template>
    <Head :title="`${venue.name} - Venue Details`">
        <meta
            name="description"
            :content="venue.description || `Explore courts and book a game at ${venue.name}.`"
        />
    </Head>

    <div>
        <!-- Hero Header -->
        <section class="relative overflow-hidden bg-surface-inverse text-white">
            <!-- Cover Background Photo with Overlay Gradient -->
            <div class="absolute inset-0">
                <img
                    :src="venue.cover_image_url || '/images/hero_pickleball.png'"
                    :alt="venue.name"
                    class="h-full w-full object-cover opacity-35"
                />
                <div class="absolute inset-0 bg-gradient-to-t from-surface-inverse via-surface-inverse/80 to-transparent" />
            </div>

            <div class="relative mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8 lg:py-24">
                <div class="flex items-center justify-between gap-4 mb-8">
                    <Link
                        href="/courts"
                        class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-bold text-white backdrop-blur-md transition-colors hover:bg-white/20"
                    >
                        <ChevronLeft class="size-4" />
                        <span>Back to All Venues</span>
                    </Link>

                    <!-- Preview Venue Image Button -->
                    <button
                        type="button"
                        @click="openImageViewer(0)"
                        class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-bold text-white backdrop-blur-md transition-all hover:bg-white/20 hover:scale-105 cursor-pointer"
                    >
                        <ZoomIn class="size-4 text-brand" />
                        <span>Preview Venue Photo</span>
                    </button>
                </div>

                <div class="grid gap-8 lg:grid-cols-12 lg:items-center">
                    <div class="space-y-6 lg:col-span-8">
                        <div class="flex flex-wrap items-center gap-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-brand px-3.5 py-1 text-xs font-bold text-brand-foreground shadow">
                                <Dumbbell class="size-3.5" />
                                <span>{{ venue.courts_count }} {{ venue.courts_count === 1 ? 'Court' : 'Courts' }} Available</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3.5 py-1 text-xs font-bold text-emerald-400 border border-emerald-500/30">
                                <span class="size-2 animate-ping rounded-full bg-emerald-400" />
                                <span>Active Facility</span>
                            </span>
                        </div>

                        <h1 class="font-display text-4xl font-black tracking-tight sm:text-5xl lg:text-6xl">
                            {{ venue.name }}
                        </h1>

                        <p v-if="venue.description" class="text-lg leading-relaxed text-slate-300 max-w-3xl">
                            {{ venue.description }}
                        </p>

                        <!-- Contact & Location Pill Bar -->
                        <div class="flex flex-wrap items-center gap-6 text-sm text-slate-300 border-t border-white/10 pt-6">
                            <div v-if="venue.address" class="flex items-center gap-2">
                                <MapPin class="size-4 text-brand shrink-0" />
                                <span>{{ venue.address }}</span>
                            </div>
                            <div v-if="venue.phone" class="flex items-center gap-2">
                                <Phone class="size-4 text-brand shrink-0" />
                                <span>{{ venue.phone }}</span>
                            </div>
                            <div v-if="venue.email" class="flex items-center gap-2">
                                <Mail class="size-4 text-brand shrink-0" />
                                <span>{{ venue.email }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hero Quick Action Box -->
                    <div class="lg:col-span-4">
                        <div class="rounded-2xl border border-white/15 bg-white/10 p-6 backdrop-blur-xl shadow-2xl space-y-4">
                            <div class="flex items-center justify-between border-b border-white/10 pb-4">
                                <div>
                                    <span class="text-xs font-bold tracking-wider text-slate-300 uppercase">Operating Status</span>
                                    <h4 class="text-lg font-black text-white">Open Daily</h4>
                                </div>
                                <span class="rounded-full bg-brand/20 p-2.5 text-brand">
                                    <Clock class="size-6" />
                                </span>
                            </div>

                            <p class="text-xs leading-relaxed text-slate-300">
                                Reserve your preferred court at {{ venue.name }} with instant real-time availability confirmation.
                            </p>

                            <button
                                type="button"
                                @click="openBookingForCourt()"
                                class="w-full flex items-center justify-center gap-2 rounded-full bg-brand py-3.5 px-6 text-sm font-black text-brand-foreground shadow-lg shadow-brand/25 transition-all duration-300 hover:scale-102 hover:bg-brand/95 cursor-pointer"
                            >
                                <CalendarCheck class="size-5" />
                                <span>Book Court at this Venue</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Facility Features Bar -->
        <section class="border-b border-line bg-surface-elevated/40 py-6">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-brand/10 p-2.5 text-brand">
                            <ShieldCheck class="size-5" />
                        </div>
                        <div>
                            <h5 class="text-xs font-black text-content uppercase tracking-wider">Great Courts</h5>
                            <p class="text-[11px] text-content-muted">Well-kept &amp; welcoming</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-brand/10 p-2.5 text-brand">
                            <Sparkles class="size-5" />
                        </div>
                        <div>
                            <h5 class="text-xs font-black text-content uppercase tracking-wider">LED Floodlighting</h5>
                            <p class="text-[11px] text-content-muted">Optimal night play</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-brand/10 p-2.5 text-brand">
                            <Clock class="size-5" />
                        </div>
                        <div>
                            <h5 class="text-xs font-black text-content uppercase tracking-wider">Hourly Slots</h5>
                            <p class="text-[11px] text-content-muted">Flexible booking times</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-brand/10 p-2.5 text-brand">
                            <CheckCircle class="size-5" />
                        </div>
                        <div>
                            <h5 class="text-xs font-black text-content uppercase tracking-wider">Lounge & Amenities</h5>
                            <p class="text-[11px] text-content-muted">Clean equipment & gear</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Courts Listing Section under this Venue -->
        <section class="py-16 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-12">
                <div>
                    <span class="text-xs font-extrabold tracking-widest text-brand uppercase">Available Courts</span>
                    <h2 class="mt-1 font-display text-3xl font-black tracking-tight text-content sm:text-4xl">
                        Courts at {{ venue.name }}
                    </h2>
                    <p class="mt-2 text-sm text-content-muted max-w-2xl">
                        Choose your preferred court below to check schedule availability and complete your instant booking.
                    </p>
                </div>

                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="c in venue.courts"
                        :key="c.id"
                        class="group flex flex-col overflow-hidden rounded-[var(--site-radius,1.25rem)] border border-line bg-surface-elevated shadow-md transition-all duration-300 hover:-translate-y-2 hover:border-brand/50 hover:shadow-2xl"
                    >
                        <!-- Court Cover Image (Clickable for preview) -->
                        <div
                            @click="openImageViewer(0)"
                            class="relative aspect-[16/10] overflow-hidden bg-surface-inverse cursor-pointer"
                        >
                            <img
                                :src="c.primary_image_url || '/images/court_pickleball.png'"
                                :alt="c.name"
                                class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                            />
                            <div class="absolute inset-0 bg-gradient-to-t from-surface-inverse/80 via-transparent to-transparent" />

                            <span class="absolute top-4 left-4 rounded-full bg-surface/85 backdrop-blur-md px-3 py-1 text-xs font-bold text-content uppercase tracking-wider border border-line">
                                {{ c.sport_type }}
                            </span>

                            <div class="absolute right-4 bottom-4 left-4 flex items-center justify-between">
                                <h3 class="font-display text-2xl font-black text-white">
                                    {{ c.name }}
                                </h3>
                                <div class="rounded-full bg-black/50 p-2 text-white opacity-0 transition-opacity group-hover:opacity-100 backdrop-blur-sm">
                                    <ZoomIn class="size-4" />
                                </div>
                            </div>
                        </div>

                        <!-- Court Card Details -->
                        <div class="flex flex-1 flex-col justify-between p-6">
                            <div>
                                <p v-if="c.description" class="line-clamp-2 text-sm text-content-muted leading-relaxed">
                                    {{ c.description }}
                                </p>
                                <p v-else class="text-sm text-content-muted italic">
                                    A friendly, well-kept court with good netting and evening lighting.
                                </p>
                            </div>

                            <div class="mt-6 flex items-center justify-between border-t border-line pt-4">
                                <div>
                                    <span class="block text-[10px] font-bold text-content-muted uppercase tracking-wider">Rate</span>
                                    <span class="text-xl font-black text-brand">₱{{ c.base_price }}</span>
                                    <span class="text-xs text-content-muted">/{{ c.slot_duration_minutes }}m</span>
                                </div>

                                <button
                                    type="button"
                                    @click="openBookingForCourt(c)"
                                    class="inline-flex items-center gap-2 rounded-full bg-brand px-5 py-2.5 text-xs font-bold text-brand-foreground shadow-md shadow-brand/10 transition-all hover:bg-brand/95 cursor-pointer"
                                >
                                    <CalendarCheck class="size-4" />
                                    <span>Book Court</span>
                                </button>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        <!-- Court Images & Facility Gallery Section -->
        <section v-if="venueImages && venueImages.length > 0" class="border-t border-line bg-surface-elevated/20 py-16 sm:py-24">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 space-y-8">
                <div class="flex items-center justify-between">
                    <div>
                        <span class="text-xs font-extrabold tracking-widest text-brand uppercase">Facility Gallery</span>
                        <h2 class="mt-1 font-display text-3xl font-black tracking-tight text-content sm:text-4xl">
                            Court Areas & Photos
                        </h2>
                    </div>
                    <p class="text-xs font-bold text-content-muted">Click any photo to open full-screen preview</p>
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
                    <div
                        v-for="(imgUrl, idx) in venueImages"
                        :key="idx"
                        @click="openImageViewer(idx)"
                        class="group relative aspect-[4/3] overflow-hidden rounded-2xl border border-line bg-surface-inverse shadow cursor-pointer"
                    >
                        <img
                            :src="imgUrl"
                            :alt="`${venue.name} Court Photo ${idx + 1}`"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                        />
                        <div class="absolute inset-0 bg-surface-inverse/0 transition-colors group-hover:bg-surface-inverse/40 flex items-center justify-center">
                            <div class="rounded-full bg-black/60 p-3 text-white opacity-0 transition-opacity group-hover:opacity-100 backdrop-blur-sm shadow-lg">
                                <ZoomIn class="size-5" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Fullscreen Venue Image Preview Modal -->
        <VenueImageViewer
            :is-open="isImageViewerOpen"
            :images="venueImages"
            :initial-index="previewImageIndex"
            :title="venue.name"
            @close="isImageViewerOpen = false"
        />

        <!-- Booking Modal Window -->
        <BookingModal
            :is-open="isBookingModalOpen"
            :venue="venue"
            :court="selectedCourtForBooking"
            :venues="venues"
            @close="isBookingModalOpen = false"
        />
    </div>
</template>
