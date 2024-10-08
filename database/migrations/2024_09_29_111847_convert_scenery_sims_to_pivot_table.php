<?php

use App\Models\Scenery;
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
        $sceneries = Scenery::all();
        foreach ($sceneries as $scenery) {
            $scenery->simulators()->attach($scenery->simulator_id);
        }

        Schema::table('sceneries', function (Blueprint $table) {
            $table->dropForeign(['simulator_id']);
            $table->dropColumn('simulator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Breaking changes
    }
};
