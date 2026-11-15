<?php

namespace Tests\Unit;

use App\Models\AirportScore;
use App\View\ScoreIcon;
use PHPUnit\Framework\TestCase;

class ScoreIconTest extends TestCase
{
    public function test_to_array_carries_what_the_blade_view_renders(): void
    {
        $score = new AirportScore([
            'reason' => 'METAR_WINDY',
            'source' => AirportScore::SOURCE_TAF,
            'score' => 0.7,
            'data' => ['tempo' => true],
        ]);

        $this->assertSame([
            'reason' => 'METAR_WINDY',
            'icon' => 'fa-windsock',
            'tooltipLines' => ['Windy', 'Temporary condition'],
            'probabilityBadge' => true,
            'uncertain' => true,
            'facilityDotCount' => 0,
        ], (new ScoreIcon($score))->toArray());
    }

    public function test_tooltip_lines_stay_escaped(): void
    {
        $score = new AirportScore([
            'reason' => 'VATSIM_POPULAR',
            'source' => AirportScore::SOURCE_VATSIM,
            'score' => 1,
            'data' => ['movements' => '<b>3</b>'],
        ]);

        $this->assertContains('&lt;b&gt;3&lt;/b&gt; aircraft in vicinity', (new ScoreIcon($score))->toArray()['tooltipLines']);
    }
}
