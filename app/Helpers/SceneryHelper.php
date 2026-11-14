<?php

namespace App\Helpers;

use App\Models\Airport;
use App\Models\SceneryDeveloper;
use App\Models\Simulator;

class SceneryHelper
{
    /**
     * Find the official or market store link for a scenery.
     */
    public static function findOfficialOrMarketStore($stores, $compatibleSimulator)
    {
        $officialOrMarketStoreLink = null;

        foreach ($stores as $store) {

            // Check if simulator is compatible
            if ($store->simulatorVersions == null || empty($store->simulatorVersions)) {
                continue;
            }

            if (in_array($compatibleSimulator, $store->simulatorVersions)) {
                // If the store is the developer, return the link
                if ($store->isDeveloper) {
                    $officialOrMarketStoreLink = SceneryHelper::getEmbeddedUrl($store->link);
                    break;
                }

                // If the store is a known official or market store, return the link
                if (collect(['simmarket.com', 'aerosoft.com', 'orbxdirect.com', 'flightsim.to', 'contrail.shop'])->contains(function ($domain) use ($store) {
                    return strpos($store->link, $domain) !== false;
                })) {
                    $officialOrMarketStoreLink = SceneryHelper::getEmbeddedUrl($store->link);
                    break;
                }
            }
        }

        return $officialOrMarketStoreLink;
    }

    /**
     * Find the cheapest store for a scenery.
     */
    public static function findCheapestStore($stores, $compatibleSimulator)
    {
        $cheapestStore = null;
        foreach ($stores as $store) {

            if ($store->simulatorVersions == null || empty($store->simulatorVersions)) {
                continue;
            }

            if (in_array($compatibleSimulator, $store->simulatorVersions)) {
                if ($cheapestStore === null || $store->currencyPrice->EUR < $cheapestStore->currencyPrice->EUR) {
                    $cheapestStore = $store;
                }
            }
        }

        return $cheapestStore;
    }

    /**
     * Function to prepare scenery data
     */
    public static function prepareSceneryData($sceneryDeveloperData, $sceneryData, $apiData = null)
    {
        return [
            'id' => $sceneryData->id,
            'developer' => $sceneryDeveloperData->developer,
            'link' => $apiData['link'] ?? $sceneryData->link,
            'linkDomain' => isset($apiData) ? null : parse_url($sceneryData->link, PHP_URL_HOST),
            'cheapestStore' => $apiData['cheapestStore'] ?? $sceneryDeveloperData->developer,
            'cheapestPrice' => $apiData['cheapestPrice'] ?? null,
            'ratingAverage' => $apiData['ratingAverage'] ?? null,
            'payware' => $apiData['payware'] ?? $sceneryData->payware,
            'fsac' => (bool) $apiData != null,
        ];
    }

    /**
     * Only the source references seen in the current API response are excluded, never every
     * mirrored row: a product FSAddonCompare has delisted must still fall back to our copy.
     * $excludeLinks ([simulator_id => normalized links]) drops local rows FSAC just listed under another source;
     * $excludePaywareDevelopers ([simulator_id => lowercased names]) drops local payware FSAC already lists by developer.
     */
    public static function fetchW2fSceneries(&$returnData, $airportIcao, array $excludeSourceReferenceIds = [], array $excludeLinks = [], array $excludePaywareDevelopers = [])
    {
        $w2fDevelopers = SceneryDeveloper::where('icao', $airportIcao)->with(['sceneries' => function ($query) {
            $query->where('published', true);
        }, 'sceneries.simulator'])->get();
        foreach ($w2fDevelopers as $sceneryDeveloperModel) {

            // source_reference_id holds FSAC and flightsim.to ids alike, so the id check is source-scoped
            $sceneryModels = $sceneryDeveloperModel->sceneries->reject(fn ($sceneryModel) => ($sceneryModel->source === 'fsaddoncompare' && in_array($sceneryModel->source_reference_id, $excludeSourceReferenceIds))
                || in_array(self::normalizeLink($sceneryModel->link), $excludeLinks[$sceneryModel->simulator_id] ?? [], true)
                || ((int) $sceneryModel->payware === 1 && in_array(mb_strtolower($sceneryDeveloperModel->developer), $excludePaywareDevelopers[$sceneryModel->simulator_id] ?? [], true)));

            foreach ($sceneryModels as $sceneryModel) {
                $returnData[$sceneryModel->simulator->shortened_name][] = SceneryHelper::prepareSceneryData($sceneryDeveloperModel, $sceneryModel);
            }
        }
    }

    /**
     * Sort the sceneries within each simulator.
     */
    public static function sortSceneries(array &$returnData)
    {
        foreach ($returnData as $simulator => $sceneries) {
            // First sort by developer name
            usort($sceneries, fn ($a, $b) => $a['developer'] <=> $b['developer']);
            // Then sort by payware/free: -1 (bundled) on top, then payware, then freeware
            usort($sceneries, function ($a, $b) {
                if ($a['payware'] === -1 && $b['payware'] !== -1) {
                    return -1;
                }
                if ($a['payware'] !== -1 && $b['payware'] === -1) {
                    return 1;
                }

                return $b['payware'] <=> $a['payware'];
            });
            $returnData[$simulator] = $sceneries;
        }

        // Finally, order the simulator groups themselves by the simulators table "order" column
        $simulatorOrder = Simulator::pluck('order', 'shortened_name');
        uksort($returnData, fn ($a, $b) => ($simulatorOrder[$a] ?? PHP_INT_MAX) <=> ($simulatorOrder[$b] ?? PHP_INT_MAX));
    }

