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
            $table->timestamp('source_checked_at')->nullable()->after('source_reference_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sceneries', function (Blueprint $table) {
            $table->dropColumn('source_checked_at');
        });
    }
};
