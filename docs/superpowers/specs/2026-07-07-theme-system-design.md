# Theme System + Modern Public-Site Restyle — Design Spec

**Date:** 2026-07-07
**Sub-project:** Theme Manager (pulled forward from roadmap Phase 7) + modern restyle of the public marketing site.
**Branch base:** `feature/rbac-court-foundation` (or a fresh branch off it).
**Status:** Approved design, pending implementation plan.

## Goal

Give the operator a runtime **Theme Manager**: the entire public marketing site is styled by CSS variables, and an admin setting selects which of three themes is active site-wide — no redeploy, white-label ready. Ship with the Homepage and shared shell restyled to modern, reference-quality visuals across all three themes. The remaining 8 public pages inherit the themed shared components and get a lighter polish pass in a follow-up slice.

This is the Phase 7 Theme Manager brought forward at user request, scoped to the public site (the only surface currently built). Architecture must extend cleanly to the future admin/customer dashboards (Phase 6) without rework.

## Decisions locked during brainstorming

1. **Runtime theme system**, not design-exploration mockups — a real feature with an admin switcher.
2. **Three themes**, each a **single fixed mood** (no per-theme light/dark variants in v1): Court Navy (dark), Fairway (light), Electric (dark-vibrant). Per-theme light/dark can be layered later.
3. **Operator-global** active theme — one theme for the whole site, chosen by an admin. Not per-end-user.
4. **Restyle scope this slice:** Homepage + shared shell (header, footer, hero, cards, sections). Other 8 pages follow in a later polish pass.

## Current state (what we build on)

`resources/css/app.css` already carries two token systems:

- **Dashboard tokens** — shadcn-vue semantic tokens in `:root` / `.dark`, CSS-variable-driven and mapped into Tailwind via `@theme inline`. These are per-user light/dark and drive the authenticated app. **Left untouched by this work.**
- **Marketing "court" palette** — `@theme { --color-ink / court / volt / chalk / fog }`, currently **hardcoded** (not swappable). This is what the public site uses today (`bg-ink`, `text-volt`, etc.).

The theme system converts the marketing layer to the same variable-driven pattern the dashboard already uses, but keyed by an operator-selected `data-theme` attribute instead of a per-user `.dark` class. `HandleAppearance` middleware already demonstrates the server-side root-attribute injection pattern we mirror for `data-theme`.

## Architecture

### 1. Token layer

Introduce a **new semantic marketing palette** as CSS variables, defined once as names and given values per theme:

```css
@theme inline {
    --color-surface: var(--site-surface);
    --color-surface-elevated: var(--site-surface-elevated);
    --color-surface-inverse: var(--site-surface-inverse);
    --color-content: var(--site-content);
    --color-content-muted: var(--site-content-muted);
    --color-content-inverse: var(--site-content-inverse);
    --color-brand: var(--site-brand);
    --color-brand-foreground: var(--site-brand-foreground);
    --color-accent-site: var(--site-accent);
    --color-line: var(--site-line);
    /* radius / shadow / fonts as tokens too */
}

[data-theme='navy']     { --site-surface: …; --site-brand: …; /* full set */ }
[data-theme='fairway']  { … }
[data-theme='electric'] { … }
```

Utilities become theme-agnostic: `bg-surface`, `bg-surface-inverse`, `text-content`, `text-content-muted`, `bg-brand`, `text-brand`, `border-line`, etc. Components are written once against these names and re-skin automatically when `data-theme` changes.

Per-theme non-color tokens: `--radius`, `--shadow-card`, `--font-display`, `--font-body`.

**Non-breaking migration:** the legacy `ink/court/volt/chalk/fog` tokens remain in place so the 8 un-restyled pages keep rendering. Only the Homepage + shared shell migrate to the new tokens in this slice. The follow-up polish pass migrates the remaining pages, after which the legacy tokens are removed.

### 2. The three themes (starting palettes)

Derived from the reference designs; tunable in review.

| Theme | Reference | Mood | Surface | Surface-elevated | Brand | Accent | Content |
|---|---|---|---|---|---|---|---|
| **Court Navy** | Spotipb | dark | `#0b1f38` | `#12294a` | azure `#3b82f6` | violet `#7c6cf0` | `#eef3fb` |
| **Fairway** | Golfngy | light | `#f5f4ee` | `#ffffff` | forest `#2f5233` | sand/gold `#d9c48f` | `#1a1a17` |
| **Electric** | FootballCoin | dark-vibrant | `#1e1b4b` | `#241a52` | emerald `#10b981` | purple→teal gradient | `#f0eefb` |

