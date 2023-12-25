<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->uuid('key')->unique();
            $table->string('name');
            $table->string('ip_address');
            $table->boolean('disabled')->default(false);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('created_at')->default(DB::raw('(UTC_TIMESTAMP)'));
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('api_keys');
    }
};
