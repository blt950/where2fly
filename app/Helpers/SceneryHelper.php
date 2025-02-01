<?php

namespace App\Helpers;

class SceneryHelper
{
    public static function findOfficialOrMarketStore($fsacSceneries, $developer)
    {
        $fsacDeveloperScenery = $fsacSceneries->firstWhere('developer', $developer);
        $stores = collect($fsacDeveloperScenery->prices)->where('isDeveloper', true)
            ?? collect($fsacDeveloperScenery->prices)->where(fn ($price) => collect(['simmarket.com', 'aerosoft.com', 'orbxdirect.com', 'flightsim.to'])->contains(fn ($domain) => strpos($price->link, $domain) !== false));

        if (! $stores || $stores->count() === 0) {
            return false;
        }

        return $stores;
    }

    /**
     * Attach the correct stores to correct simulator versions
     */
    public static function attachSimulators($stores, $supportedSimulators, $sceneryModel, $published = true)
    {
        foreach ($stores as $store) {
            foreach ($supportedSimulators as $supportedSim) {
                if (in_array($supportedSim->shortened_name, $store->simulatorVersions)) {
                    $sceneryModel->simulators()->attach($supportedSim, [
                        'link' => SceneryHelper::getEmbeddedUrl($store->link),
                        'payware' => $store->currencyPrice->EUR > 0,
                        'published' => $published,
                        'source' => 'fsaddoncompare',
                    ]);
                }
            }
        }
    }

    /**
     * Function to prepare scenery data
     */
    public static function prepareSceneryData($scenery, $store = null, $simulator = null)
    {
        return [
            'id' => $scenery->id ?? null,
            'developer' => $scenery->developer,
            'link' => isset($simulator->pivot) ? $simulator->pivot->link : null,
            'linkDomain' => $store ? null : parse_url($simulator->pivot->link, PHP_URL_HOST),
            'currencyLink' => $store->currencyLink ?? null,
            'cheapestLink' => $store->link ?? $simulator->pivot->link,
            'cheapestStore' => $store->store ?? $simulator->pivot->developer,
            'cheapestPrice' => $store->currencyPrice ?? null,
            'ratingAverage' => $scenery->ratingAverage ?? null,
            'payware' => (int) ($store ? $store->currencyPrice->EUR > 0 : $scenery->payware),
            'fsac' => (bool) $store,
        ];
    }

    /**
     * Sort the sceneries within each simulator.
     */
    public static function sortSceneries(array &$returnData)
    {
        foreach ($returnData as $simulator => $sceneries) {
            // First sort by developer name
            usort($sceneries, fn ($a, $b) => $a['developer'] <=> $b['developer']);
            // Then sort by payware/free
            usort($sceneries, fn ($a, $b) => $a['payware'] <=> $b['payware']);
            $returnData[$simulator] = $sceneries;
        }
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
        $embeddedUrl = isset($queryParams['url']) ? $queryParams['url'] : null;

        // Strip 'www.' and 'secure.' and addoncompare from the URL
        if ($embeddedUrl) {
            $embeddedUrl = str_replace(['www.', 'secure.'], '', $embeddedUrl);
            $embeddedUrl = str_replace('?ref=fsaddoncompare', '', $embeddedUrl);
        }

        return $embeddedUrl;
    }
}