Per-theme feel: Fairway uses a larger `--radius` (soft, rounded, airy); Navy and Electric use tighter radii with deeper `--shadow-card`. Each theme sets its own `--font-display` for headings (bold/condensed for Navy & Electric, clean humanist for Fairway) over a shared `--font-body` base (Instrument Sans, already loaded). Custom display-font sourcing is a refinement, not a blocker — sensible system/loaded fallbacks are acceptable for v1.

### 3. Theme Manager (storage + switching)

- **`site_settings` table** — key/value store (`key` unique, `value` text/json, timestamps). Small, reusable; first and only consumer this slice is `active_theme`.
- **`SiteSetting` model** — cached accessor (`SiteSetting::get(string $key, $default)` / `::set()`), cache invalidated on write. Avoids a DB hit on every request.
- **`SiteTheme` enum** — `Navy` / `Fairway` / `Electric`, each exposing a human label and its `data-theme` value. TitleCase keys per project PHP conventions.
- **Admin → Appearance page** — Inertia page under the existing admin route group, admin/super-admin only (guarded by role, consistent with existing admin court routes). Three preview cards (swatch + name), radio-select the active theme, save via a Wayfinder-typed controller action. Live preview by flipping `document.documentElement.dataset.theme` before persisting.
- **Server-side application (no FOUC):** the active `data-theme` is written onto the `<html>` element in the root Blade view, sourced from the cached `SiteSetting`, mirroring how `HandleAppearance` injects the dark class. The active theme is also shared through `HandleInertiaRequests` (into `siteData`) so client code and the admin page know the current value.
- **Validation:** the update request validates `theme` against the `SiteTheme` enum; invalid values are rejected.

### 4. Modern restyle — Homepage + shared shell

Preserve the existing content structure (the 5 CMS-driven Home sections) and existing static content pipeline (`config/site.php` + `config/site_content.php` → `siteData` → `useSite()`); elevate the visual treatment to reference quality, themed:

- **Hero** — full-bleed, imagery + gradient overlay, eyebrow + large display headline with a colored keyword, subcopy, dual CTAs (primary `bg-brand`, secondary outline). "Book a court" CTA continues to point at the Courts listing (booking engine not built yet).
- **Story split** — text column + image cluster.
- **"What makes us different" band** — on `surface-inverse`, feature list with icon chips, supporting image, a stat callout card.
- **Stat callouts** — large numerals over `surface-elevated`.
- **Themed `SiteCourtCard`** — court listing card re-skinned to the new tokens.
- **Newsletter / CTA footer band** — email capture styled per theme (visual only unless wired; no new backend this slice).

Shared components restyled once so the other 8 pages inherit the look: `SiteHeader`, `SiteFooter`, `PageHero`, `SiteSection`, card primitives.

**Imagery:** references lean on photography. Absent supplied photos, use tasteful CSS/gradient treatments and the existing gallery placeholder approach. Real photos can be dropped in later without structural change.

## Testing

- **Feature (Pest):**
  - Admin/super-admin can view the Appearance page and update the active theme; the change persists.
  - Staff, customer, and guest are forbidden from the Appearance page and the update action (RBAC).
  - Public pages render the active `data-theme` on the root element.
  - An invalid theme value is rejected by validation.
- **Unit (Pest):**
  - `SiteSetting` cached get/set + cache invalidation on write.
  - `SiteTheme` enum label and `data-theme` mapping.
- **Gotchas:** run `npm run build` before page-render tests (Vite manifest resolution for `assertOk()` pages — config/inertia.php `testing.ensure_pages_exist=true`). Roles are seeded per Feature test via `tests/Pest.php`.

## Out of scope (this slice)

- Per-theme light/dark variants.
- Restyle of the other 8 public pages (About, Courts, Pricing, Gallery, FAQs, Contact, Privacy, Terms) — follow-up polish pass; they keep rendering on legacy tokens until then.
- Theming the authenticated dashboard/admin surfaces (arrives with Phase 6 dashboards).
- Wiring the newsletter capture to a backend.
- Removal of legacy `ink/court/volt/chalk/fog` tokens (deferred to the follow-up migration).

## Forward references

- Phase 3 scheduling remains designed and deferred (see memory `project-sfmp-scheduling-design`).
- The `site_settings` store and `data-theme` mechanism become the foundation the full Phase 7 Theme Manager (typography, appearance modes, logo/favicon, per-component UI styles) extends.
