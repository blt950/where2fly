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
            $table->renameColumn('scenery_id', 'scenery_developer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sceneries', function (Blueprint $table) {
            $table->renameColumn('scenery_developer_id', 'scenery_id');
        });
    }
};
