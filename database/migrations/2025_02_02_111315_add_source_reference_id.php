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
        Schema::table('sceneries', function (Blueprint $table) {
            $table->unsignedBigInteger('source_reference_id')->nullable()->after('source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sceneries', function (Blueprint $table) {
            $table->dropColumn('source_reference_id');
        });
    }
};
