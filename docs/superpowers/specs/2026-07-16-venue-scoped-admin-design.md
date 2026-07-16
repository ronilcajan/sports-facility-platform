# Venue-Scoped Admin — Design

Date: 2026-07-16
Branch: feature/theme-manager

## Goal

A **venue admin** is created by a super-admin and assigned to exactly one venue
(`users.venue_id`). Once scoped, the admin only sees and manages data for that
venue. Super-admins remain global. The sidebar, routes, and data queries all
reflect this scope.

## Roles → Sidebar

| Role | Platform group | Systems group |
|---|---|---|
| Super admin | Dashboard, Bookings, Venues, Courts, Customers | Users, Reports, Appearance |
| Venue admin | Dashboard, Bookings, Courts, Customers | Users, Reports, Setting |
| Staff | *(unchanged: assigned courts)* | — |
| Customer | Dashboard, Bookings | — |

- Venue admin **Systems › Users** → `admin.users.index`, scoped to the venue —
  the account-management view showing the venue's staff **and** customer logins.
  Admin can add staff here (auto-assigned to the admin's venue).
- Venue admin **Platform › Customers** → same `AdminUserController`, filtered to
  just the venue's customers (operational "who is booking" list).
- Super-admin **Systems › Users** → `admin.staff.index` (all staff, unchanged).
- Admin "Setting" edits the admin's own venue. Super-admin uses Appearance +
  the Venues resource instead (no Setting entry).

## Backend Scoping — single seam

`Court::scopeVisibleTo(Builder, User)` is the documented single source of truth
for court visibility. Narrow it:

- super-admin → all courts
- **admin → `where('venue_id', $user->venue_id)`** (new)
- staff → assigned courts (unchanged)
- admin with `venue_id === null` → no courts (safe default)

Order matters: `User::isAdmin()` returns true for super-admins too, so check
`isSuperAdmin()` first. `Booking::scopeVisibleTo` already delegates to court
visibility via `whereHas('court', visibleTo)`, so bookings scope automatically.

## Controllers

- **AdminDashboardController** — stats (courts, bookings, revenue, customers)
  computed from `visibleTo($user)` court/booking scopes; customer count =
  distinct users with a booking at the admin's venue.
- **CourtController@index** — `Court::visibleTo($user)`.
- **AdminBookingController@index** — `Booking::visibleTo($user)`.
- **AdminUserController@index** — serves both admin menu entries via an optional
  filter:
  - Platform › Customers → customers scoped to the admin's venue (users with a
    booking at the venue).
  - Systems › Users → all account logins scoped to the venue (staff + customers).
  Super-admin sees all users/customers unscoped.
- **AdminUserController@store** — when a venue admin creates a staff account,
  auto-assign `venue_id = admin's venue` (no venue picker). Super-admin picks
  the venue.
- **AdminStaffController** — unchanged; remains the super-admin "Users" screen.
- **AdminReportController** — scoped to the admin's venue.
- **SingleVenueController** (currently an empty stub) — `edit`/`update` on
  `$user->venue`, reusing the existing `admin/venues/Edit` page. Route
  `admin.settings.*`.
- **Customer bookings** — new `customer.bookings.index` route + page listing the
  signed-in customer's own bookings (upcoming + past) with cancel/manage
  actions, reusing the booking query the `/dashboard` closure already builds.

### Security

`show`/`update`/`destroy` for court / booking / user (and the Setting venue)
must verify the bound model belongs to the admin's venue, else `abort(403)`.
This prevents an admin editing another venue's records by guessing IDs.
Super-admins bypass the check.

## Routes (`routes/admin.php`)

- **admin | super-admin** (shared, data-scoped): dashboard, courts (+ images,
  staff-assign), bookings, users (customers), reports, staff, settings.
- **super-admin only**: venues resource, appearance.

Reports and Staff move out of the super-admin-only block into the shared block
now that they are venue-scoped.

## Frontend

- `AppSidebar.vue` — split the `can_manage_all_courts` branch into super-admin
  vs venue admin (`is_admin && !is_super_admin`), producing the two menu shapes
  above. Customer branch gains a Bookings item.
- Setting page reuses `admin/venues/Edit.vue`.
- New `customer/Bookings/Index.vue` (or similar) for the customer bookings list.

## Testing

- Feature tests: a venue admin sees only their venue's courts / bookings /
  customers / staff; cannot open another venue's court/booking/user (403);
  Setting edits only their venue. Super-admin retains global access. Customer
  bookings page lists only the signed-in customer's bookings.
