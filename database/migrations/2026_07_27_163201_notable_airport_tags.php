<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notable_airport_tags', function (Blueprint $table) {
            $table->foreignId('airport_id')
                ->constrained('airports');

            $table->unsignedTinyInteger('category');

            // Prevent the same tag being attached twice to one airport.
            $table->unique(['airport_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notable_airport_tags');
    }
};
