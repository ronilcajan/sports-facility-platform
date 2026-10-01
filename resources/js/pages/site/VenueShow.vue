<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
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
import VenueAvailabilitySchedule from '@/components/site/VenueAvailabilitySchedule.vue';
import type { CatalogVenue } from '@/components/site/SiteVenueCard.vue';
import type { PublicCourt } from '@/types';
import { formatDuration } from '@/utils/timeSlots';
import { show as showCourt } from '@/routes/site/courts';

const props = defineProps<{
    venue: CatalogVenue & { images?: string[] };
    venues: CatalogVenue[];
}>();

const isBookingModalOpen = ref(false);
const selectedCourtForBooking = ref<PublicCourt | null>(null);
const selectedBookingDate = ref<string | null>(null);
const selectedBookingSlot = ref<string | null>(null);

const isImageViewerOpen = ref(false);
const previewImageIndex = ref(0);

const courtImages = computed(() => {
    return props.venue.images || [];
});

function openBookingForCourt(
    court?: PublicCourt,
    date?: string,
    slot?: string,
) {
    selectedCourtForBooking.value = court || null;
    if (date) {
        selectedBookingDate.value = date;
    }
    selectedBookingSlot.value = slot || null;
    isBookingModalOpen.value = true;
}

function openCourt(court: PublicCourt): void {
    router.visit(showCourt.url(court.slug));
}

function handleScheduleDateChange(date: string) {
    selectedBookingDate.value = date;
}

function openImageViewer(index = 0) {
    previewImageIndex.value = index;
    isImageViewerOpen.value = true;
}
</script>

