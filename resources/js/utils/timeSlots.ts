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
 * Format a booking duration for customer-facing labels.
 */
export function formatDuration(minutes: number | null | undefined): string {
    const duration = Number(minutes) || 0;

    if (duration > 0 && duration % 60 === 0) {
        const hours = duration / 60;
        return `${hours} ${hours === 1 ? 'hr' : 'hrs'}`;
    }

    return `${duration} min`;
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
export function getMergedTimeSlots(
    customSlotsFromPrices?: Record<string, any> | string[] | null,
): string[] {
    let extra: string[] = [];
    if (Array.isArray(customSlotsFromPrices)) {
        extra = customSlotsFromPrices;
    } else if (
        customSlotsFromPrices &&
        typeof customSlotsFromPrices === 'object'
    ) {
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
 * Render a slot as the full span it actually occupies, e.g. "07:00-07:59 AM".
 *
 * A booking covers its whole slot, so a chip labelled only "07:00 AM" reads as an
 * instant rather than an hour. People selecting 07:00 through 10:00 assume three
 * hours when the system books four (07:00-10:59), so the end time is spelled out.
 * The period is printed once when both ends share it, and twice when they differ
 * (e.g. a 90 minute slot running "11:00 AM-12:29 PM").
 */
export function formatSlotRange(
    slot: string,
    durationMinutes: number = 60,
): string {
    const startMinutes = timeToMinutes(slot);
    if (startMinutes === 9999) {
        return slot;
    }

    const duration = durationMinutes > 0 ? durationMinutes : 60;
    const endMinutes = startMinutes + duration - 1;

    const start = formatTimeSlot(
        Math.floor((startMinutes % 1440) / 60),
        startMinutes % 60,
    );
    const end = formatTimeSlot(
        Math.floor((endMinutes % 1440) / 60),
        endMinutes % 60,
    );

    return start.slice(-2) === end.slice(-2)
        ? `${start.slice(0, 5)}-${end.slice(0, 5)} ${end.slice(-2)}`
        : `${start}-${end}`;
}

/**
 * Parse the start time of a slot into minutes from midnight (0 to 1439).
 * Handles "07:00 AM", "07:00-07:59 AM", "07:00 AM - 08:00 AM", "07:00", etc.
 */
export function getSlotStartMinutes(slot: string): number {
    const trimmed = (slot || '').trim();
    if (!trimmed) return 0;

    // Standard "HH:MM AM/PM"
    const standardMatch = trimmed.match(/^(\d{1,2}):(\d{2})\s*(AM|PM)$/i);
    if (standardMatch) {
        let h = parseInt(standardMatch[1], 10);
        const m = parseInt(standardMatch[2], 10);
        const period = standardMatch[3].toUpperCase();
        if (period === 'PM' && h !== 12) h += 12;
        if (period === 'AM' && h === 12) h = 0;
        let total = h * 60 + m;
        // Slots between 12:00 AM and 04:59 AM belong to late night shift after 11:00 PM
        if (h < 5) total += 24 * 60;
        return total;
    }

    // Range like "07:00-07:59 AM" or "07:00 AM - 08:00 AM"
    const rangeMatch = trimmed.match(/^(\d{1,2}):(\d{2})(?:\s*(AM|PM))?/i);
    if (rangeMatch) {
        let h = parseInt(rangeMatch[1], 10);
        const m = parseInt(rangeMatch[2], 10);
        let period = rangeMatch[3]?.toUpperCase();
        if (!period) {
            const endPeriod = trimmed.match(/\b(AM|PM)\b/i);
            period = endPeriod ? endPeriod[1].toUpperCase() : 'AM';
        }
        if (period === 'PM' && h !== 12) h += 12;
        if (period === 'AM' && h === 12) h = 0;
        let total = h * 60 + m;
        // Slots between 12:00 AM and 04:59 AM belong to late night shift after 11:00 PM
        if (h < 5) total += 24 * 60;
        return total;
    }

    // 24h format like "14:00"
    const h24Match = trimmed.match(/^(\d{1,2}):(\d{2})/);
    if (h24Match) {
        let h = parseInt(h24Match[1], 10);
        const m = parseInt(h24Match[2], 10);
        let total = h * 60 + m;
        if (h < 5) total += 24 * 60;
        return total;
    }

    return 0;
}

/**
 * Check if a given date and slot have already passed based on current local date and time.
 * Accurately accounts for late-night operating hours (12:00 AM to 04:59 AM) following 11:00 PM.
 * @param dateStr Date string in "YYYY-MM-DD" format
 * @param slot Slot string (e.g. "03:00 PM", "03:00-03:59 PM", "02:00-02:59 AM")
 * @param now Optional Date reference (defaults to new Date())
 */
export function isSlotPassed(dateStr: string, slot: string, now: Date = new Date()): boolean {
    if (!dateStr || !slot) return false;

    // Operating day starts at 05:00 AM; hours from 00:00 to 04:59 belong to the previous calendar date's operating day
    const opDate = new Date(now);
    const nowHour = opDate.getHours();
    let currentOperatingMinutes: number;

    if (nowHour < 5) {
        opDate.setDate(opDate.getDate() - 1);
        currentOperatingMinutes = (nowHour + 24) * 60 + opDate.getMinutes();
    } else {
        currentOperatingMinutes = nowHour * 60 + opDate.getMinutes();
    }

    const curYear = opDate.getFullYear();
    const curMonth = String(opDate.getMonth() + 1).padStart(2, '0');
    const curDay = String(opDate.getDate()).padStart(2, '0');
    const operatingDateStr = `${curYear}-${curMonth}-${curDay}`;

    // If date is before current operating date, it has already passed
    if (dateStr < operatingDateStr) {
        return true;
    }

    // If date is after current operating date, it has not passed
    if (dateStr > operatingDateStr) {
        return false;
    }

    // Date is the current operating date: compare slot start minutes with current operating minutes
    const slotStart = getSlotStartMinutes(slot);

    return slotStart <= currentOperatingMinutes;
}

