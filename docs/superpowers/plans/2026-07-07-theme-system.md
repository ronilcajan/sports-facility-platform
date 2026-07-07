# Theme System + Modern Public-Site Restyle — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a runtime Theme Manager — the public marketing site is styled entirely by CSS variables, and an admin picks which of three fixed-mood themes (Court Navy / Fairway / Electric) is active site-wide — plus a modern restyle of the Homepage and shared shell across all three themes.

**Architecture:** A new semantic marketing token layer (`--site-*` CSS variables) is defined once by name and given values per theme under `[data-theme="…"]` selectors, mirroring the existing `:root`/`.dark` mechanism but operator-selected. The active theme is stored in a `site_settings` key/value table, injected onto `<html data-theme>` server-side (no FOUC, same pattern as `HandleAppearance`), and managed from an admin Appearance page.

**Tech Stack:** Laravel 13, Inertia v3, Vue 3, Tailwind v4 (CSS-first `@theme`), Wayfinder, Spatie Permission, Pest 4.

## Global Constraints

- PHP 8.4; enums are string-backed with TitleCase cases, a static `values(): array`, and a `label(): string` — match `app/Enums/RoleName.php` and `app/Enums/CourtStatus.php`.
- Use PHP 8 constructor property promotion, explicit return types, curly braces on all control structures.
- Admin routes are gated by `role:super-admin|admin` middleware in `routes/admin.php`; do not add a new base folder.
- Every change is test-driven (Pest). Run `vendor/bin/pint --dirty --format agent` after PHP edits.
- Run `npm run build` before any Pest test that renders an Inertia page (`config/inertia.php` `testing.ensure_pages_exist=true`). Roles are seeded per Feature test via `tests/Pest.php`; the `userWithRole(string $role)` global helper exists.
- Frontend imports Wayfinder actions from `@/actions/…`; regenerate with `php artisan wayfinder:generate` after adding/altering controllers or routes.
- Leave the shadcn dashboard tokens (`:root`/`.dark`) and the legacy `ink/court/volt/chalk/fog` marketing tokens untouched — the un-restyled pages still depend on the legacy tokens.
- Verification loop before finalizing: `vendor/bin/pint --dirty` → `npm run build` → `php artisan test --compact` → `npm run types:check`.

---

### Task 1: `SiteTheme` enum

**Files:**
- Create: `app/Enums/SiteTheme.php`
- Test: `tests/Unit/SiteThemeTest.php`

**Interfaces:**
- Produces: `App\Enums\SiteTheme` (string-backed: `Navy='navy'`, `Fairway='fairway'`, `Electric='electric'`), `SiteTheme::values(): array<int,string>`, `SiteTheme->label(): string`, `SiteTheme->description(): string`, `SiteTheme::default(): self` (returns `Navy`).

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Enums\SiteTheme;

test('site theme exposes its string values', function (): void {
    expect(SiteTheme::values())->toBe(['navy', 'fairway', 'electric']);
});

test('site theme has a human label and description for each case', function (): void {
    expect(SiteTheme::Navy->label())->toBe('Court Navy')
        ->and(SiteTheme::Fairway->label())->toBe('Fairway')
        ->and(SiteTheme::Electric->label())->toBe('Electric')
        ->and(SiteTheme::Navy->description())->not->toBe('');
});

