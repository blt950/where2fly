<?php

namespace App\Helpers;

use App\Exceptions\WeatherCacheUnavailableException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

class AviationWeatherHelper
{
    /**
     * Download a gzipped aviationweather.gov bulk cache file and unpack it to a
     * scratch path. Returns both file paths — the caller is responsible for
     * deleting them via cleanup() once the database write has succeeded, as
     * nothing from this pipeline is meant to be retained on the filesystem.
     *
     * @return array{gz: string, xml: string}
     *
     * @throws WeatherCacheUnavailableException
     */
    public static function downloadCache(string $url): array
    {
        $directory = storage_path('app/tmp');
        File::ensureDirectoryExists($directory);

        $gzPath = $directory . '/' . basename($url);
        $xmlPath = substr($gzPath, 0, -strlen('.gz'));
        $paths = ['gz' => $gzPath, 'xml' => $xmlPath];

        $response = Http::timeout(60)->retry(3, 1000)->sink($gzPath)->get($url);
        if (! $response->successful()) {
            self::fail($paths, 'Failed to download ' . $url . ' (HTTP ' . $response->status() . ')');
        }

        $gz = @gzopen($gzPath, 'rb');
        if ($gz === false) {
            self::fail($paths, 'Could not open the downloaded archive for ' . $url);
        }

        $xml = @fopen($xmlPath, 'wb');
        if ($xml === false) {
            gzclose($gz);
            self::fail($paths, 'Could not write the unpacked cache for ' . $url);
        }

        while (! gzeof($gz)) {
            $buffer = @gzread($gz, 1024 * 1024);

            // A truncated or non-gzip body stops mid-stream — better to bail than
            // hand the caller a half-written document that parses to nonsense
            if ($buffer === false) {
                gzclose($gz);
                fclose($xml);
                self::fail($paths, 'Corrupt archive while unpacking ' . $url);
            }

            fwrite($xml, $buffer);
        }
        gzclose($gz);
        fclose($xml);

        self::assertLooksLikeXml($paths, $url);

        return $paths;
    }

    public static function cleanup(array $paths): void
    {
        foreach ($paths as $path) {
            File::delete($path);
        }
    }

    /**
     * Upstream occasionally serves an empty body or an HTML error page behind a
     * 200 — catching it here keeps the failure out of the XML parsers, where it
     * would surface as a libxml warning turned ErrorException.
     */
    private static function assertLooksLikeXml(array $paths, string $url): void
    {
        if (! File::exists($paths['xml']) || File::size($paths['xml']) === 0) {
            self::fail($paths, 'Empty cache file received from ' . $url);
        }

        $handle = @fopen($paths['xml'], 'rb');
        if ($handle === false) {
            self::fail($paths, 'Could not read the unpacked cache for ' . $url);
        }

        $head = ltrim((string) fread($handle, 1024));
        fclose($handle);

        if (! str_starts_with($head, '<')) {
            self::fail($paths, 'Cache file from ' . $url . ' is not XML');
        }
    }

    /**
     * @throws WeatherCacheUnavailableException
     */
    private static function fail(array $paths, string $message): never
    {
        self::cleanup($paths);

        throw new WeatherCacheUnavailableException($message);
    }
}
