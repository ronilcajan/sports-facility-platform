# RBAC + Court Foundation — Design Spec

**Sub-project #1 of the Sports Facility Management Platform (SFMP)**
Date: 2026-07-06 · Branch: `feature/rbac-court-foundation`

## Purpose

Establish the authorization and court-domain foundation that every later
sub-project (booking, payments, dashboards, reports) reads through. Two
concerns, delivered together because they are coupled by staff-court scoping:

1. **RBAC** — roles & permissions via `spatie/laravel-permission`.
2. **Court domain** — the `Court` model, its images, and the many-to-many
   staff↔court assignment that gates all future booking queries.

Out of scope here (later sub-projects): business hours, holidays, the booking
engine, payments, QR, CMS, theme, public site, reports.

## Roles

Roles are seeded, not user-created (V1). Names use kebab-case per Spatie
convention, surfaced through a `RoleName` string enum for type safety.

| Role | Notes |
|------|-------|
| `super-admin` | Full access. Bypasses all policies via `Gate::before`. |
| `admin` | Manages courts, bookings, customers, payments, reports, announcements. Cannot manage super-admins. |
| `staff` | Scoped to assigned courts only (via `court_user`). |
| `customer` | Books courts, views own bookings/profile. |

**Guest is NOT a database role.** A guest is an *unauthenticated* actor who can
book without an account. It is represented by the absence of a `User`, handled
at the policy/route level — not by a Spatie role. This avoids a meaningless
"guest" row that can never be assigned to a user.

Every newly registered user (via Fortify `CreateNewUser`) is assigned the
`customer` role automatically.

## Permissions

Grouped by module, seeded alongside roles. Foundation ships the court-domain
permissions; later sub-projects append their own in their own migrations/seeders:

- `courts.viewAny`, `courts.view`, `courts.create`, `courts.update`, `courts.delete`
- `courts.assignStaff`

`admin` and `super-admin` get all court permissions. `staff` gets `courts.view`
+ `courts.viewAny` (scoped by policy to assigned courts). `customer` gets none
of the management permissions.

## Data Model

### `courts`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| name | string | |
| slug | string, unique | derived from name |
| sport_type | string (enum-backed) | default `pickleball`; enum lists all future sports for extensibility |
| description | text, nullable | |
| status | string (enum-backed) | `available` \| `maintenance` \| `closed`, default `available` |
| base_price | decimal(10,2) | price per slot; booking sub-project may layer dynamic pricing later |
| slot_duration_minutes | unsignedSmallInt | default 60 |
| buffer_minutes | unsignedSmallInt | default 0 |
| is_active | boolean | default true (soft on/off distinct from maintenance status) |
| timestamps | | |
| softDeletes | | courts are rarely hard-deleted; bookings reference them |

Indexes: unique(`slug`), index(`status`), index(`sport_type`).

**Operating hours are deliberately NOT on the court here.** The doc has both
per-court hours and a global "Business Hours" config — reconciling them is
sub-project #2's job. Adding an hours column now would be rework.

### `court_images`
| Column | Type | Notes |
|--------|------|-------|
| id | bigint PK | |
| court_id | FK → courts, cascade delete | |
| path | string | storage path |
| is_primary | boolean | default false |
| sort_order | unsignedSmallInt | default 0 |
| timestamps | | |

### `court_user` (pivot — staff assignment)
| Column | Type |
|--------|------|
| court_id | FK → courts, cascade delete |
| user_id | FK → users, cascade delete |
| timestamps | |

Composite unique(`court_id`, `user_id`).

## Enums (PHP 8.4, TitleCase keys per project rules)

- `App\Enums\RoleName` — SuperAdmin, Admin, Staff, Customer (string-backed kebab values).
- `App\Enums\SportType` — Pickleball, Tennis, Badminton, Padel, Basketball, Volleyball, Squash, Futsal, MeetingRoom.
- `App\Enums\CourtStatus` — Available, Maintenance, Closed.

## Models & Relationships

- `User` — add Spatie `HasRoles` trait; `assignedCourts(): BelongsToMany` (staff).
  Follow existing `#[Fillable]`/`#[Hidden]` attribute convention.
- `Court` — `HasFactory`, `SoftDeletes`; `images(): HasMany`,
  `primaryImage(): HasOne`, `staff(): BelongsToMany`. `sport_type`/`status`
  cast to enums in `casts()`.
- `CourtImage` — `belongsTo(Court)`.

## Authorization

- `CourtPolicy` maps each ability to its `courts.*` permission.
- **Staff scoping:** `view`/`update` return true for staff only when the court
  is in their assigned set. A `Court::scopeVisibleTo(User)` query scope returns
  the assignment-filtered set for staff and all courts for admins — every future
  booking query uses this scope so scoping lives in one place.
- `Gate::before` grants `super-admin` everything.

## HTTP Surface (this sub-project)

Admin court management only — the customer/public court browsing UI belongs to
later sub-projects. Wayfinder-generated typed routes.

- `CourtController` (resource: index, create, store, show, edit, update, destroy)
  under an `admin` route group guarded by `role:admin|super-admin`.
- `CourtStaffController` (store/destroy) for assigning/unassigning staff.
- `StoreCourtRequest` / `UpdateCourtRequest` Form Requests (validated data only).
- Inertia pages: `admin/courts/Index`, `Create`, `Edit` (shadcn-vue). Basic but
  clean; premium polish can iterate later.

## Seeders & Factories

- `RolePermissionSeeder` — idempotent; creates roles + court permissions, wires
  them up. Safe to re-run.
- `CourtFactory` with states: `available()`, `maintenance()`, `closed()`.
- `CourtImageFactory`.
- `DatabaseSeeder` — seeds roles, a super-admin, an admin, a staff user, a
  customer, and a handful of courts for local dev.

## Testing (Pest, feature-first)

- Roles/permissions seed correctly and are idempotent on re-run.
- New registrations receive the `customer` role.
- Super-admin bypasses policies; admin can CRUD courts; customer is forbidden.
- **Staff sees/edits only assigned courts** (the critical scoping test) — asserts
  `scopeVisibleTo` and `CourtPolicy` agree.
- Court CRUD happy paths + validation failures via Form Requests.
- Slug uniqueness + auto-generation.

## Dependencies

- Add `spatie/laravel-permission` (approved). No other new deps.

## Definition of Done

Migrations run clean; seeders idempotent; all Pest tests green;
`vendor/bin/pint --dirty` clean; admin can manage courts and assign staff; a
staff user provably cannot see unassigned courts. Committed on the feature
branch.
