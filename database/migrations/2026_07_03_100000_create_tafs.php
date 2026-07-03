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
        Schema::create('tafs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('airport_id')->constrained('airports')->onDelete('cascade');
            $table->text('raw_text');
            $table->string('change_indicator')->nullable();
            $table->unsignedTinyInteger('probability')->nullable();
            $table->string('wind_dir_degrees')->nullable();
            $table->unsignedInteger('wind_speed_kt')->nullable();
            $table->unsignedInteger('wind_gust_kt')->nullable();
            $table->string('visibility_statute_mi')->nullable();
            $table->string('wx_string')->nullable();
            $table->json('sky_condition')->nullable();
            $table->dateTime('issued_at');
            $table->dateTime('valid_from');
            $table->dateTime('valid_to');
            $table->dateTime('last_update');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tafs');
    }
};
