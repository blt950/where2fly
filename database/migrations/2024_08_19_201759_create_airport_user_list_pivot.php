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
        Schema::create('airport_user_list', function (Blueprint $table) {
            $table->foreignId('user_list_id')->constrained('user_lists')->onDelete('cascade');
            $table->foreignId('airport_id')->constrained('airports')->onDelete('cascade');
            $table->primary(['user_list_id', 'airport_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('airport_user_list');
    }
};
