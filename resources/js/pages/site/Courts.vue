<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import PageHero from '@/components/site/PageHero.vue';
import SiteCourtCard from '@/components/site/SiteCourtCard.vue';
import BookingModal from '@/components/site/BookingModal.vue';
import type { PublicCourt } from '@/types';

interface PublicVenue {
    id: number;
    name: string;
    slug: string;
    description?: string | null;
    address?: string | null;
    courts_count?: number;
}

const props = defineProps<{
    courts: PublicCourt[];
    venues?: PublicVenue[];
    selectedVenueId?: number | null;
}>();

const activeCourt = ref<PublicCourt | null>(null);
const isBookingOpen = ref(false);
const activeVenueFilter = ref<number | null>(props.selectedVenueId || null);

const filteredCourts = computed(() => {
    if (!activeVenueFilter.value) {
        return props.courts;
    }
    return props.courts.filter(c => c.venue?.id === activeVenueFilter.value);
});

function selectVenue(venueId: number | null) {
    activeVenueFilter.value = venueId;
}

function handleBook(court: PublicCourt) {
    activeCourt.value = court;
    isBookingOpen.value = true;
}
</script>

<template>
    <Head title="Courts & Venues" />

    <PageHero
        eyebrow="Facilities & Courts"
        title="Find & Book Courts by Venue"
        lede="Explore sports facility venues and book courts in real-time."
    />

    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20">
        <!-- Interactive Venue Selector Tabs -->
        <div v-if="venues && venues.length > 0" class="mb-12">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-content">Select Facility Venue</h2>
                    <p class="text-xs text-content-muted">Choose a venue to view its assigned courts.</p>
                </div>
            </div>

            <!-- Venue Pills Navigation -->
            <div class="flex flex-wrap gap-2.5">
                <button
                    type="button"
                    @click="selectVenue(null)"
                    class="rounded-full px-5 py-2.5 text-xs font-bold transition-all duration-200 cursor-pointer"
                    :class="[
                        activeVenueFilter === null
                            ? 'bg-brand text-brand-foreground shadow-md shadow-brand/20 scale-102'
                            : 'bg-surface-elevated text-content-muted border border-line hover:border-brand/40 hover:text-content'
                    ]"
                >
                    All Venues ({{ courts.length }})
                </button>

                <button
                    v-for="v in venues"
                    :key="v.id"
                    type="button"
                    @click="selectVenue(v.id)"
                    class="rounded-full px-5 py-2.5 text-xs font-bold transition-all duration-200 cursor-pointer flex items-center gap-2"
                    :class="[
                        activeVenueFilter === v.id
                            ? 'bg-brand text-brand-foreground shadow-md shadow-brand/20 scale-102'
                            : 'bg-surface-elevated text-content-muted border border-line hover:border-brand/40 hover:text-content'
                    ]"
                >
                    <span>{{ v.name }}</span>
                    <span class="rounded-full bg-black/10 dark:bg-white/10 px-2 py-0.5 text-[10px] font-extrabold">
                        {{ v.courts_count }} courts
                    </span>
                </button>
            </div>
        </div>

        <!-- Courts Grid -->
        <div
            v-if="filteredCourts.length"
            class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3"
        >
            <SiteCourtCard
                v-for="court in filteredCourts"
                :key="court.id"
                :court="court"
                @book="handleBook"
            />
        </div>
        <div
            v-else
            class="rounded-2xl border border-dashed border-line p-16 text-center text-content-muted"
        >
            No courts found for the selected venue. Please select another venue or check back soon.
        </div>
    </div>

    <!-- Booking Modal Component overlay -->
    <BookingModal
        :court="activeCourt"
        :is-open="isBookingOpen"
        @close="isBookingOpen = false"
    />
</template>
