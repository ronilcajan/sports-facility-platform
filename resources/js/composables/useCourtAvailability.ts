import { useHttp } from '@inertiajs/vue3';
import type { Ref } from 'vue';
import { ref } from 'vue';
import { availability } from '@/routes/site/bookings';

/** Booked/blacked-out slot times, keyed by court id. */
export type BookedSlotsByCourt = Record<string, string[]>;

/** Customer names keyed by court id and slot time. */
export type BookedSlotCustomersByCourt = Record<string, Record<string, string>>;

export type FetchAvailabilityParams = {
    date: string;
    courtId?: number | string | null;
    excludeBookingId?: number | string | null;
};

export type UseCourtAvailabilityReturn = {
    bookedSlotsByCourt: Ref<BookedSlotsByCourt>;
    bookedSlotCustomersByCourt: Ref<BookedSlotCustomersByCourt>;
    isLoading: Ref<boolean>;
    fetchAvailability: (params: FetchAvailabilityParams) => Promise<void>;
    slotsForCourt: (courtId: number | string) => string[];
    customerForSlot: (courtId: number | string, slot: string) => string | null;
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
    const bookedSlotCustomersByCourt = ref<BookedSlotCustomersByCourt>({});
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
            const {
                booked_slots: bookedSlots,
                booked_slot_customers: bookedCustomers,
            } = (await http.submit(
                availability({ query }),
            )) as {
                booked_slots: BookedSlotsByCourt;
                booked_slot_customers?: BookedSlotCustomersByCourt;
            };

            bookedSlotsByCourt.value = bookedSlots ?? {};
            bookedSlotCustomersByCourt.value = bookedCustomers ?? {};
        } catch {
            // Availability is an enhancement over the statically rendered
            // slots; leave the last known map in place on failure.
        } finally {
            isLoading.value = false;
        }
    };

    const slotsForCourt = (courtId: number | string): string[] =>
        bookedSlotsByCourt.value[String(courtId)] ?? [];

    const customerForSlot = (
        courtId: number | string,
        slot: string,
    ): string | null => {
        const courtMap = bookedSlotCustomersByCourt.value[String(courtId)];
        if (!courtMap) {
            return null;
        }
        return courtMap[slot] ?? courtMap[slot.trim()] ?? null;
    };

    return {
        bookedSlotsByCourt,
        bookedSlotCustomersByCourt,
        isLoading,
        fetchAvailability,
        slotsForCourt,
        customerForSlot,
    };
};
