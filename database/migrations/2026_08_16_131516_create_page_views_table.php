<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table): void {
            $table->id();

            // Daily-rotating hash of IP + user-agent. Identifies a unique
            // visitor for a single day without ever storing the raw IP.
            $table->string('visitor_id', 64);

            // Groups consecutive views into one session so bounce rate and
            // session duration can be derived.
            $table->string('session_id', 64);

            $table->string('path');
            $table->string('route_name')->nullable();
            $table->string('referrer_host')->nullable();
            $table->string('source', 16);
            $table->string('device', 16);
            $table->timestamp('viewed_at');

            // Every dashboard aggregate filters on the reporting window first,
            // then groups by one of these columns.
            $table->index('viewed_at');
            $table->index(['viewed_at', 'visitor_id']);
            $table->index(['viewed_at', 'path']);
            $table->index(['session_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
