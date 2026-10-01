<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PageHero from '@/components/site/PageHero.vue';
import SiteVenueCard, {
    type CatalogVenue,
} from '@/components/site/SiteVenueCard.vue';
import SiteCourtCard from '@/components/site/SiteCourtCard.vue';
import BookingModal from '@/components/site/BookingModal.vue';
import { MapPin, Phone, Mail, ArrowLeft, Building2 } from '@lucide/vue';
import type { PublicCourt } from '@/types';

const props = defineProps<{
    venues: CatalogVenue[];
    courts: PublicCourt[];
    selectedVenueId?: number | null;
}>();

const activeCourt = ref<PublicCourt | null>(null);
const activeBookingVenue = ref<CatalogVenue | null>(null);
const isBookingOpen = ref(false);
const selectedVenue = ref<CatalogVenue | null>(
    props.selectedVenueId
        ? props.venues.find((v) => v.id === props.selectedVenueId) || null
        : null,
);

const displayCourts = computed(() => {
    if (selectedVenue.value) {
        return selectedVenue.value.courts &&
            selectedVenue.value.courts.length > 0
            ? selectedVenue.value.courts
            : props.courts.filter(
                  (c) => c.venue?.id === selectedVenue.value?.id,
              );
    }
    return props.courts;
});

function handleViewCourts(venue: CatalogVenue) {
    selectedVenue.value = venue;
    window.scrollTo({ top: 350, behavior: 'smooth' });
}

function clearVenueSelection() {
    selectedVenue.value = null;
}

function handleBookVenue(venue: CatalogVenue) {
    activeBookingVenue.value = venue;
    // Booking from a venue card picks no court — the customer chooses one in the modal.
    activeCourt.value = null;
    isBookingOpen.value = true;
}

function handleBookCourt(court: PublicCourt) {
    activeCourt.value = court;
    activeBookingVenue.value =
        props.venues.find((v) => v.id === court.venue?.id) || null;
    isBookingOpen.value = true;
}
</script>

