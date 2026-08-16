<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            // Availability lookups and per-court calendars filter on both.
            $table->index(['court_id', 'date']);

            // Calendar boards and reports scan date ranges across all courts.
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropIndex(['court_id', 'date']);
            $table->dropIndex(['date']);
        });
    }
};
