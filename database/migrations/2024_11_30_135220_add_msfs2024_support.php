<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('simulators', function (Blueprint $table) {
            $table->integer('order')->nullable();
        });

        DB::table('simulators')->update([
            'order' => DB::raw('id'),
        ]);

        // Rename the existing MSFS to "MSFS20"
        DB::table('simulators')
            ->where('shortened_name', 'MSFS')
            ->update([
                'name' => 'Microsoft Flight Simulator 2020',
                'shortened_name' => 'MSFS2020',
            ]);

        // Insert a new row for "Microsoft Flight Simulator 2024"
        DB::table('simulators')->insert([
            'name' => 'Microsoft Flight Simulator 2024',
            'shortened_name' => 'MSFS2024',
            'order' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the 'order' column
        Schema::table('simulators', function (Blueprint $table) {
            $table->dropColumn('order');
        });

        // Restore the original "MSFS" name
        DB::table('simulators')
            ->where('shortened_name', 'MSFS2020')
            ->update([
                'name' => 'Microsoft Flight Simulator',
                'shortened_name' => 'MSFS',
            ]);

        // Remove the "Microsoft Flight Simulator 2024" row
        DB::table('simulators')
            ->where('shortened_name', 'MSFS2024')
            ->delete();
    }
};
