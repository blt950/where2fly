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
        Schema::create('sceneries', function (Blueprint $table) {
            $table->id();
            $table->string('icao', 4);
            $table->string('author');
            $table->string('link');
            $table->foreignId('airport_id')->nullable()->constrained()->onDelete('SET NULL');
            $table->foreignId('simulator_id')->nullable()->constrained()->onDelete('SET NULL');
            $table->boolean('payware');
            $table->boolean('published');
            $table->foreignId('suggested_by_user_id')->nullable()->constrained('users', 'id')->onDelete('SET NULL');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sceneries');
    }
};
