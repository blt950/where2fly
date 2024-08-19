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
        Schema::create('simulators', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('shortened_name');
        });

        // Seed with some default data
        DB::table('simulators')->insert([
            ['name' => 'Microsoft Flight Simulator', 'shortened_name' => 'MSFS'],
            ['name' => 'X-Plane 12', 'shortened_name' => 'XP12'],
            ['name' => 'X-Plane 11', 'shortened_name' => 'XP11'],
            ['name' => 'Prepar3D v5', 'shortened_name' => 'P3Dv5'],
            ['name' => 'Prepar3D v4', 'shortened_name' => 'P3Dv4'],
            ['name' => 'Prepar3D v3', 'shortened_name' => 'P3Dv3'],
            ['name' => 'Flight Simulator X', 'shortened_name' => 'FSX'],
            ['name' => 'Flight Simulator 2004', 'shortened_name' => 'FS2004'],
            ['name' => 'Infinite Flight', 'shortened_name' => 'IF'],
            ['name' => 'Other', 'shortened_name' => 'Other'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('simulators');
    }
};