<template>
    <Head
        :title="
            selectedVenue
                ? `${selectedVenue.name} - Courts`
                : 'Sports Locations & Facilities'
        "
    />

    <PageHero
        :eyebrow="selectedVenue ? 'Selected Facility' : 'Sports Locations'"
        :title="
            selectedVenue
                ? selectedVenue.name
                : 'Explore Our Facility Locations'
        "
        :lede="
            selectedVenue
                ? selectedVenue.description ||
                  'View courts and real-time reservation availability for this location.'
                : 'Select a sports facility location below to view its courts or book instantly.'
        "
    />

    <div class="mx-auto max-w-6xl space-y-12 px-4 py-16 sm:px-6 sm:py-20">
        <!-- MODE 1: Venue Cards First Grid (Default) -->
        <template v-if="!selectedVenue">
            <div
                class="mb-2 flex flex-col justify-between gap-4 sm:flex-row sm:items-center"
            >
                <div>
                    <h2 class="text-2xl font-black tracking-tight text-content">
                        Available Locations
                    </h2>
                    <p class="text-xs text-content-muted">
                        Choose a facility location to book or view its available
                        courts.
                    </p>
                </div>
                <div class="text-xs font-semibold text-content-muted">
                    Showing {{ venues.length }}
                    {{ venues.length === 1 ? 'location' : 'locations' }}
                </div>
            </div>

            <div
                v-if="venues.length"
                class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3"
            >
                <SiteVenueCard
                    v-for="v in venues"
                    :key="v.id"
                    :venue="v"
                    @book-now="handleBookVenue"
                    @view-courts="handleViewCourts"
                />
            </div>

            <div
                v-else
                class="rounded-2xl border border-dashed border-line p-16 text-center text-content-muted"
            >
                No active locations available right now. Please check back soon.
            </div>
        </template>

        <!-- MODE 2: Venue Courts Showcase (When a Venue is Selected) -->
        <template v-else>
            <!-- Navigation Breadcrumb & Back Button -->
            <div
                class="flex flex-col gap-3 border-b border-line pb-5 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between sm:gap-4 sm:pb-6"
            >
                <button
                    type="button"
                    @click="clearVenueSelection"
                    class="inline-flex cursor-pointer items-center gap-2 rounded-full border border-line bg-surface-elevated px-4 py-2 text-xs font-bold text-content shadow-sm transition-all hover:border-brand hover:text-brand"
                >
                    <ArrowLeft class="size-4" />
                    <span>← Back to All Locations</span>
                </button>

                <div
                    class="flex items-center gap-2 text-xs font-semibold text-content-muted"
                >
                    <Building2 class="size-4 text-brand" />
                    <span class="font-bold text-content">{{
                        selectedVenue.name
                    }}</span>
                    <span
                        >({{ selectedVenue.courts_count }}
                        {{
                            selectedVenue.courts_count === 1
                                ? 'court'
                                : 'courts'
                        }})</span
                    >
                </div>
            </div>

            <!-- Venue Info Overview Banner -->
            <div
                class="flex flex-col justify-between gap-6 rounded-2xl border border-line bg-surface-elevated p-6 shadow-sm md:flex-row md:items-center"
            >
                <div class="space-y-2">
                    <span
                        class="inline-block rounded-full bg-brand/10 px-3 py-1 text-[11px] font-bold tracking-wider text-brand uppercase"
                    >
                        Active Facility Location
                    </span>
                    <h3 class="text-2xl font-black text-content">
                        {{ selectedVenue.name }}
                    </h3>
                    <p class="max-w-2xl text-sm text-content-muted">
                        {{ selectedVenue.description }}
                    </p>

                    <div
                        class="flex flex-wrap gap-4 pt-2 text-xs font-medium text-content-muted"
                    >
                        <div
                            v-if="selectedVenue.address"
                            class="flex items-center gap-1.5"
                        >
                            <MapPin class="size-4 text-brand" />
                            <span>{{ selectedVenue.address }}</span>
                        </div>
                        <div
                            v-if="selectedVenue.phone"
                            class="flex items-center gap-1.5"
                        >
                            <Phone class="size-4 text-brand" />
                            <span>{{ selectedVenue.phone }}</span>
                        </div>
                        <div
                            v-if="selectedVenue.email"
                            class="flex items-center gap-1.5"
                        >
                            <Mail class="size-4 text-brand" />
                            <span>{{ selectedVenue.email }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex shrink-0 flex-wrap items-center gap-3">
                    <button
                        type="button"
                        @click="handleBookVenue(selectedVenue)"
                        class="cursor-pointer rounded-md bg-brand px-4 py-2.5 text-xs font-bold text-brand-foreground shadow-md transition-all hover:bg-brand/95"
                    >
                        Book at {{ selectedVenue.name }}
                    </button>

                    <button
                        type="button"
                        @click="clearVenueSelection"
                        class="cursor-pointer rounded-full border border-line bg-surface px-5 py-2.5 text-xs font-bold text-content transition-colors hover:bg-surface-elevated"
                    >
                        Switch Location
                    </button>
                </div>
            </div>

            <!-- Courts Grid for Selected Venue -->
            <div class="space-y-6">
                <h3 class="text-xl font-bold tracking-tight text-content">
                    Courts at {{ selectedVenue.name }}
                </h3>

                <div
                    v-if="displayCourts.length"
                    class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <SiteCourtCard
                        v-for="court in displayCourts"
                        :key="court.id"
                        :court="court"
                        @book="handleBookCourt"
                    />
                </div>

                <div
                    v-else
                    class="rounded-2xl border border-dashed border-line p-12 text-center text-content-muted"
                >
                    No active courts currently available for
                    {{ selectedVenue.name }}.
                </div>
            </div>
        </template>
    </div>

    <!-- Booking Modal Component overlay -->
    <BookingModal
        :court="activeCourt"
        :venue="activeBookingVenue"
        :venues="venues"
        :is-open="isBookingOpen"
        @close="isBookingOpen = false"
    />
</template>
