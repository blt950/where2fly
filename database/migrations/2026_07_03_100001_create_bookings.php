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
        Schema::create('bookings', function (Blueprint $table) {
            $table->unsignedBigInteger('vatsim_booking_id')->primary();
            $table->string('callsign');
            $table->foreignId('airport_id')->constrained('airports')->onDelete('cascade');
            $table->string('division')->nullable();
            $table->string('subdivision')->nullable();
            $table->dateTime('start');
            $table->dateTime('end');
            $table->dateTime('last_synced_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
