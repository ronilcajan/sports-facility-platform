<?php

namespace App\Console\Commands;

use App\Models\PageView;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class PrunePageViews extends Command
{
    protected $signature = 'analytics:prune {--days=180 : Delete page views older than this many days}';

    protected $description = 'Delete page view records outside the analytics retention window';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = Carbon::now()->subDays($days)->startOfDay();

        $deleted = PageView::query()->where('viewed_at', '<', $cutoff)->delete();

        $this->info("Pruned {$deleted} page view(s) recorded before {$cutoff->toDateString()}.");

        return self::SUCCESS;
    }
}
