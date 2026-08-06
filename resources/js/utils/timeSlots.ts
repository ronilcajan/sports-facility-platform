export const DEFAULT_TIME_SLOTS: string[] = [
    '07:00 AM',
    '08:00 AM',
    '09:00 AM',
    '10:00 AM',
    '11:00 AM',
    '12:00 PM',
    '01:00 PM',
    '02:00 PM',
    '03:00 PM',
    '04:00 PM',
    '05:00 PM',
    '06:00 PM',
    '07:00 PM',
    '08:00 PM',
    '09:00 PM',
    '10:00 PM',
    '11:00 PM',
    '12:00 AM',
    '01:00 AM',
    '02:00 AM',
];

/**
 * Convert 12h time string (e.g., "07:00 AM", "02:30 PM", "12:00 AM") to minutes for chronological sorting.
 * Operating hours are ordered starting from 05:00 AM. Late night slots (12:00 AM to 04:59 AM)
 * are placed after 11:00 PM.
 */
export function timeToMinutes(slot: string): number {
    const match = slot.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return 9999;
    let h = parseInt(match[1], 10);
    const m = parseInt(match[2], 10);
    const period = match[3].toUpperCase();

    if (period === 'PM' && h !== 12) h += 12;
    if (period === 'AM' && h === 12) h = 0;

    let totalMinutes = h * 60 + m;
    // Slots between 12:00 AM and 04:59 AM belong to late night shift after 11:00 PM
    if (h < 5) {
        totalMinutes += 24 * 60;
    }
    return totalMinutes;
}

/**
 * Format hours & minutes into standard "hh:mm AM/PM" format.
 */
export function formatTimeSlot(hours: number, minutes: number = 0): string {
    const period = hours >= 12 && hours < 24 ? 'PM' : 'AM';
    let h12 = hours % 12;
    if (h12 === 0) h12 = 12;
    const hh = String(h12).padStart(2, '0');
    const mm = String(minutes).padStart(2, '0');
    return `${hh}:${mm} ${period}`;
}

/**
 * Sort a list of time slot strings chronologically.
 */
export function sortTimeSlots(slots: string[]): string[] {
    const unique = Array.from(new Set(slots));
    return unique.sort((a, b) => timeToMinutes(a) - timeToMinutes(b));
}

/**
 * Get all active time slots for a court or venue by merging DEFAULT_TIME_SLOTS
 * with any custom slots defined in slot_prices or custom_slots.
 */
export function getMergedTimeSlots(customSlotsFromPrices?: Record<string, any> | string[] | null): string[] {
    let extra: string[] = [];
    if (Array.isArray(customSlotsFromPrices)) {
        extra = customSlotsFromPrices;
    } else if (customSlotsFromPrices && typeof customSlotsFromPrices === 'object') {
        extra = Object.keys(customSlotsFromPrices);
    }
    const combined = [...DEFAULT_TIME_SLOTS, ...extra];
    return sortTimeSlots(combined);
}

/**
 * Checks if a slot string is one of the default system slots.
 */
export function isDefaultTimeSlot(slot: string): boolean {
    return DEFAULT_TIME_SLOTS.includes(slot.trim());
}
