<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Scenery;
use App\Models\Simulator;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $sceneries = Scenery::all();
        $msfs2024 = Simulator::find(11);

        foreach($sceneries as $scenery){

            $linkedSimulators = $scenery->simulators;
            foreach($linkedSimulators as $simulator){

                // Set the source based on when it was added to the database
                $source = 'manual';
                if($scenery->suggested_by_user_id){
                    $source = 'user_contribution';
                } else {
                    if($scenery->created_at >= '2024-10-10'){
                        $source = 'fsaddoncompare';
                    } else {
                        $source = 'manual';
                    }
                }

                // Re-insert the scenery into the pivot table
                $simulator->sceneries()->detach($scenery->id);
                $simulator->sceneries()->attach($scenery->id, [
                    'link' => $scenery->link,
                    'payware' => $scenery->payware,
                    'published' => $scenery->published,
                    'source' => $source,
                    'suggested_by_user_id' => $scenery->suggested_by_user_id,
                    'created_at' => $scenery->created_at,
                    'updated_at' => $scenery->updated_at
                ]);

                // If the scenery is a included one for MSFS2020, let's dupe it into the MSFS2024 as well
                if($scenery->payware == -1){
                    $msfs2024->sceneries()->attach($scenery->id, [
                        'link' => $scenery->link,
                        'payware' => $scenery->payware,
                        'published' => $scenery->published,
                        'source' => $source,
                        'suggested_by_user_id' => $scenery->suggested_by_user_id,
                        'created_at' => $scenery->created_at,
                        'updated_at' => $scenery->updated_at
                    ]);
                }
            }

        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Breaking change
    }
};