test('site theme default is navy', function (): void {
    expect(SiteTheme::default())->toBe(SiteTheme::Navy);
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SiteThemeTest`
Expected: FAIL — `Class "App\Enums\SiteTheme" not found`.

- [ ] **Step 3: Write the enum**

```php
<?php

namespace App\Enums;

enum SiteTheme: string
{
    case Navy = 'navy';
    case Fairway = 'fairway';
    case Electric = 'electric';

    /**
     * All theme values.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $theme): string => $theme->value, self::cases());
    }

    /**
     * The theme applied when no preference has been saved.
     */
    public static function default(): self
    {
        return self::Navy;
    }

    /**
     * Human-readable name for the admin picker.
     */
    public function label(): string
    {
        return match ($this) {
            self::Navy => 'Court Navy',
            self::Fairway => 'Fairway',
            self::Electric => 'Electric',
        };
    }

    /**
     * Short mood description shown on the theme card.
     */
    public function description(): string
    {
        return match ($this) {
            self::Navy => 'Deep navy with electric azure — bold and sporty.',
            self::Fairway => 'Light, airy cream and forest green — premium and calm.',
            self::Electric => 'Vibrant indigo and emerald — energetic and modern.',
        };
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test --compact --filter=SiteThemeTest`
Expected: PASS (3 tests).

- [ ] **Step 5: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Enums/SiteTheme.php tests/Unit/SiteThemeTest.php
git commit -m "feat: add SiteTheme enum"
```

---

### Task 2: `site_settings` table + cached `SiteSetting` model

**Files:**
- Create: `database/migrations/2026_07_07_000001_create_site_settings_table.php`
- Create: `app/Models/SiteSetting.php`
- Test: `tests/Unit/SiteSettingTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: `App\Models\SiteSetting` with static `SiteSetting::get(string $key, mixed $default = null): mixed` (cache-backed) and `SiteSetting::set(string $key, mixed $value): void` (writes + busts cache). Cache key format: `site_setting:{$key}`.

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

test('get returns the default when the key is absent', function (): void {
    expect(SiteSetting::get('active_theme', 'navy'))->toBe('navy');
});

test('set persists a value that get returns', function (): void {
    SiteSetting::set('active_theme', 'fairway');

    expect(SiteSetting::get('active_theme', 'navy'))->toBe('fairway');
    $this->assertDatabaseHas('site_settings', ['key' => 'active_theme', 'value' => 'fairway']);
});

test('set overwrites an existing value and busts the cache', function (): void {
    SiteSetting::set('active_theme', 'fairway');
    SiteSetting::get('active_theme'); // warm the cache

    SiteSetting::set('active_theme', 'electric');

    expect(SiteSetting::get('active_theme'))->toBe('electric');
    expect(Cache::get('site_setting:active_theme'))->toBe('electric');
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --compact --filter=SiteSettingTest`
Expected: FAIL — `Class "App\Models\SiteSetting" not found`.

- [ ] **Step 3: Write the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
    }
};
```

- [ ] **Step 4: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property int $id
 * @property string $key
 * @property string|null $value
 */
#[Fillable(['key', 'value'])]
class SiteSetting extends Model
{
    /**
     * Read a setting by key, falling back to the given default. Cached forever
     * and busted on write so hot paths (every request) avoid a DB round-trip.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = Cache::rememberForever(
            "site_setting:{$key}",
            fn (): mixed => static::query()->where('key', $key)->value('value'),
        );

        return $value ?? $default;
    }

    /**
     * Write a setting by key and bust its cache entry.
     */
    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget("site_setting:{$key}");
    }
}
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --compact --filter=SiteSettingTest`
Expected: PASS (3 tests).

- [ ] **Step 6: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add database/migrations/2026_07_07_000001_create_site_settings_table.php app/Models/SiteSetting.php tests/Unit/SiteSettingTest.php
git commit -m "feat: add site_settings store with cached SiteSetting model"
```

---

### Task 3: CSS token layer — semantic marketing tokens + three theme blocks

**Files:**
- Modify: `resources/css/app.css` (append after the existing marketing `@theme` block near line 186)

**Interfaces:**
- Produces: Tailwind utilities `bg-surface`, `bg-surface-elevated`, `bg-surface-inverse`, `text-content`, `text-content-muted`, `text-content-inverse`, `bg-brand`/`text-brand`, `text-brand-foreground`, `bg-highlight`/`text-highlight`, `border-line`, `font-display`, plus `var(--site-radius)` and `var(--site-shadow-card)`. Values switch with `<html data-theme="navy|fairway|electric">`.

- [ ] **Step 1: Add the token mappings and theme blocks**

Append to `resources/css/app.css`:

```css
/*
  Theme-driven marketing tokens. Names are theme-agnostic; values are set per
  theme under [data-theme]. The active theme is chosen by an operator and
  applied to <html data-theme>. Kept separate from the shadcn dashboard tokens
  (:root/.dark) and the legacy ink/court/volt palette.
*/
@theme inline {
    --color-surface: var(--site-surface);
    --color-surface-elevated: var(--site-surface-elevated);
    --color-surface-inverse: var(--site-surface-inverse);
    --color-content: var(--site-content);
    --color-content-muted: var(--site-content-muted);
    --color-content-inverse: var(--site-content-inverse);
    --color-brand: var(--site-brand);
    --color-brand-foreground: var(--site-brand-foreground);
    --color-highlight: var(--site-highlight);
    --color-line: var(--site-line);
    --font-display: var(--site-font-display);
}

/* Court Navy — Spotipb-inspired dark. Default theme. */
[data-theme='navy'] {
    --site-surface: #0b1f38;
    --site-surface-elevated: #12294a;
    --site-surface-inverse: #071528;
    --site-content: #eef3fb;
    --site-content-muted: #9fb2cc;
    --site-content-inverse: #eef3fb;
    --site-brand: #3b82f6;
    --site-brand-foreground: #ffffff;
    --site-highlight: #7c6cf0;
    --site-line: rgba(255, 255, 255, 0.1);
    --site-radius: 0.9rem;
    --site-shadow-card: 0 20px 45px -20px rgba(0, 0, 0, 0.55);
    --site-font-display: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}

/* Fairway — Golfngy-inspired light. */
[data-theme='fairway'] {
    --site-surface: #f5f4ee;
    --site-surface-elevated: #ffffff;
    --site-surface-inverse: #1f2a1c;
    --site-content: #1a1a17;
    --site-content-muted: #5c6157;
    --site-content-inverse: #f3f5ee;
    --site-brand: #2f5233;
    --site-brand-foreground: #ffffff;
    --site-highlight: #c8a86b;
    --site-line: rgba(26, 26, 23, 0.1);
    --site-radius: 1.25rem;
    --site-shadow-card: 0 24px 50px -28px rgba(31, 42, 28, 0.35);
    --site-font-display: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}

/* Electric — FootballCoin-inspired dark-vibrant. */
[data-theme='electric'] {
    --site-surface: #1e1b4b;
    --site-surface-elevated: #241a52;
    --site-surface-inverse: #14102e;
    --site-content: #f0eefb;
    --site-content-muted: #b0a9d6;
    --site-content-inverse: #f0eefb;
    --site-brand: #10b981;
    --site-brand-foreground: #06231a;
    --site-highlight: #8b5cf6;
    --site-line: rgba(255, 255, 255, 0.1);
    --site-radius: 0.8rem;
    --site-shadow-card: 0 22px 48px -22px rgba(0, 0, 0, 0.6);
    --site-font-display: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif;
}
```

- [ ] **Step 2: Verify the stylesheet compiles**

Run: `npm run build`
Expected: build completes with no CSS errors; the manifest is written.

- [ ] **Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "feat: add theme-driven marketing token layer with 3 themes"
```

---

### Task 4: Server-side theme injection (no FOUC)

**Files:**
- Create: `app/Http/Middleware/HandleSiteTheme.php`
- Modify: `bootstrap/app.php` (web middleware group — add `HandleSiteTheme::class` beside `HandleAppearance::class`)
- Modify: `resources/views/app.blade.php` (add `data-theme` to `<html>` and per-theme FOUC background)
- Modify: `app/Http/Middleware/HandleInertiaRequests.php` (expose `activeTheme` in `siteData()`)
- Test: `tests/Feature/Site/ThemeRenderTest.php`

**Interfaces:**
- Consumes: `SiteSetting::get()`, `SiteTheme::default()`.
- Produces: Blade view variable `$siteTheme` (string); Inertia shared prop `site.activeTheme` (string).

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\SiteSetting;

test('the homepage renders the default theme on the html element', function (): void {
    $this->get('/')->assertOk()->assertSee('data-theme="navy"', false);
});

test('the homepage reflects the active theme setting', function (): void {
    SiteSetting::set('active_theme', 'fairway');

    $this->get('/')->assertOk()->assertSee('data-theme="fairway"', false);
});
```

- [ ] **Step 2: Build assets, then run the test to verify it fails**

Run: `npm run build && php artisan test --compact --filter=ThemeRenderTest`
Expected: FAIL — no `data-theme` attribute is rendered yet.

- [ ] **Step 3: Write the middleware**

```php
<?php

namespace App\Http\Middleware;

use App\Enums\SiteTheme;
use App\Models\SiteSetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class HandleSiteTheme
{
    /**
     * Share the operator-selected marketing theme with the root view so it can
     * be applied to <html data-theme> before first paint.
     */
    public function handle(Request $request, Closure $next): Response
    {
        View::share('siteTheme', SiteSetting::get('active_theme', SiteTheme::default()->value));

        return $next($request);
    }
}
```

- [ ] **Step 4: Register the middleware**

In `bootstrap/app.php`, find the `->withMiddleware(function (Middleware $middleware) {` block where `HandleAppearance::class` is appended to the web group and add `HandleSiteTheme::class` alongside it. Example (match the existing call style):

```php
$middleware->web(append: [
    \App\Http\Middleware\HandleAppearance::class,
    \App\Http\Middleware\HandleSiteTheme::class,
    \App\Http\Middleware\HandleInertiaRequests::class,
    \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
]);
```

If the existing block lists these individually, insert only the `HandleSiteTheme::class` line after `HandleAppearance::class` without reordering the rest.

- [ ] **Step 5: Apply the theme in the root Blade view**

In `resources/views/app.blade.php`, change the opening `<html>` tag (line 2) to add the attribute:

```blade
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="{{ $siteTheme ?? 'navy' }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
```

Then, inside the existing `<style>` block (near line 23), add per-theme background colors so the first paint matches the theme surface:

```blade
<style>
    html {
        background-color: oklch(1 0 0);
    }

    html.dark {
        background-color: oklch(0.145 0 0);
    }

    html[data-theme='navy'] { background-color: #0b1f38; }
    html[data-theme='fairway'] { background-color: #f5f4ee; }
    html[data-theme='electric'] { background-color: #1e1b4b; }
</style>
```

- [ ] **Step 6: Expose the active theme to Inertia**

In `app/Http/Middleware/HandleInertiaRequests.php`, add the import `use App\Enums\SiteTheme;` and `use App\Models\SiteSetting;`, then add one entry to the array returned by `siteData()`:

```php
'activeTheme' => SiteSetting::get('active_theme', SiteTheme::default()->value),
```

- [ ] **Step 7: Run the test to verify it passes**

Run: `php artisan test --compact --filter=ThemeRenderTest`
Expected: PASS (2 tests).

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Middleware/HandleSiteTheme.php bootstrap/app.php resources/views/app.blade.php app/Http/Middleware/HandleInertiaRequests.php tests/Feature/Site/ThemeRenderTest.php
git commit -m "feat: inject active theme onto html data-theme server-side"
```

---

### Task 5: Admin Appearance controller, request, and routes

**Files:**
- Create: `app/Http/Controllers/Admin/AppearanceController.php`
- Create: `app/Http/Requests/Admin/UpdateAppearanceRequest.php`
- Modify: `routes/admin.php`
- Test: `tests/Feature/Admin/AppearanceTest.php`

**Interfaces:**
- Consumes: `SiteTheme`, `SiteSetting`, `UpdateAppearanceRequest`.
- Produces: named routes `admin.appearance.index` (GET `admin/appearance`) and `admin.appearance.update` (PUT `admin/appearance`); Wayfinder action `@/actions/App/Http/Controllers/Admin/AppearanceController`. `index` renders `admin/appearance/Index` with props `themes: { value, label, description }[]` and `activeTheme: string`.

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Enums\RoleName;
use App\Models\SiteSetting;

test('an admin can view the appearance page', function (): void {
    $this->actingAs(userWithRole(RoleName::Admin->value))
        ->get(route('admin.appearance.index'))
        ->assertOk();
});

test('an admin can update the active theme', function (): void {
    $this->actingAs(userWithRole(RoleName::Admin->value))
        ->put(route('admin.appearance.update'), ['theme' => 'fairway'])
        ->assertRedirect();

    expect(SiteSetting::get('active_theme'))->toBe('fairway');
    $this->assertDatabaseHas('site_settings', ['key' => 'active_theme', 'value' => 'fairway']);
});

test('an invalid theme is rejected', function (): void {
    $this->actingAs(userWithRole(RoleName::Admin->value))
        ->put(route('admin.appearance.update'), ['theme' => 'chartreuse'])
        ->assertSessionHasErrors('theme');
});

test('staff and customers cannot manage appearance', function (string $role): void {
    $this->actingAs(userWithRole($role))
        ->get(route('admin.appearance.index'))
        ->assertForbidden();
})->with([RoleName::Staff->value, RoleName::Customer->value]);

test('guests are redirected to login', function (): void {
    $this->get(route('admin.appearance.index'))->assertRedirect(route('login'));
});
```

- [ ] **Step 2: Build assets, then run the test to verify it fails**

Run: `npm run build && php artisan test --compact --filter=AppearanceTest`
Expected: FAIL — route `admin.appearance.index` is not defined.

- [ ] **Step 3: Write the Form Request**

```php
<?php

namespace App\Http\Requests\Admin;

use App\Enums\SiteTheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAppearanceRequest extends FormRequest
{
    /**
     * The admin route group already gates access by role, so authorization
     * here is unconditional.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'theme' => ['required', Rule::enum(SiteTheme::class)],
        ];
    }
}
```

- [ ] **Step 4: Write the controller**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SiteTheme;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAppearanceRequest;
use App\Models\SiteSetting;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    /**
     * Show the theme picker.
     */
    public function index(): Response
    {
        return Inertia::render('admin/appearance/Index', [
            'themes' => array_map(fn (SiteTheme $theme): array => [
                'value' => $theme->value,
                'label' => $theme->label(),
                'description' => $theme->description(),
            ], SiteTheme::cases()),
            'activeTheme' => SiteSetting::get('active_theme', SiteTheme::default()->value),
        ]);
    }

    /**
     * Persist the operator-selected active theme.
     */
    public function update(UpdateAppearanceRequest $request): RedirectResponse
    {
        SiteSetting::set('active_theme', $request->validated('theme'));

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Theme updated.')]);

        return back();
    }
}
```

- [ ] **Step 5: Register the routes**

In `routes/admin.php`, add the import `use App\Http\Controllers\Admin\AppearanceController;` and, inside the existing group closure, add:

```php
Route::get('appearance', [AppearanceController::class, 'index'])->name('appearance.index');
Route::put('appearance', [AppearanceController::class, 'update'])->name('appearance.update');
```

- [ ] **Step 6: Generate Wayfinder actions**

Run: `php artisan wayfinder:generate`
Expected: `resources/js/actions/App/Http/Controllers/Admin/AppearanceController.ts` is created.

- [ ] **Step 7: Run the test to verify it passes**

Run: `php artisan test --compact --filter=AppearanceTest`
Expected: PASS (all cases, including the two dataset roles).

- [ ] **Step 8: Format and commit**

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/AppearanceController.php app/Http/Requests/Admin/UpdateAppearanceRequest.php routes/admin.php resources/js/actions tests/Feature/Admin/AppearanceTest.php
git commit -m "feat: add admin appearance controller for theme selection"
```

---

### Task 6: Admin Appearance page (Vue)

**Files:**
- Create: `resources/js/pages/admin/appearance/Index.vue`
- Test: extend `tests/Feature/Admin/AppearanceTest.php` with an Inertia-shape assertion

**Interfaces:**
- Consumes: props `themes: { value: string; label: string; description: string }[]`, `activeTheme: string`; Wayfinder action `AppearanceController.update`.
- Produces: a page under the auto-selected `AppLayout` (via `defineOptions`) matching `resources/js/pages/admin/courts/Index.vue` conventions.

- [ ] **Step 1: Add the failing Inertia assertion**

Append to `tests/Feature/Admin/AppearanceTest.php`:

```php
test('the appearance page receives themes and the active theme', function (): void {
    $this->actingAs(userWithRole(RoleName::Admin->value))
        ->get(route('admin.appearance.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/appearance/Index')
            ->has('themes', 3)
            ->where('activeTheme', 'navy'));
});
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --compact --filter="the appearance page receives themes"`
Expected: FAIL — component `admin/appearance/Index` does not exist (Vite manifest miss).

- [ ] **Step 3: Write the Vue page**

```vue
<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppearanceController from '@/actions/App/Http/Controllers/Admin/AppearanceController';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';

interface ThemeOption {
    value: string;
    label: string;
    description: string;
}

const props = defineProps<{
    themes: ThemeOption[];
    activeTheme: string;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Appearance', href: '/admin/appearance' }],
    },
});

const form = useForm({ theme: props.activeTheme });

function preview(value: string): void {
    form.theme = value;
    document.documentElement.dataset.theme = value;
}

function save(): void {
    form.submit(AppearanceController.update(), { preserveScroll: true });
}
</script>

<template>
    <div class="px-4 py-6">
        <Head title="Appearance" />

        <Heading
            title="Appearance"
            description="Choose the active theme for the public website. Applies site-wide."
        />

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <button
                v-for="theme in themes"
                :key="theme.value"
                type="button"
                class="rounded-xl border p-5 text-left transition"
                :class="
                    form.theme === theme.value
                        ? 'border-primary ring-2 ring-primary'
                        : 'border-border hover:border-primary/50'
                "
                @click="preview(theme.value)"
            >
                <span class="block text-base font-semibold">{{ theme.label }}</span>
                <span class="mt-1 block text-sm text-muted-foreground">{{ theme.description }}</span>
            </button>
        </div>

        <div class="mt-6 flex items-center gap-3">
            <Button :disabled="form.processing" @click="save">Save theme</Button>
            <span v-if="form.recentlySuccessful" class="text-sm text-muted-foreground">Saved.</span>
        </div>
    </div>
</template>
```

- [ ] **Step 4: Build and run the test to verify it passes**

Run: `npm run build && php artisan test --compact --filter=AppearanceTest`
Expected: PASS (all Appearance tests, including the Inertia-shape assertion).

- [ ] **Step 5: Type-check and commit**

```bash
npm run types:check
git add resources/js/pages/admin/appearance/Index.vue tests/Feature/Admin/AppearanceTest.php
git commit -m "feat: add admin appearance theme picker page"
```

---

### Task 7: Modern restyle — Homepage + shared shell across all themes

**Files:**
- Modify: `resources/js/components/site/SiteHeader.vue`
- Modify: `resources/js/components/site/SiteFooter.vue`
- Modify: `resources/js/components/site/PageHero.vue`
- Modify: `resources/js/components/site/SiteSection.vue`
- Modify: `resources/js/components/site/SiteCourtCard.vue`
- Modify: `resources/js/pages/site/Home.vue`
- Test: existing `tests/Feature/Site/*` must stay green; `tests/Feature/Site/ThemeRenderTest.php` extended for the three themes.

**Interfaces:**
- Consumes: the token utilities from Task 3 (`bg-surface`, `text-content`, `bg-brand`, etc.), the active theme from Task 4.
- Produces: themed shared components + restyled Homepage. No new backend.

> **REQUIRED SUB-SKILL for this task:** Use `frontend-design` for the visual work, and `inertia-vue-development` / `tailwindcss-development` for the component edits. Restyle to reference quality (the three provided designs). This task is design-led; the automated gate is "existing site tests + build + typecheck stay green across all three themes," and visual QA is manual.

- [ ] **Step 1: Extend the render test to assert every theme applies**

Replace the second test in `tests/Feature/Site/ThemeRenderTest.php` with a dataset covering all three:

```php
test('the homepage reflects each active theme', function (string $theme): void {
    SiteSetting::set('active_theme', $theme);

    $this->get('/')->assertOk()->assertSee('data-theme="'.$theme.'"', false);
})->with(['navy', 'fairway', 'electric']);
```

- [ ] **Step 2: Build and confirm the render test passes (guards the plumbing before restyling)**

Run: `npm run build && php artisan test --compact --filter=ThemeRenderTest`
Expected: PASS (default + 3 dataset themes).

- [ ] **Step 3: Migrate the shared shell to the new tokens**

For each of `SiteHeader.vue`, `SiteFooter.vue`, `PageHero.vue`, `SiteSection.vue`, `SiteCourtCard.vue`, replace legacy marketing classes with the theme tokens:
- backgrounds: `bg-ink`/`bg-chalk` → `bg-surface` (page) or `bg-surface-elevated` (cards) or `bg-surface-inverse` (contrast bands);
- text: `text-ink`/`text-chalk`/`text-fog` → `text-content` / `text-content-muted`, and `text-content-inverse` on inverse bands;
- primary CTAs and links: `bg-volt`/`text-court` → `bg-brand text-brand-foreground` (buttons) and `text-brand`/`text-highlight` (accents);
- borders/dividers: → `border-line`;
- headings: add `font-display`.
Keep markup/structure; only swap classes and add the modern hero/section treatments described in the spec (full-bleed hero with gradient overlay + dual CTAs, feature band on `surface-inverse`, stat callouts, themed cards, newsletter/CTA band). The "Book a court" CTA keeps pointing at the Courts listing route.

- [ ] **Step 4: Restyle `Home.vue`**

Rebuild the five existing sections to reference quality using the tokens and restyled components above. Preserve the content coming from `useSite()` / `config/site*.php` — no content-pipeline changes. Ensure the layout is responsive (mobile-first) and respects the existing `.reveal` reduced-motion animations.

- [ ] **Step 5: Build, type-check, and run the full public-site suite**

Run: `npm run build && npm run types:check && php artisan test --compact`
Expected: build + typecheck clean; all tests pass (public-site suite + theme tests + admin tests + prior suites).

- [ ] **Step 6: Manual visual QA across themes**

Run `composer run dev` (or `npm run dev`). Visit `/`, then set each theme from `/admin/appearance` (as an admin) and confirm the Homepage + header/footer re-skin correctly in Court Navy, Fairway, and Electric — check contrast, hero imagery/gradients, cards, and the inverse band in each.

- [ ] **Step 7: Commit**

```bash
git add resources/js/components/site resources/js/pages/site/Home.vue tests/Feature/Site/ThemeRenderTest.php
git commit -m "feat: modern themed restyle of homepage and shared shell"
```

---

## Self-Review

**Spec coverage:**
- Runtime theme system / CSS variables → Tasks 3, 4. ✓
- 3 fixed-mood themes (Navy/Fairway/Electric) → Tasks 1, 3. ✓
- Operator-global active theme + admin picker → Tasks 5, 6. ✓
- `site_settings` store + cached `SiteSetting` → Task 2. ✓
- No FOUC / server-side `data-theme` (mirrors HandleAppearance) → Task 4. ✓
- Active theme exposed in `siteData` → Task 4 Step 6. ✓
- RBAC (admin-only management) → Task 5 tests. ✓
- Homepage + shared-shell restyle across themes → Task 7. ✓
- Non-breaking (legacy tokens retained; other 8 pages untouched) → Global Constraints + Task 3 comment. ✓
- Testing (feature + unit) + build/typecheck gates → every task + Global Constraints. ✓
- Out-of-scope items (per-theme light/dark, other 8 pages, dashboard theming, newsletter backend, legacy-token removal) → not implemented, correct. ✓

**Placeholder scan:** No TBD/TODO; every code step shows real code; commands have expected output. ✓

**Type consistency:** `SiteSetting::get/set`, `SiteTheme::default()/values()/label()/description()`, prop shapes `{ value, label, description }`, and the `activeTheme` string are used identically across Tasks 1–6. ✓
