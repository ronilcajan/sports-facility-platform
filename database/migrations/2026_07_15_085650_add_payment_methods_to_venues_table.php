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
        Schema::table('venues', function (Blueprint $table) {
            $table->string('gcash_number')->nullable()->after('email');
            $table->string('gcash_qr_path')->nullable()->after('gcash_number');
            $table->string('maya_number')->nullable()->after('gcash_qr_path');
            $table->string('maya_qr_path')->nullable()->after('maya_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('venues', function (Blueprint $table) {
            $table->dropColumn(['gcash_number', 'gcash_qr_path', 'maya_number', 'maya_qr_path']);
        });
    }
};
