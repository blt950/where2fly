<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notable_airports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('airport_id')
                ->constrained('airports');

            $table->text('description');
            $table->string('source_url');

            $table->unique('airport_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notable_airports');
    }
};
