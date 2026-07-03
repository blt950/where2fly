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
        // The table is rebuilt from scratch on every calc:scores run, so it's
        // safe to empty it before changing column types and adding NOT NULL columns
        DB::table('airport_scores')->truncate();

        Schema::table('airport_scores', function (Blueprint $table) {
            $table->unsignedBigInteger('airport_id')->change();
            $table->foreign('airport_id')->references('id')->on('airports')->onDelete('cascade');
            $table->json('data')->nullable()->change();
            $table->string('source')->after('data');
            $table->dateTime('valid_from')->after('source');
            $table->dateTime('valid_to')->after('valid_from');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airport_scores', function (Blueprint $table) {
            $table->dropForeign(['airport_id']);
            $table->dropColumn(['source', 'valid_from', 'valid_to']);
            $table->text('data')->nullable()->change();
            $table->string('airport_id')->change();
        });
    }
};
