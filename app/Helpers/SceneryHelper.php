<?php

namespace App\Helpers;

use App\Models\SceneryDeveloper;

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
                if ($cheapestStore == null) {
                    $cheapestStore = $store;
                }
                if ($store->currencyPrice->EUR < $cheapestStore->currencyPrice->EUR) {
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
            'currencyLink' => $apiData['currencyLink'] ?? null,
            'cheapestLink' => $apiData['cheapestLink'] ?? $sceneryData->link,
            'cheapestStore' => $apiData['cheapestStore'] ?? $sceneryDeveloperData->developer,
            'cheapestPrice' => $apiData['cheapestPrice'] ?? null,
            'ratingAverage' => $apiData['ratingAverage'] ?? null,
            'payware' => $apiData['payware'] ?? $sceneryData->payware,
            'fsac' => (bool) $apiData != null,
        ];
    }

    public static function fetchW2fSceneries(&$returnData, $airportIcao, $whereNotSourceReference = false)
    {
        $w2fDevelopers = SceneryDeveloper::where('icao', $airportIcao)->with(['sceneries' => function ($query) {
            $query->where('published', true);
        }, 'sceneries.simulator'])->get();
        foreach ($w2fDevelopers as $sceneryDeveloperModel) {

            $sceneryModels = $sceneryDeveloperModel->sceneries;
            if ($whereNotSourceReference) {
                $sceneryModels = $sceneryModels->whereNull('source_reference_id');
            }

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
            $embeddedUrl = str_replace(['www.'], '', $embeddedUrl);
            $embeddedUrl = str_replace('?ref=fsaddoncompare', '', $embeddedUrl);
        }

        return $embeddedUrl;
    }
}
