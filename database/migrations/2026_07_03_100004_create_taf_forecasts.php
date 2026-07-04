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
        Schema::create('taf_forecasts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('taf_id')->constrained('tafs')->onDelete('cascade');
            $table->string('change_indicator')->nullable();
            $table->unsignedTinyInteger('probability')->nullable();
            $table->string('wind_dir_degrees')->nullable();
            $table->unsignedInteger('wind_speed_kt')->nullable();
            $table->unsignedInteger('wind_gust_kt')->nullable();
            $table->string('visibility_statute_mi')->nullable();
            $table->string('wx_string')->nullable();
            $table->unsignedInteger('ceiling_ft_agl')->nullable();
            $table->dateTime('valid_from');
            $table->dateTime('valid_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('taf_forecasts');
    }
};
