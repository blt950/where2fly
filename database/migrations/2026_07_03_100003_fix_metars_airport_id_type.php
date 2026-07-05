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
        DB::table('metars')
            ->whereNotIn('airport_id', DB::table('airports')->select('id'))
            ->delete();

        Schema::table('metars', function (Blueprint $table) {
            $table->unsignedBigInteger('airport_id')->change();
            $table->foreign('airport_id')->references('id')->on('airports')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('metars', function (Blueprint $table) {
            $table->dropForeign(['airport_id']);
            $table->string('airport_id')->change();
        });
    }
};
