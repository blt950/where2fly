<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The score column becomes a confidence weight: 1.00 for certain signals,
     * below 1 for uncertain TAF periods (TEMPO/PROB) — see
     * AirportScore::forecastWeight(). Existing rows are all 1 and stay 1.00.
     */
    public function up(): void
    {
        Schema::table('airport_scores', function (Blueprint $table) {
            $table->decimal('score', 3, 2)->unsigned()->default(1)->change();
        });

        // Backfill existing TEMPO/PROB rows: fetch:tafs only rebuilds scores
        // when a TAF's issued_at advances, so without this the pre-migration
        // rows keep weight 1 (badged as uncertain but ranked/rendered as
        // certain) until every TAF has been reissued. The ladder is inlined —
        // a migration shouldn't drift with future AirportScore changes.
        $weights = [
            'JSON_EXTRACT(data, "$.probability") = 40 AND JSON_EXTRACT(data, "$.tempo") = true' => 0.4,
            'JSON_EXTRACT(data, "$.probability") = 30 AND JSON_EXTRACT(data, "$.tempo") = true' => 0.25,
            'JSON_EXTRACT(data, "$.probability") = 40' => 0.5,
            'JSON_EXTRACT(data, "$.probability") = 30' => 0.3,
            'JSON_EXTRACT(data, "$.tempo") = true' => 0.7,
        ];

        foreach ($weights as $condition => $weight) {
            DB::table('airport_scores')
                ->where('source', 'taf')
                ->where('score', 1)
                ->whereRaw($condition)
                ->update(['score' => $weight]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('airport_scores', function (Blueprint $table) {
            $table->tinyInteger('score')->default(1)->change();
        });
    }
};
