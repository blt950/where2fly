<?php

namespace App\Console\Commands;

use App\Helpers\SceneryHelper;
use App\Models\Scenery;
use App\Models\SceneryDeveloper;
use App\Models\Simulator;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;

class FetchSceneries extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:sceneries {--probe-limit=50 : Max addon pages checked for deletion per run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync MSFS airport sceneries (freeware and payware) from the flightsim.to map feeds';

    public const FEED_URL = 'https://flightsim.to/map-data/new/output';

    public const ADDON_URL = 'https://flightsim.to/addon/';

    // Intl, Regional, GA, Mountain, Small island, Bush strip, Remote airstrip and Military airports
    public const CATEGORIES = [3, 46, 304, 307, 309, 87, 306, 305];

    private const COMPATIBILITY = [
        'Flight Simulator 2020' => ['MSFS2020'],
        'Flight Simulator 2024' => ['MSFS2024'],
        'Flight Simulator 2020/24' => ['MSFS2020', 'MSFS2024'],
    ];

    // A feed returning far fewer items than we already track is treated as truncated, not as mass deletion
    private const MIN_FEED_COVERAGE = 0.8;

    // flightsim.to keeps some live addons off its map, so those are re-probed at most this often
    private const RECHECK_DAYS = 30;

    private const USER_AGENT = 'Where2Fly scenery sync';

    private array $stats = [
        'created' => 0, 'adopted' => 0, 'simulators_added' => 0, 'simulators_removed' => 0, 'links_updated' => 0,
        'payware_updated' => 0, 'probed_live' => 0, 'probed_deleted' => 0, 'probed_unknown' => 0,
        'unresolved' => 0, 'unknown_compatibility' => 0,
    ];

    private ?array $airportIndex = null;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $processTime = microtime(true);

        $simulatorIds = Simulator::whereIn('shortened_name', ['MSFS2020', 'MSFS2024'])->pluck('id', 'shortened_name');
        if ($simulatorIds->count() !== 2) {
            $this->error('MSFS2020/MSFS2024 simulators are missing');

            return Command::FAILURE;
        }

        $this->info('> Fetching flightsim.to map feeds...');
        [$feed, $healthy] = $this->fetchFeeds();
        if (empty($feed)) {
            $this->error('No items fetched, aborting');

            return Command::FAILURE;
        }

        $rowsByAddon = $this->loadRows($simulatorIds->values()->all());
        $trackedAddons = $rowsByAddon->filter(fn ($rows) => $rows->contains(fn ($row) => $this->isTracked($row)))->count();
        $complete = $healthy && count($feed) >= self::MIN_FEED_COVERAGE * $trackedAddons;
        if (! $complete) {
            $this->warn('Feeds incomplete (' . count($feed) . ' items, ' . $trackedAddons . ' tracked) — skipping all deletions');
        }

        $this->info('> Syncing ' . count($feed) . ' sceneries...');
        $newRows = [];
        foreach ($feed as $addonId => $item) {
            $simulators = isset(self::COMPATIBILITY[$item['compatibility']])
                ? array_map(fn ($name) => $simulatorIds[$name], self::COMPATIBILITY[$item['compatibility']])
                : null;

            if ($rowsByAddon->has($addonId)) {
                $this->syncExisting($addonId, $item, $rowsByAddon[$addonId], $simulators, $complete);
            } elseif ($simulators === null) {
                $this->stats['unknown_compatibility']++;
            } else {
                array_push($newRows, ...$this->rowsForNewItem($addonId, $item, $simulators));
            }
        }
        foreach (array_chunk($newRows, 500) as $chunk) {
            Scenery::insert($chunk);
        }

        if ($complete) {
            $this->probeUnlisted($rowsByAddon->diffKeys($feed));
        }

        $this->info('> Done in ' . round(microtime(true) - $processTime) . ' seconds: ' . collect($this->stats)->map(fn ($count, $key) => "$key=$count")->implode(', '));

