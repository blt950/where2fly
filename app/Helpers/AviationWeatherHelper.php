<?php

namespace App\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class AviationWeatherHelper
{
    /**
     * Download a gzipped aviationweather.gov bulk cache file and unpack it to a
     * scratch path. Returns both file paths — the caller is responsible for
     * deleting them via cleanup() once the database write has succeeded, as
     * nothing from this pipeline is meant to be retained on the filesystem.
     *
     * @return array{gz: string, xml: string}
     */
    public static function downloadCache(string $url): array
    {
        $directory = storage_path('app/tmp');
        File::ensureDirectoryExists($directory);

        $gzPath = $directory . '/' . basename($url);
        $xmlPath = substr($gzPath, 0, -strlen('.gz'));

        $response = Http::timeout(60)->retry(3, 1000)->sink($gzPath)->get($url);
        if (! $response->successful()) {
            throw new RuntimeException('Failed to download ' . $url . ' (HTTP ' . $response->status() . ')');
        }

        $gz = gzopen($gzPath, 'rb');
        $xml = fopen($xmlPath, 'wb');
        while (! gzeof($gz)) {
            fwrite($xml, gzread($gz, 1024 * 1024));
        }
        gzclose($gz);
        fclose($xml);

        return ['gz' => $gzPath, 'xml' => $xmlPath];
    }

    public static function cleanup(array $paths): void
    {
        foreach ($paths as $path) {
            File::delete($path);
        }
    }
}