<template>
    <Head :title="`${venue.name} - Location Details`">
        <meta
            name="description"
            :content="
                venue.description ||
                `Explore courts and book a game at ${venue.name}.`
            "
        />
    </Head>

    <div>
        <!-- Hero Header -->
        <section class="relative overflow-hidden bg-surface-inverse text-white">
            <!-- Cover Background Photo with Overlay Gradient -->
            <div
                v-if="venue.cover_image_url || venue.image_url"
                class="absolute inset-0"
            >
                <img
                    :src="venue.image_url || venue.cover_image_url || ''"
                    :alt="venue.name"
                    class="h-full w-full object-cover opacity-35"
                />
                <div
                    class="absolute inset-0 bg-gradient-to-t from-surface-inverse via-surface-inverse/80 to-transparent"
                />
            </div>

            <div
                class="relative mx-auto max-w-7xl px-4 py-12 sm:px-6 sm:py-16 lg:px-8 lg:py-24"
            >
                <div
                    class="mb-6 flex flex-wrap items-center justify-between gap-3 sm:mb-8"
                >
                    <Link
                        href="/courts"
                        class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-bold text-white backdrop-blur-md transition-colors hover:bg-white/20"
                    >
                        <ChevronLeft class="size-4" />
                        <span>Back to All Locations</span>
                    </Link>

                    <!-- Preview Venue Image Button -->
                    <button
                        v-if="courtImages.length > 0"
                        type="button"
                        @click="openImageViewer(0)"
                        class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-white/20 bg-white/10 px-4 py-1.5 text-xs font-bold text-white backdrop-blur-md transition-all hover:scale-105 hover:bg-white/20"
                    >
                        <ZoomIn class="size-4 text-brand" />
                        <span>Preview Location Photo</span>
                    </button>
                </div>

                <div class="grid gap-8 lg:grid-cols-12 lg:items-center">
                    <div class="space-y-6 lg:col-span-8">
                        <div class="flex flex-wrap items-center gap-3">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full bg-brand px-3.5 py-1 text-xs font-bold text-brand-foreground shadow"
                            >
                                <Dumbbell class="size-3.5" />
                                <span
                                    >{{ venue.courts_count }}
                                    {{
                                        venue.courts_count === 1
                                            ? 'Court'
                                            : 'Courts'
                                    }}
                                    Available</span
                                >
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border border-emerald-500/30 bg-emerald-500/20 px-3.5 py-1 text-xs font-bold text-emerald-400"
                            >
                                <span
                                    class="size-2 animate-ping rounded-full bg-emerald-400"
                                />
                                <span>Active Facility</span>
                            </span>
                        </div>

                        <h1
                            class="font-display text-3xl font-black tracking-tight sm:text-4xl lg:text-5xl xl:text-6xl"
                        >
                            {{ venue.name }}
                        </h1>

                        <p
                            v-if="venue.description"
                            class="max-w-3xl text-lg leading-relaxed text-slate-300"
                        >
                            {{ venue.description }}
                        </p>

                        <!-- Contact & Location Pill Bar -->
                        <div
                            class="flex flex-wrap items-center gap-4 border-t border-white/10 pt-5 text-sm text-slate-300 sm:gap-6 sm:pt-6"
                        >
                            <div
                                v-if="venue.address"
                                class="flex items-center gap-2"
                            >
                                <MapPin class="size-4 shrink-0 text-brand" />
                                <span>{{ venue.address }}</span>
                            </div>
                            <div
                                v-if="venue.phone"
                                class="flex items-center gap-2"
                            >
                                <Phone class="size-4 shrink-0 text-brand" />
                                <span>{{ venue.phone }}</span>
                            </div>
                            <div
                                v-if="venue.email"
                                class="flex items-center gap-2"
                            >
                                <Mail class="size-4 shrink-0 text-brand" />
                                <span>{{ venue.email }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hero Quick Action Box -->
                    <div class="lg:col-span-4">
                        <div
                            class="space-y-4 rounded-2xl border border-white/15 bg-white/10 p-6 shadow-2xl backdrop-blur-xl"
                        >
                            <div
                                class="flex items-center justify-between border-b border-white/10 pb-4"
                            >
                                <div>
                                    <span
                                        class="text-xs font-bold tracking-wider text-slate-300 uppercase"
                                        >Operating Status</span
                                    >
                                    <h4 class="text-lg font-black text-white">
                                        Open Daily
                                    </h4>
                                </div>
                                <span
                                    class="rounded-full bg-brand/20 p-2.5 text-brand"
                                >
                                    <Clock class="size-6" />
                                </span>
                            </div>

                            <p class="text-xs leading-relaxed text-slate-300">
                                Reserve your preferred court at
                                {{ venue.name }} with instant real-time
                                availability confirmation.
                            </p>

                            <button
                                type="button"
                                @click="openBookingForCourt()"
                                class="flex w-full cursor-pointer items-center justify-center gap-2 rounded-md bg-brand px-6 py-3.5 text-sm font-black text-brand-foreground shadow-lg shadow-brand/25 transition-all duration-300 hover:-translate-y-0.5 hover:bg-brand/95"
                            >
                                <CalendarCheck class="size-5" />
                                <span>Book Court at this Location</span>
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
                            <h5
                                class="text-xs font-black tracking-wider text-content uppercase"
                            >
                                Great Courts
                            </h5>
                            <p class="text-[11px] text-content-muted">
                                Well-kept &amp; welcoming
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-brand/10 p-2.5 text-brand">
                            <Sparkles class="size-5" />
                        </div>
                        <div>
                            <h5
                                class="text-xs font-black tracking-wider text-content uppercase"
                            >
                                LED Floodlighting
                            </h5>
                            <p class="text-[11px] text-content-muted">
                                Optimal night play
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-brand/10 p-2.5 text-brand">
                            <Clock class="size-5" />
                        </div>
                        <div>
                            <h5
                                class="text-xs font-black tracking-wider text-content uppercase"
                            >
                                Hourly Slots
                            </h5>
                            <p class="text-[11px] text-content-muted">
                                Flexible booking times
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="rounded-xl bg-brand/10 p-2.5 text-brand">
                            <CheckCircle class="size-5" />
                        </div>
                        <div>
                            <h5
                                class="text-xs font-black tracking-wider text-content uppercase"
                            >
                                Lounge & Amenities
                            </h5>
                            <p class="text-[11px] text-content-muted">
                                Clean equipment & gear
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Availability Schedule Section -->
        <VenueAvailabilitySchedule
            :venue="venue"
            @book-court="openBookingForCourt"
            @date-selected="handleScheduleDateChange"
        />

        <!-- Courts Listing Section under this Venue -->
        <section class="py-16 sm:py-24">
            <div class="mx-auto max-w-7xl space-y-12 px-4 sm:px-6 lg:px-8">
                <div>
                    <span
                        class="text-xs font-extrabold tracking-widest text-brand uppercase"
                        >Available Courts</span
                    >
                    <h2
                        class="mt-1 font-display text-3xl font-black tracking-tight text-content sm:text-4xl"
                    >
                        Courts at {{ venue.name }}
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm text-content-muted">
                        Choose your preferred court below to check schedule
                        availability and complete your instant booking.
                    </p>
                </div>

                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">
                    <article
                        v-for="c in venue.courts"
                        :key="c.id"
                        class="group flex cursor-pointer flex-col overflow-hidden rounded-[var(--site-radius,1.25rem)] border border-line bg-surface-elevated shadow-md hover:border-brand/50 hover:shadow-2xl"
                        role="link"
                        tabindex="0"
                        @click="openCourt(c)"
                        @keydown.enter="openCourt(c)"
                        @keydown.space.prevent="openCourt(c)"
                    >
                        <!-- Court Cover Image -->
                        <div
                            class="relative aspect-[16/10] overflow-hidden bg-surface-inverse"
                        >
                            <img
                                :src="
                                    c.primary_image_url ||
                                    '/images/court_pickleball.png'
                                "
                                :alt="c.name"
                                class="h-full w-full object-cover"
                            />
                            <div
                                class="absolute inset-0 bg-gradient-to-t from-surface-inverse/80 via-transparent to-transparent"
                            />

                            <span
                                class="absolute top-4 left-4 rounded-full border border-line bg-surface/85 px-3 py-1 text-xs font-bold tracking-wider text-content uppercase backdrop-blur-md"
                            >
                                {{ c.sport_type }}
                            </span>

                            <div
                                class="absolute right-4 bottom-4 left-4 flex items-center justify-between"
                            >
                                <h3
                                    class="font-display text-2xl font-black text-white"
                                >
                                    {{ c.name }}
                                </h3>
                            </div>
                        </div>

                        <!-- Court Card Details -->
                        <div class="flex flex-1 flex-col justify-between p-6">
                            <div>
                                <p
                                    v-if="c.description"
                                    class="line-clamp-2 text-sm leading-relaxed text-content-muted"
                                >
                                    {{ c.description }}
                                </p>
                                <p
                                    v-else
                                    class="text-sm text-content-muted italic"
                                >
                                    A friendly, well-kept court with good
                                    netting and evening lighting.
                                </p>
                            </div>

                            <div
                                class="mt-6 flex items-center justify-between border-t border-line pt-4"
                            >
                                <div>
                                    <span class="text-xl font-black text-brand"
                                        >₱{{ c.base_price }}</span
                                    >
                                    <span class="text-xs text-content-muted"
                                        >/{{
                                            formatDuration(
                                                c.slot_duration_minutes,
                                            )
                                        }}</span
                                    >
                                </div>

                                <button
                                    type="button"
                                    @click.stop="openBookingForCourt(c)"
                                    class="inline-flex cursor-pointer items-center gap-2 rounded-md bg-brand px-4 py-2.5 text-xs font-bold text-brand-foreground shadow-md shadow-brand/10 transition-all hover:bg-brand/95"
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
        <section
            v-if="courtImages && courtImages.length > 0"
            class="border-t border-line bg-surface-elevated/20 py-16 sm:py-24"
        >
            <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div>
                        <span
                            class="text-xs font-extrabold tracking-widest text-brand uppercase"
                            >Facility Gallery</span
                        >
                        <h2
                            class="mt-1 font-display text-2xl font-black tracking-tight text-content sm:text-3xl lg:text-4xl"
                        >
                            Court Areas &amp; Photos
                        </h2>
                    </div>
                    <p class="text-xs font-bold text-content-muted">
                        Click any photo to open full-screen preview
                    </p>
                </div>

                <div
                    class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4"
                >
                    <div
                        v-for="(imgUrl, idx) in courtImages"
                        :key="idx"
                        @click="openImageViewer(idx)"
                        class="group relative aspect-[4/3] cursor-pointer overflow-hidden rounded-2xl border border-line bg-surface-inverse shadow"
                    >
                        <img
                            :src="imgUrl"
                            :alt="`${venue.name} Court Photo ${idx + 1}`"
                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                        />
                        <div
                            class="absolute inset-0 flex items-center justify-center bg-surface-inverse/0 transition-colors group-hover:bg-surface-inverse/40"
                        >
                            <div
                                class="rounded-full bg-black/60 p-3 text-white opacity-0 shadow-lg backdrop-blur-sm transition-opacity group-hover:opacity-100"
                            >
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
            :images="courtImages"
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
            :initial-date="selectedBookingDate"
            :initial-slots="selectedBookingSlot ? [selectedBookingSlot] : []"
            @close="isBookingModalOpen = false"
        />
    </div>
</template>