    /**
     * The flightsim.to addon id in a link (`/addon/{id}/…`, or the legacy `/file/{id}/…`), if any.
     */
    public static function flightsimAddonId(?string $link): ?int
    {
        return $link && preg_match('~flightsim\.to/(?:addon|file)/(\d+)~i', $link, $match) ? (int) $match[1] : null;
    }

    /**
     * Comparable form of a store link. flightsim.to links collapse to their addon id, since the
     * same addon appears under /addon/ and /file/ paths with differing slugs.
     */
    public static function normalizeLink(?string $link): ?string
    {
        if (! $link) {
            return null;
        }

        if ($addonId = self::flightsimAddonId($link)) {
            return 'flightsim.to/addon/' . $addonId;
        }

        $host = preg_replace('/^www\./', '', strtolower((string) parse_url($link, PHP_URL_HOST)));

        return $host . rtrim((string) parse_url($link, PHP_URL_PATH), '/');
    }

    /**
     * Every airport by ident and in 1°×1° cells. The flag marks ICAO-shaped idents (4 alphanumerics),
     * which the nearest-airport fallback prefers over local idents like `09J` or `RO-0017`.
     *
     * @return array{byIcao: array<string, array{0: int, 1: float, 2: float, 3: bool}>, grid: array<string, list<string>>}
     */
    public static function airportIndex(): array
    {
        $index = ['byIcao' => [], 'grid' => []];
        $airports = Airport::query()->toBase()
            ->select('id', 'icao')
            ->selectRaw('ST_Latitude(coordinates) AS lat, ST_Longitude(coordinates) AS lon')
            ->cursor();

        foreach ($airports as $airport) {
            if ($airport->lat === null || $airport->lon === null || $airport->icao === null || $airport->icao === '') {
                continue;
            }
            $index['byIcao'][$airport->icao] = [(int) $airport->id, (float) $airport->lat, (float) $airport->lon, self::isIcaoShaped($airport->icao)];
            $index['grid'][self::gridKey(floor($airport->lat), floor($airport->lon))][] = $airport->icao;
        }

        return $index;
    }

    public static function isIcaoShaped(string $ident): bool
    {
        return (bool) preg_match('/^[A-Z0-9]{4}$/', $ident);
    }

    /**
     * Match a scenery to an airport: an explicit code first, then an ICAO-looking title token near
     * the scenery's coordinates (rejects words like PEAK), then simply the nearest airport.
     *
     * @return array{id: int, icao: string}|null
     */
    public static function resolveAirport(?string $code, string $title, float $lat, float $lon, array $index, float $titleRadiusNm = 5, float $nearestRadiusNm = 2): ?array
    {
        $byIcao = $index['byIcao'];

        $code = strtoupper(trim((string) $code));
        if ($code !== '' && isset($byIcao[$code])) {
            return ['id' => $byIcao[$code][0], 'icao' => $code];
        }

        $best = null;
        preg_match_all('/\b[A-Z0-9]{4}\b/u', mb_strtoupper($title), $tokens);
        foreach (array_unique($tokens[0]) as $token) {
            if (! isset($byIcao[$token])) {
                continue;
            }
            $distance = self::distanceNm($lat, $lon, $byIcao[$token][1], $byIcao[$token][2]);
            if ($distance <= $titleRadiusNm && ($best === null || $distance < $best[1])) {
                $best = [$token, $distance];
            }
        }

        if ($best === null) {
            // Nearest ICAO-shaped ident first, so a heliport or local strip id can't steal a real airport
            $nearest = [true => null, false => null];
            $cellLat = floor($lat);
            $cellLon = floor($lon);
            for ($dLat = -1; $dLat <= 1; $dLat++) {
                for ($dLon = -1; $dLon <= 1; $dLon++) {
                    foreach ($index['grid'][self::gridKey($cellLat + $dLat, $cellLon + $dLon)] ?? [] as $icao) {
                        $tier = $byIcao[$icao][3] ?? self::isIcaoShaped($icao);
                        $distance = self::distanceNm($lat, $lon, $byIcao[$icao][1], $byIcao[$icao][2]);
                        if ($distance <= $nearestRadiusNm && ($nearest[$tier] === null || $distance < $nearest[$tier][1])) {
                            $nearest[$tier] = [$icao, $distance];
                        }
                    }
                }
            }
            $best = $nearest[true] ?? $nearest[false];
        }

        return $best ? ['id' => $byIcao[$best[0]][0], 'icao' => $best[0]] : null;
    }

    /**
     * Equirectangular approximation — accurate to well under 1% at the few-nm radii it's used for.
     */
    public static function distanceNm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $dLon = fmod($lon2 - $lon1 + 540, 360) - 180;
        $x = $dLon * cos(deg2rad(($lat1 + $lat2) / 2));

        return sqrt($x ** 2 + ($lat2 - $lat1) ** 2) * 60;
    }

    private static function gridKey(float $cellLat, float $cellLon): string
    {
        // Wrap so cells either side of the antimeridian are neighbours
        $cellLon = fmod($cellLon + 180 + 360, 360) - 180;

        return (int) $cellLat . ':' . (int) $cellLon;
    }

    public static function getEmbeddedUrl($fullUrl)
    {
        // First, decode the URL (if necessary)
        $decodedUrl = urldecode($fullUrl);

        // Parse the query part of the URL
        $urlComponents = parse_url($decodedUrl);

        // Parse the query string into an associative array
        parse_str($urlComponents['query'], $queryParams);

        // Retrieve the 'url' parameter value
        $embeddedUrl = $queryParams['url'] ?? null;

        // Strip 'www.' and 'secure.' and addoncompare from the URL
        if ($embeddedUrl) {
            $embeddedUrl = str_replace(['www.'], '', $embeddedUrl);
            $embeddedUrl = str_replace('?ref=fsaddoncompare', '', $embeddedUrl);
        }

        return $embeddedUrl;
    }
}
