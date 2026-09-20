<?php

namespace Tests\Unit;

use App\Console\Commands\FetchMetars;
use App\Console\Commands\FetchTafs;
use App\Exceptions\WeatherCacheUnavailableException;
use ErrorException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class WeatherCacheParsingTest extends TestCase
{
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $path) {
            @unlink($path);
        }

        restore_error_handler();

        parent::tearDown();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Stand in for Laravel's HandleExceptions: any leaked libxml warning becomes
        // an ErrorException here, which is exactly the production failure (WHERE2FLY-8Y)
        set_error_handler(function (int $severity, string $message, string $file, int $line) {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });
    }

    public static function malformedProvider(): array
    {
        return [
            'empty' => [''],
            'whitespace only' => ["\n  \n"],
            'html error page' => ['<!DOCTYPE html><html><body>503</body></html>'],
            'truncated mid element' => ['<response><data num_results="1"><TAF><station_id>EGLL'],
            'not xml at all' => ['upstream temporarily unavailable'],
        ];
    }

    #[DataProvider('malformedProvider')]
    public function test_taf_parser_rejects_malformed_documents_without_raising_warnings(string $contents): void
    {
        $this->expectException(WeatherCacheUnavailableException::class);

        $this->parseTafs($this->tempFile($contents));
    }

    #[DataProvider('malformedProvider')]
    public function test_metar_parser_rejects_malformed_documents_without_raising_warnings(string $contents): void
    {
        $this->expectException(WeatherCacheUnavailableException::class);

        $this->parseMetars($this->tempFile($contents));
    }

    public function test_taf_parser_rejects_a_well_formed_document_with_no_taf_data(): void
    {
        $this->expectException(WeatherCacheUnavailableException::class);

        $this->parseTafs($this->tempFile('<response><errors><error>No data</error></errors></response>'));
    }

    public function test_taf_parser_reads_a_valid_document(): void
    {
        $from = gmdate('Y-m-d\TH:i:s\Z', time() + 3600);
        $to = gmdate('Y-m-d\TH:i:s\Z', time() + 21600);

        $documents = $this->parseTafs($this->tempFile(
            '<response><data num_results="1"><TAF>' .
            '<raw_text>TAF EGLL 041100Z 0412/0518 24010KT 9999 SCT030</raw_text>' .
            '<station_id>EGLL</station_id>' .
            '<issue_time>' . gmdate('Y-m-d\TH:i:s\Z') . '</issue_time>' .
            '<forecast><fcst_time_from>' . $from . '</fcst_time_from><fcst_time_to>' . $to . '</fcst_time_to>' .
            '<wind_speed_kt>10</wind_speed_kt>' .
            '<sky_condition sky_cover="SCT" cloud_base_ft_agl="3000"/>' .
            '</forecast></TAF></data></response>'
        ));

        $this->assertArrayHasKey('EGLL', $documents);
        $this->assertSame('041100Z 0412/0518 24010KT 9999 SCT030', $documents['EGLL']['raw_text']);
        $this->assertCount(1, $documents['EGLL']['periods']);
    }

    public function test_metar_parser_reads_a_valid_document(): void
    {
        $observations = $this->parseMetars($this->tempFile(
            '<response><data num_results="1"><METAR>' .
            '<raw_text>METAR EGLL 041120Z 24010KT 9999 SCT030 15/09 Q1013</raw_text>' .
            '<station_id>EGLL</station_id>' .
            '<observation_time>' . gmdate('Y-m-d\TH:i:s\Z') . '</observation_time>' .
            '<wind_dir_degrees>240</wind_dir_degrees><wind_speed_kt>10</wind_speed_kt>' .
            '<temp_c>15.0</temp_c>' .
            '</METAR></data></response>'
        ));

        $this->assertArrayHasKey('EGLL', $observations);
        $this->assertSame('041120Z 24010KT 9999 SCT030 15/09 Q1013', $observations['EGLL']['metar']);
        $this->assertSame(240, $observations['EGLL']['wind_direction']);
        $this->assertSame(15, $observations['EGLL']['temperature']);
    }

    public function test_metar_parser_keeps_variable_wind_direction_null(): void
    {
        $observations = $this->parseMetars($this->tempFile(
            '<response><data num_results="1"><METAR>' .
            '<raw_text>METAR EGLL 041120Z VRB03KT 9999 SCT030 15/09 Q1013</raw_text>' .
            '<station_id>EGLL</station_id>' .
            '<observation_time>' . gmdate('Y-m-d\TH:i:s\Z') . '</observation_time>' .
            '<wind_dir_degrees>VRB</wind_dir_degrees><wind_speed_kt>3</wind_speed_kt>' .
            '<temp_c>15.0</temp_c>' .
            '</METAR></data></response>'
        ));

        $this->assertNull($observations['EGLL']['wind_direction']);
    }

    private function parseTafs(string $path): array
    {
        $method = new ReflectionMethod(FetchTafs::class, 'parseTafDocuments');

        return $method->invoke(new FetchTafs, $path);
    }

    private function parseMetars(string $path): array
    {
        $method = new ReflectionMethod(FetchMetars::class, 'parseMetarNodes');

        return $method->invoke(new FetchMetars, $path);
    }

    private function tempFile(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'awc') . '.xml';
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }
}
