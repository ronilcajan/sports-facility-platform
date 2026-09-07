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

/**
 * Format a slot string (e.g., "07:00 AM", "7:00 AM") into a 1-hour time range
 * or specified duration range (e.g., "7:00 AM – 8:00 AM").
 */
export function formatSlotRange(slot: string, durationMinutes: number = 60): string {
    const match = slot.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return slot;

    let h = parseInt(match[1], 10);
    const m = parseInt(match[2], 10);
    const period = match[3].toUpperCase();

    let start24 = h;
    if (period === 'PM' && h !== 12) start24 += 12;
    if (period === 'AM' && h === 12) start24 = 0;

    const safeDuration = durationMinutes > 0 ? durationMinutes : 60;
    const startTotalMinutes = start24 * 60 + m;
    const endTotalMinutes = (startTotalMinutes + safeDuration) % (24 * 60);

    const endH24 = Math.floor(endTotalMinutes / 60);
    const endM = endTotalMinutes % 60;

    const format12h = (hour24: number, minute: number): string => {
        const p = hour24 >= 12 && hour24 < 24 ? 'PM' : 'AM';
        let h12 = hour24 % 12;
        if (h12 === 0) h12 = 12;
        const mm = String(minute).padStart(2, '0');
        return `${h12}:${mm} ${p}`;
    };

    return `${format12h(start24, m)} \u2013 ${format12h(endH24, endM)}`;
}

/**
 * Parse a standard 12-hour clock string (e.g. "07:00 AM", "7:00 AM", "12:00 PM")
 * into regular minutes from midnight (0 to 1439).
 */
export function standardClockMinutes(timeStr: string): number | null {
    const match = timeStr.trim().match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (!match) return null;
    let h = parseInt(match[1], 10);
    const m = parseInt(match[2], 10);
    const period = match[3].toUpperCase();

    if (period === 'PM' && h !== 12) h += 12;
    if (period === 'AM' && h === 12) h = 0;

    return h * 60 + m;
}

/**
 * Parse a slot duration in minutes. Handles both single start times (e.g. "07:00 AM")
 * and range strings (e.g. "7:00 AM \u2013 8:00 AM", "9:00 AM \u2013 11:00 AM", "07:00 AM - 08:00 AM").
 */
export function parseSlotDurationMinutes(slot: string, fallbackMinutes: number = 60): number {
    if (!slot || typeof slot !== 'string') return 0;

    const parts = slot.split(/\s*(?:–|-|\bto\b)\s*/i);
    if (parts.length === 2) {
        const startMin = standardClockMinutes(parts[0]);
        const endMin = standardClockMinutes(parts[1]);
        if (startMin !== null && endMin !== null) {
            let diff = endMin - startMin;
            if (diff < 0) {
                diff += 24 * 60;
            }
            if (diff > 0) {
                return diff;
            }
        }
    }

    return fallbackMinutes > 0 ? fallbackMinutes : 60;
}

/**
 * Calculate total hours for a booking from its time_slots array.
 * Examples:
 * - ["07:00 AM"] -> 1
 * - ["7:00 AM \u2013 8:00 AM"] -> 1
 * - ["9:00 AM \u2013 11:00 AM"] -> 2
 * - ["09:00 AM", "10:00 AM"] -> 2
 */
export function calculateBookingHours(timeSlots?: string[] | null, fallbackSlotMinutes: number = 60): number {
    if (!timeSlots || !Array.isArray(timeSlots) || timeSlots.length === 0) {
        return 0;
    }

    let totalMinutes = 0;
    for (const slot of timeSlots) {
        totalMinutes += parseSlotDurationMinutes(slot, fallbackSlotMinutes);
    }

    return totalMinutes / 60;
}

/**
 * Formats hours into human-readable label (e.g. "1 hour", "3 hours", "1.5 hours", "0 hours").
 */
export function formatHours(hours: number): string {
    const formatted = Number.isInteger(hours)
        ? String(hours)
        : hours.toFixed(1).replace(/\.0$/, '');
    return `${formatted} ${hours === 1 ? 'hour' : 'hours'}`;
}
