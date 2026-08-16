<?php

use App\Models\PageView;

it('deletes page views older than the retention window and keeps recent ones', function () {
    PageView::factory()->create(['viewed_at' => now()->subDays(200)]);
    PageView::factory()->create(['viewed_at' => now()->subDays(10)]);

    $this->artisan('analytics:prune')->assertSuccessful();

    expect(PageView::query()->count())->toBe(1);
});

it('honours a custom retention window', function () {
    PageView::factory()->create(['viewed_at' => now()->subDays(20)]);
    PageView::factory()->create(['viewed_at' => now()->subDays(2)]);

    $this->artisan('analytics:prune', ['--days' => 7])->assertSuccessful();

    expect(PageView::query()->count())->toBe(1);
});
