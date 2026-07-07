<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Anchor lookups fall back to local_code (where('icao')->orWhere('local_code')).
     */
    public function up(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->index('local_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airports', function (Blueprint $table) {
            $table->dropIndex(['local_code']);
        });
    }
};
