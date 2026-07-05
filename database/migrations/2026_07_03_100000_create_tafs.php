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
            $table->dateTime('issued_at');
            $table->dateTime('bulletin_time')->nullable();
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
