<?php

namespace App\Services\Msfs;

use Closure;

/**
 * Turns the packages of an MSFS 2024 Content.xml into a suggested scenery list. It knows no HTTP
 * and no database: `$lookup` answers which airport codes exist.
 */
class SceneryPackageClassifier
{
    private const CATEGORY_RANK = ['native' => 1, 'marketplace' => 2, 'community' => 3];

    /**
     * @param  array{package_overrides: array<string, string>, ignore_patterns: list<string>, token_denylist: list<string>}  $config
     * @param  Closure(list<string>): array<string, string>  $lookup  uppercase codes in, [code => the airport's ICAO] out for those that exist
     */
    public function __construct(private array $config, private Closure $lookup) {}

    /**
     * @param  iterable<array{name: ?string, active: ?string}>  $packages
     */
    public function classify(iterable $packages, bool $includeNative): array
    {
        $stats = ['packages_total' => 0, 'enabled' => 0, 'disabled_by_state' => [], 'duplicates_collapsed' => 0, 'skipped_incomplete' => 0,
            'ignored' => 0, 'native_unresolved' => 0, 'native_only_available' => 0, 'states_seen' => []];

        // Assumption: when duplicates disagree the last entry wins, as the sim gives later entries priority
        $latest = [];
        foreach ($packages as $package) {
            $stats['packages_total']++;
            $name = trim((string) ($package['name'] ?? ''));
            $state = trim((string) ($package['active'] ?? ''));

            if ($name === '' || $state === '') {
                $stats['skipped_incomplete']++;

                continue;
            }

            $key = mb_strtolower($name);
            if (isset($latest[$key])) {
                $stats['duplicates_collapsed']++;
            }
            $latest[$key] = ['package' => $name, 'state' => $state];
        }

        $states = [];
        $entries = [];
        foreach ($latest as $key => ['package' => $name, 'state' => $state]) {
            $label = $states[strtolower($state)] ??= $state;

            // Only "Activated" means enabled; SystemDisabled, UserDisabled and anything new do not
            if (strtolower($state) !== 'activated') {
                $stats['disabled_by_state'][$label] = ($stats['disabled_by_state'][$label] ?? 0) + 1;

                continue;
            }

            $stats['enabled']++;
            $entry = $this->classifyName($key);

            if ($entry === null) {
                $stats['ignored']++;
            } else {
                $entries[] = ['package' => $name] + $entry;
            }
        }

        sort($states);
        $stats['states_seen'] = array_values($states);

        $codes = [];
        foreach ($entries as $entry) {
            if ($entry['candidate'] !== null) {
                $codes[$entry['candidate']] = true;
            }
            foreach ($entry['tokens'] as $token) {
                $codes[$token] = true;
            }
        }
        $known = $codes === [] ? [] : ($this->lookup)(array_map('strval', array_keys($codes)));

        $airports = [];
        $errors = [];
        $guessed = [];
        $unresolved = [];

        foreach ($entries as ['package' => $name, 'category' => $category, 'candidate' => $candidate, 'tokens' => $tokens]) {
            if ($candidate !== null) {
                if (! preg_match('/^[A-Z0-9]{3,4}$/', $candidate)) {
                    $errors[] = ['package' => $name, 'icao' => $candidate, 'reason' => 'malformed_icao', 'category' => $category];
                } elseif (! isset($known[$candidate])) {
                    $errors[] = ['package' => $name, 'icao' => $candidate, 'reason' => 'icao_not_in_database', 'category' => $category];
                } else {
                    $airports[$known[$candidate]][] = ['package' => $name, 'category' => $category];
                }

                continue;
            }

            if ($category === 'native') {
                $stats['native_unresolved']++;

                continue;
            }

            $hits = array_values(array_filter($tokens, fn ($token) => isset($known[$token])));
            if ($hits !== []) {
                // A guess is never trusted into the suggestion; the user confirms it
                $guessed[] = ['package' => $name, 'suggested_icao' => $known[$hits[0]], 'reason' => 'icao_guessed_from_name', 'category' => $category];
            } else {
                $unresolved[] = ['package' => $name, 'suggested_icao' => null, 'reason' => 'no_icao_found', 'category' => $category];
            }
        }

        ksort($airports, SORT_STRING);
        $listed = [];
        foreach ($airports as $icao => $contributions) {
            $categories = array_column($contributions, 'category');
            usort($categories, fn ($a, $b) => self::CATEGORY_RANK[$b] <=> self::CATEGORY_RANK[$a]);
            $category = $categories[0];

            if ($category === 'native' && ! $includeNative) {
                $stats['native_only_available']++;

                continue;
            }

            $listed[] = [
                'icao' => (string) $icao,
                'category' => $category,
                'also_native' => $category !== 'native' && in_array('native', $categories, true),
                'packages' => array_column($contributions, 'package'),
            ];
        }

        return [
            'suggestion' => implode("\n", array_column($listed, 'icao')),
            'count' => count($listed),
            'include_native' => $includeNative,
            'airports' => $listed,
            'errors' => $errors,
            'needs_review' => [...$guessed, ...$unresolved],
            'stats' => $stats,
        ];
    }

    /**
     * Null when the package is not scenery. `candidate` is a trusted ICAO, `tokens` are only guesses.
     *
     * @return array{category: string, candidate: ?string, tokens: list<string>}|null
     */
    private function classifyName(string $name): ?array
    {
        $category = match (true) {
            (bool) preg_match('/^communityfs\d{2}-/', $name) => 'community',
            (bool) preg_match('/^fs\d{2}-fs-/', $name) => null,
            (bool) preg_match('/^fs\d{2}-(asobo|microsoft)-/', $name) => 'native',
            (bool) preg_match('/^fs\d{2}-/', $name) => 'marketplace',
            default => null,
        };

        if (isset($this->config['package_overrides'][$name])) {
            return ['category' => $category ?? 'community', 'candidate' => strtoupper(trim($this->config['package_overrides'][$name])), 'tokens' => []];
        }

        foreach ($this->config['ignore_patterns'] as $pattern) {
            if (str_contains($name, $pattern)) {
                return null;
            }
        }

        if ($category === null) {
            return null;
        }

        if (preg_match('/-airport-([a-z0-9]{3,4})(?:-|$)/', $name, $match)) {
            return ['category' => $category, 'candidate' => strtoupper($match[1]), 'tokens' => []];
        }

        // Four-letter tokens first, then the first four letters of longer ones ("zudcsat")
        $exact = [];
        $prefixes = [];
        if ($category !== 'native') {
            foreach (array_slice(preg_split('/[-_\s]+/', $name), 1) as $token) {
                if (! preg_match('/^[a-z]{4,}$/', $token)) {
                    continue;
                }
                $code = substr($token, 0, 4);
                if (! in_array($code, $this->config['token_denylist'], true)) {
                    strlen($token) === 4 ? $exact[] = strtoupper($code) : $prefixes[] = strtoupper($code);
                }
            }
        }

        return ['category' => $category, 'candidate' => null, 'tokens' => array_values(array_unique([...$exact, ...$prefixes]))];
    }
}
