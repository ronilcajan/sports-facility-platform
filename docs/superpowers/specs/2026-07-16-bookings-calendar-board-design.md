# Bookings Calendar Board — Design

Date: 2026-07-16
Branch: feature/theme-manager

## Goal

Replace the plain bookings table with a 5-day calendar board (default view),
keeping the table as a switchable "List" view. Works for super-admins, venue
admins, and staff — each already scoped by the existing `Booking::visibleTo`
seam. Must be mobile-friendly.

## Views

The bookings page is a shell with a **Calendar | List** toggle, persisted via
`?view=calendar|list` (calendar default). List = the current paginated table,
unchanged. Filters: **Court**, **Status**, and **Venue** (super-admin only).
Search stays on List only.

## The board — `resources/js/components/admin/BookingsCalendar.vue` (shared)

Reused by both `admin/bookings/Index.vue` and `staff/bookings/Index.vue`.

Props: `days`, `courts`, `venues` (optional, super-admin), `filters`, `window`
(`{ start, prev, next, today, isToday }`), `basePath` (`/admin/bookings` or
`/staff/bookings`), `canDelete` (admin/super true, staff false),
`showVenueFilter` (super-admin only).

- **5-day rolling window** from `start` (default today). Header day strip
  `Mon 16 · … · Fri 20` with **‹ Prev 5 · Today · Next 5 ›** navigation via
  `router.get(basePath, { start, ... })`.
- **Desktop (lg+):** 5 day-columns. Within each day, bookings **grouped by
  court** (court sub-header), sorted by earliest time slot. Card = customer
  name · time · ₱amount · status pill (color-coded).
- **Mobile (< lg):** day strip becomes tappable **day chips** (horizontal snap
  scroll); the selected day renders full-width as a single column below.
- **Card click → modal**: details + inline actions — approve / reject / cancel /
  complete, delete (when `canDelete`), and a link to the full show page.
  Actions post to `${basePath}/${id}/status` (PATCH) and `${basePath}/${id}`
  (DELETE). Reuses each controller's existing `updateStatus` / `destroy`.

Grouping-by-court and time-sort happen client-side from the card's court data.

## Backend

### `App\Support\BookingCalendar` (shared builder)

`build(Builder $bookings, CarbonImmutable $start): array` — returns 5 day
objects `{ date, weekday, dayNum, isToday, bookings: [{id,name,time_slots,
status,total_price,court:{id,name}}] }` for `[start, start+4]`. The `date`
column is a `Y-m-d` string, so `whereBetween` on strings is correct.

### Controllers

`AdminBookingController@index` and `StaffBookingController@index` both:
1. Start from their already-scoped `Booking::visibleTo($user)` query.
2. Apply Court / Status / (admin) Venue filters. **Default status:** when no
   status filter is set, `whereNotIn('status', ['rejected','cancelled'])` so the
   board shows only active bookings; selecting a status overrides this.
3. Branch on `view`:
   - `calendar` (default): `start` = `?start` or today (CarbonImmutable);
     `days = BookingCalendar::build(...)`; window anchors prev = start−5,
     next = start+5, today. Return `days`, `window`, `courts`, `venues`
     (super only), `filters`, plus the props above.
   - `list`: existing paginated table payload (unchanged).

Admin passes `basePath='/admin/bookings'`, `canDelete=true`,
`showVenueFilter` = super-admin. Staff passes `basePath='/staff/bookings'`,
`canDelete=false`, no venue filter.

## Testing

- Calendar view returns exactly 5 days from `start` (default today).
- `start` param shifts the window; prev/next anchors are start∓5.
- Venue admin board excludes other venues; staff board only assigned courts.
- Court and Status filters narrow the board; default hides rejected/cancelled.
- List view still paginates.
