import { useHttp } from '@inertiajs/vue3';
import type { Ref } from 'vue';
import { ref } from 'vue';
import { availability } from '@/routes/site/bookings';

/** Booked/blacked-out slot times, keyed by court id. */
export type BookedSlotsByCourt = Record<string, string[]>;

export type FetchAvailabilityParams = {
    date: string;
    courtId?: number | string | null;
    excludeBookingId?: number | string | null;
};

export type UseCourtAvailabilityReturn = {
    bookedSlotsByCourt: Ref<BookedSlotsByCourt>;
    isLoading: Ref<boolean>;
    fetchAvailability: (params: FetchAvailabilityParams) => Promise<void>;
    slotsForCourt: (courtId: number | string) => string[];
};

/**
 * Reads real-time booked slots and staff blackouts for a given date.
 *
 * State is per-instance rather than module-level, since each consuming
 * component tracks its own selected date and court.
 */
export const useCourtAvailability = (): UseCourtAvailabilityReturn => {
    const http = useHttp();

    const bookedSlotsByCourt = ref<BookedSlotsByCourt>({});
    const isLoading = ref<boolean>(false);

    const fetchAvailability = async ({
        date,
        courtId = null,
        excludeBookingId = null,
    }: FetchAvailabilityParams): Promise<void> => {
        if (!date) {
            return;
        }

        const query: Record<string, string> = { date };

        if (courtId !== null && courtId !== undefined && courtId !== '') {
            query.court_id = String(courtId);
        }

        if (excludeBookingId !== null && excludeBookingId !== undefined) {
            query.exclude_booking_id = String(excludeBookingId);
        }

        isLoading.value = true;

        try {
            const { booked_slots: bookedSlots } = (await http.submit(
                availability({ query }),
            )) as { booked_slots: BookedSlotsByCourt };

            bookedSlotsByCourt.value = bookedSlots ?? {};
        } catch {
            // Availability is an enhancement over the statically rendered
            // slots; leave the last known map in place on failure.
        } finally {
            isLoading.value = false;
        }
    };

    const slotsForCourt = (courtId: number | string): string[] =>
        bookedSlotsByCourt.value[String(courtId)] ?? [];

    return {
        bookedSlotsByCourt,
        isLoading,
        fetchAvailability,
        slotsForCourt,
    };
};
