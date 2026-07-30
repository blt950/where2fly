<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * applyCoversEta filters on source + valid_from/valid_to in every score
     * EXISTS probe and in the sortByScores join. Keep this the only extra
     * index on the table — the fetch commands delete+rebuild it constantly
     * and every index taxes those bulk writes.
     */
    public function up(): void
    {
        Schema::table('airport_scores', function (Blueprint $table) {
            $table->index(['airport_id', 'source', 'valid_from', 'valid_to']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airport_scores', function (Blueprint $table) {
            $table->dropIndex(['airport_id', 'source', 'valid_from', 'valid_to']);
        });
    }
};
