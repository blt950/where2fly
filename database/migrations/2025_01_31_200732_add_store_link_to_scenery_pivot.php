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
        Schema::table('scenery_simulators', function (Blueprint $table) {
            $table->string('link')->nullable()->after('simulator_id');
            $table->boolean('payware')->default(false)->after('link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('scenery_simulators', function (Blueprint $table) {
            $table->dropColumn('link');
            $table->dropColumn('payware');
        });
    }
};
