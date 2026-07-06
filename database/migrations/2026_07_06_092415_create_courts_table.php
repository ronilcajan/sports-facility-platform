<?php

use App\Enums\CourtStatus;
use App\Enums\SportType;
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
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('sport_type')->default(SportType::Pickleball->value)->index();
            $table->text('description')->nullable();
            $table->string('status')->default(CourtStatus::Available->value)->index();
            $table->decimal('base_price', 10, 2)->default(0);
            $table->unsignedSmallInteger('slot_duration_minutes')->default(60);
            $table->unsignedSmallInteger('buffer_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
