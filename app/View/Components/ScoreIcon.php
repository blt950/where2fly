<?php

namespace App\View\Components;

use App\Http\Controllers\ScoreController;
use App\Models\Airport;
use App\Models\AirportScore;
use Illuminate\Support\Collection;
use Illuminate\View\Component;
use Illuminate\View\View;

class ScoreIcon extends Component
{
    public array $scoreType;

    /** Pre-escaped tooltip lines — section headers carry markup */
    public array $tooltipLines;

    public bool $probabilityBadge;

    public Collection $facilityDots;

    public function __construct(
        public AirportScore $score,
        public ?Airport $airport = null,
        public bool $highlighted = false,
        public string $bookingsLabel = 'Bookings',
    ) {
        $this->scoreType = ScoreController::$score_types[$score->reason];
        $this->probabilityBadge = isset($score->data['probability']) || ! empty($score->data['tempo']);
        $this->facilityDots = $score->reason === 'VATSIM_ATC' && $airport ? $airport->atcFacilities() : collect();
        $this->tooltipLines = $this->buildTooltipLines();
    }

    /**
     * Description, forecast uncertainty on its own line, then the per-source
     * detail — for VATSIM_ATC the online stations and booked positions,
     * otherwise the row's own text.
     */
    private function buildTooltipLines(): array
    {
        $lines = [e($this->scoreType['desc'])];

        if (isset($this->score->data['probability'])) {
            $lines[] = e($this->score->data['probability'] . '% probability');
        } elseif (! empty($this->score->data['tempo'])) {
            $lines[] = 'Temporary condition';
        }

        if ($this->score->reason === 'VATSIM_ATC' && $this->airport) {
            return array_merge($lines, $this->atcLines());
        }

        if ($text = $this->score->tooltipText()) {
            $lines[] = e($text);
        }

        return $lines;
    }

    private function atcLines(): array
    {
        $lines = [];
        $onlineStations = $this->airport->atcOnlineStations();
        $bookingScores = $this->airport->atcBookingScores();

        if ($onlineStations->count()) {
            $lines[] = '<b>Online</b>';
            foreach ($onlineStations as $station) {
                $lines[] = e(AirportScore::onlineForText($station['facility'], $station['logon_time']));
            }
        }

        if ($bookingScores->count()) {
            $lines[] = '<b>' . e($this->bookingsLabel) . '</b>';
            foreach ($bookingScores as $bookingScore) {
                $lines[] = e($bookingScore->tooltipText());
            }
        }

        // Nothing renderable above (e.g. only a CTR station) — fall back to the row's own text
        if (! $lines && ($text = $this->score->tooltipText())) {
            $lines[] = e($text);
        }

        return $lines;
    }

    public function render(): View
    {
        return view('components.score-icon');
    }
}
