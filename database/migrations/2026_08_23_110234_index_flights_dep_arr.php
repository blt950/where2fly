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
        Schema::table('flights', function (Blueprint $table) {
            // Adding this composite (same leading column) makes InnoDB auto-drop the
            // auto-generated FK index flights_airport_dep_id_foreign — expected and harmless.
            $table->index(['airport_dep_id', 'airport_arr_id', 'seen_counter']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('flights', function (Blueprint $table) {
            // MySQL refuses to drop the last index serving the airport_dep_id FK (errno 1553),
            // so restore an explicit single-column index before dropping the composite.
            $table->index('airport_dep_id');
            $table->dropIndex(['airport_dep_id', 'airport_arr_id', 'seen_counter']);
        });
    }
};