        return $complete ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * The unfiltered category feeds (payware included, flagged) by addon id.
     *
     * @return array{0: array<int, array>, 1: bool}
     */
    private function fetchFeeds(): array
    {
        $feed = [];
        $healthy = true;

        foreach (self::CATEGORIES as $category) {
            try {
                $response = Http::withUserAgent(self::USER_AGENT)->timeout(60)->retry(2, 5000, throw: false)
                    ->get(self::FEED_URL, ['category' => $category]);
            } catch (ConnectionException) {
                $response = null;
            }

            $items = $response?->successful() && $response->json('success') === true ? $response->json('data') : null;
            if (! is_array($items) || empty($items)) {
                $this->warn("Category $category feed failed" . ($response ? ' with HTTP ' . $response->status() : ''));
                $healthy = false;

                continue;
            }

            foreach ($items as $item) {
                if (! isset($item['id'], $item['link'], $item['latitude'], $item['longitude']) || ! is_numeric($item['id'])) {
                    continue;
                }

                // Only what the sync needs — the raw feeds are several MB of JSON
                $feed[(int) $item['id']] = [
                    'title' => (string) ($item['title'] ?? ''),
                    'code' => (string) ($item['code'] ?? ''),
                    'developer' => trim((string) ($item['developer'] ?? '')),
                    'latitude' => (float) $item['latitude'],
                    'longitude' => (float) $item['longitude'],
                    'compatibility' => (string) ($item['compatibility'] ?? ''),
                    'payware' => filter_var($item['is_payware'] ?? false, FILTER_VALIDATE_BOOLEAN),
                    // Any slug resolves on flightsim.to, so truncating to the column width keeps the link working
                    'link' => mb_substr((string) $item['link'], 0, 255),
                ];
            }
        }

        return [$feed, $healthy];
    }

    /**
     * Every MSFS row tied to a flightsim.to addon — by tracked reference or by its link — grouped by addon id.
     * FSAC rows are excluded: their source_reference_id is an FSAC product id.
     *
     * @return Collection<int, Collection<int, object>>
     */
    private function loadRows(array $simulatorIds): Collection
    {
        return Scenery::query()->toBase()
            ->select('id', 'scenery_developer_id', 'simulator_id', 'link', 'payware', 'published', 'source', 'source_reference_id', 'source_checked_at')
            ->whereIn('simulator_id', $simulatorIds)
            ->where(fn ($query) => $query->whereNull('source')->orWhere('source', '!=', 'fsaddoncompare'))
            ->where(fn ($query) => $query->where('link', 'like', '%flightsim.to/%')
                ->orWhere(fn ($query) => $query->where('source', 'flightsim.to')->whereNotNull('source_reference_id')))
            ->get()
            ->groupBy(fn ($row) => $row->source === 'flightsim.to' && $row->source_reference_id
                ? (int) $row->source_reference_id
                : SceneryHelper::flightsimAddonId($row->link))
            ->forget('');
    }

    private function isTracked(object $row, ?int $addonId = null): bool
    {
        return $row->source === 'flightsim.to' && $row->source_reference_id !== null
            && ($addonId === null || (int) $row->source_reference_id === $addonId);
    }

