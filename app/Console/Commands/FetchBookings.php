<?php

namespace App\Console\Commands;

use App\Helpers\AirportCallsignHelper;
use App\Models\Airport;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchBookings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:bookings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch ATC bookings from VATSIM for the next 24 hours';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $processTime = microtime(true);
        $this->info('Fetching VATSIM ATC bookings');

        $response = Http::timeout(60)->retry(3, 1000)->get('https://atc-bookings.vatsim.net/api/booking');
        if (! $response->successful()) {
            $this->error('Fetching of bookings failed with HTTP ' . $response->status());

            return Command::FAILURE;
        }

        // Only the id is used from these maps — don't load full models for all airports
        $airportsByIcao = Airport::select('id', 'icao', 'local_code')->get()->keyBy('icao');
        $airportsByLocalCode = $airportsByIcao->filter(fn ($airport) => ! empty($airport->local_code))->keyBy('local_code');

        $upsertData = [];
        $seenBookingIds = [];
        $horizon = now()->addHours(24);
        foreach ($response->object() as $booking) {

            // The API returns naive datetimes with no offset — they are UTC
            $start = Carbon::parse($booking->start, 'UTC');
            $end = Carbon::parse($booking->end, 'UTC');

            // Only store the next 24 hours of bookings, matching the practical horizon of a search's ETA
            if ($start->gt($horizon) || $end->lte(now())) {
                continue;
            }

            // A booking that can't be resolved to exactly one airport is dropped, never stored with a null FK
            $icao = AirportCallsignHelper::resolveIcao($booking->callsign);
            $airport = $icao ? ($airportsByIcao[$icao] ?? $airportsByLocalCode[$icao] ?? null) : null;
            if (! $airport) {
                continue;
            }

            $seenBookingIds[] = $booking->id;
            $upsertData[] = [
                'vatsim_booking_id' => $booking->id,
                'callsign' => $booking->callsign,
                'airport_id' => $airport->id,
                'division' => $booking->division ?? null,
                'subdivision' => $booking->subdivision ?? null,
                'start' => $start,
                'end' => $end,
                'last_synced_at' => now(),
            ];
        }

        foreach (array_chunk($upsertData, 500) as $chunk) {
            Booking::upsert(
                $chunk,
                ['vatsim_booking_id'],
                ['callsign', 'airport_id', 'division', 'subdivision', 'start', 'end', 'last_synced_at']
            );
        }

        // Remove bookings that were cancelled upstream or have already ended
        Booking::whereNotIn('vatsim_booking_id', $seenBookingIds)->orWhere('end', '<', now())->delete();

        $this->info('Fetching of ' . count($upsertData) . ' bookings finished in ' . round(microtime(true) - $processTime) . ' seconds');

    }
}
