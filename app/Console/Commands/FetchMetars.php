<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;
use App\Models\Metar;

class FetchMetars extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:metars';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch all latest METARs from VATSIM';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $response = Http::get('https://metar.vatsim.net?id=all');
        if($response->successful()){
            $data = collect(preg_split("/\r\n|\n|\r/", $response->body()));

            foreach($data as $d){
                $icao = substr($d, 0, 4);
                $time = Carbon::now()->setHour(substr($d, 7, 2))->setMinute(substr($d, 9, 2))->setSeconds(0);
                $metar = substr($d, 13, null);

                Metar::updateOrCreate(['icao' => $icao],[
                    'last_update' => $time,
                    'metar' => $metar
                ]);
            }
            
        }

        return 0;
    }
}