    /**
     * Adopt the addon's rows, refresh their link, and match the simulator rows to the feed's compatibility.
     * Duplicate rows for one simulator are collapsed, since the feed is now the source of truth for them.
     */
    private function syncExisting(int $addonId, array $item, Collection $rows, ?array $simulators, bool $complete): void
    {
        $now = now();

        $untracked = $rows->reject(fn ($row) => $this->isTracked($row, $addonId));
        if ($untracked->isNotEmpty()) {
            Scenery::whereIn('id', $untracked->pluck('id'))->update(['source' => 'flightsim.to', 'source_reference_id' => $addonId, 'updated_at' => $now]);
            $this->stats['adopted']++;
        }

        $staleLinks = $rows->where('link', '!=', $item['link']);
        if ($staleLinks->isNotEmpty()) {
            Scenery::whereIn('id', $staleLinks->pluck('id'))->update(['link' => $item['link'], 'source' => 'flightsim.to', 'source_reference_id' => $addonId, 'updated_at' => $now]);
            $this->stats['links_updated']++;
        }

        // -1 marks a scenery bundled with the sim, which no store flag should override
        $wrongPayware = $rows->filter(fn ($row) => (int) $row->payware !== -1 && (bool) $row->payware !== $item['payware']);
        if ($wrongPayware->isNotEmpty()) {
            Scenery::whereIn('id', $wrongPayware->pluck('id'))->update(['payware' => $item['payware'], 'source' => 'flightsim.to', 'source_reference_id' => $addonId, 'updated_at' => $now]);
            $this->stats['payware_updated']++;
        }

        if ($simulators === null) {
            return;
        }

        // Keep one row per simulator, preferring a published one, then the oldest
        $keep = $rows->sortBy(fn ($row) => [$row->published ? 0 : 1, $row->id])->unique('simulator_id');
        $surplus = $rows->diffKeys($keep)->merge($keep->whereNotIn('simulator_id', $simulators));
        if ($complete && $surplus->isNotEmpty()) {
            Scenery::whereIn('id', $surplus->pluck('id'))->delete();
            $this->stats['simulators_removed'] += $surplus->count();
        }

        $missing = array_diff($simulators, $keep->pluck('simulator_id')->all());
        foreach ($missing as $simulatorId) {
            Scenery::insert([
                'scenery_developer_id' => $rows->first()->scenery_developer_id,
                'simulator_id' => $simulatorId,
                'link' => $item['link'],
                'payware' => $item['payware'],
                // An item an admin hid stays hidden on its new simulator too
                'published' => $rows->contains(fn ($row) => (bool) $row->published),
                'source' => 'flightsim.to',
                'source_reference_id' => $addonId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            $this->stats['simulators_added']++;
        }
    }

    /**
     * Scenery rows for an addon we haven't seen before, or none if it can't be tied to an airport.
     */
    private function rowsForNewItem(int $addonId, array $item, array $simulators): array
    {
        $this->airportIndex ??= SceneryHelper::airportIndex();
        $airport = SceneryHelper::resolveAirport($item['code'], $item['title'], $item['latitude'], $item['longitude'], $this->airportIndex);
        if (! $airport || $item['developer'] === '') {
            $this->stats['unresolved']++;
            $this->line("  Unresolved: #$addonId {$item['title']} ({$item['latitude']}, {$item['longitude']})", verbosity: 'v');

            return [];
        }

        $developer = SceneryDeveloper::firstOrCreate(
            ['icao' => $airport['icao'], 'developer' => $item['developer']],
            ['airport_id' => $airport['id']],
        );

        $now = now();
        $this->stats['created']++;

        return array_map(fn ($simulatorId) => [
            'scenery_developer_id' => $developer->id,
            'simulator_id' => $simulatorId,
            'link' => $item['link'],
            'payware' => $item['payware'],
            'published' => true,
            'source' => 'flightsim.to',
            'source_reference_id' => $addonId,
            'created_at' => $now,
            'updated_at' => $now,
        ], $simulators);
    }

    /**
     * Addons missing from every feed are only deleted once their page 404s — flightsim.to leaves
     * some live addons off the map entirely. Rate-limited and re-checked monthly to stay polite.
     */
    private function probeUnlisted(Collection $rowsByAddon): void
    {
        $recheckBefore = now()->subDays(self::RECHECK_DAYS);
        $candidates = $rowsByAddon
            ->map(fn ($rows) => $rows->max('source_checked_at'))
            ->filter(fn ($checkedAt) => $checkedAt === null || $checkedAt < $recheckBefore->toDateTimeString())
            ->sort()
            ->take(max(0, (int) $this->option('probe-limit')));

        $first = true;
        foreach ($candidates->keys() as $addonId) {
            if (! $first) {
                Sleep::for(1)->second();
            }
            $first = false;

            try {
                // Without a slug flightsim.to serves its 200 app shell for any id; with one (any one) a deleted addon 404s
                $status = Http::withUserAgent(self::USER_AGENT)->timeout(10)->get(self::ADDON_URL . $addonId . '/addon')->status();
            } catch (ConnectionException) {
                $status = 0;
            }

            $ids = $rowsByAddon[$addonId]->pluck('id');
            if ($status === 404 || $status === 410) {
                Scenery::whereIn('id', $ids)->delete();
                $this->stats['probed_deleted']++;
            } elseif ($status >= 200 && $status < 300) {
                Scenery::whereIn('id', $ids)->update(['source' => 'flightsim.to', 'source_reference_id' => $addonId, 'source_checked_at' => now()]);
                $this->stats['probed_live']++;
            } else {
                $this->stats['probed_unknown']++;
                if ($status === 429) {
                    $this->warn('flightsim.to rate-limited the probe, stopping for this run');
                    break;
                }
            }
        }
    }
}
