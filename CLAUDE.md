# Where2Fly

Flight destination finder: given a departure or arrival airport, suggests nearby destinations filtered/scored by weather (METAR) and VATSIM network activity.

## Tech stack

- **Backend:** Laravel 13, PHP ^8.2, MySQL 8.4 (Oracle client, see Dockerfile), Sentry, `laravel-eloquent-spatial` for geo queries.
- **Frontend:** Blade + React 19 for the interactive map canvas only (`resources/js/components/map`) — the rest of the UI is server-rendered Blade/JS/SCSS. The map is MapLibre GL (WebGL2) on CARTO basemap tiles.
- **Frontend layout:** `resources/js/components/{map,context,ui,utils}`, `resources/js/functions`.

Consult the `laravel-13` skill for Laravel 13-specific API/behavior questions (post-dates training data), and the `run-where2fly` skill to build/run the app, take screenshots, or run the PHP test suite.

## The map (MapLibre GL)

`Map.jsx` is the app's only React mount point, dynamically imported from `app.js` only when the page has an `#map` element — so React and MapLibre stay off every page without a map. Don't add static React imports back to `app.js`.

**Component convention:** `MapProvider` owns the `maplibregl.Map` and hands it down through `MapGLContext`; every child renders `null` and does its work in effects. One component owns one source and its layer, and takes both down on unmount — use `useMapLayer` from `map/mapLayers.js` rather than hand-rolling `addSource`/`addLayer`/teardown (`removeSourceLayer` alone is for overlays that can't add on mount, e.g. `MapWeather` waiting on a fetch). Layer ids that cross module boundaries live in `mapConfig.js` (`BASEMAP_ANCHORS`, `TERMINATOR_LAYER`) and `utils/airportLayerSpec.js` (`AIRPORT_SOURCES`, `hitId`, `clusterIds`) — overlays stack against them via `beneath()`, and a rename that misses one degrades silently into wrong z-ordering, never an error.

**Gotchas** (each cost real debugging time):

- **`setWorkerUrl` is mandatory.** MapLibre v6 resolves its worker relative to `import.meta.url`, which after bundling is a 404 Vite never emits. The failure is silent: no error event, black canvas, zero tile requests. `MapProvider` fixes it with Vite's `?worker&url`.
- **v6 has no default export** — namespace imports (`import * as maplibregl`) throughout.
- **Glyphs are committed**, under `public/fonts/`, because CARTO's glyph server only carries Open Sans / Roboto / Noto. Regenerate only when the typeface changes: `npm install --no-save fontnik && node scripts/build-glyphs.mjs` (fontnik is native and deliberately not a devDependency, so it can't break CI).
- **Style swaps remount every child.** Changing theme calls `setStyle`, which fires `style.load` and bumps `styleEpoch`; children are keyed on it, so glyphs, projection and theme overrides must be reapplied on every load, not once.
- **`localStorage` throws outright** where a browser blocks site data, and the map reads it during render — always go through `utils/storage.js`, never `localStorage` directly.
- **Reduced motion:** MapLibre turns `flyTo` into a synchronous `jumpTo`, which fires `moveend` *inside* the call — register listeners before flying, not after. Own animations (the route reveal, the radar ping) need their own `prefers-reduced-motion` path.
- **Tile providers are third parties** your browser talks to directly (CARTO always; Tilezen DEM and RainViewer when terrain/precipitation are on). `resources/views/privacy.blade.php` lists them — keep it in step when adding a source.

## Codebase navigation

This repo has a `graphify` knowledge graph checked into `graphify-out/` (`graph.json`, `GRAPH_REPORT.md`, `graph.html`). Before a broad grep/explore sweep to understand architecture or cross-file relationships, check `graphify-out/GRAPH_REPORT.md` (god nodes, communities, surprising connections) or run `graphify query "<question>"` — it's usually cheaper than re-deriving structure from scratch. Requires the `graphifyy` package on `PATH`/`python3` (`pip install graphifyy` or `uv tool install graphifyy`); if it's not installed and can't be, skip straight to normal grep/Explore rather than stalling on setup. The graph can go stale as the repo changes — run `graphify update /app` (the code-only subcommand, no LLM key needed) after a burst of commits if answers look off; the `graphify /app --update` flag form instead triggers full semantic extraction, demands an LLM API key and chokes on the repo's ~1,300 images. Treat the graph as a shortcut, not ground truth over reading the actual files.

## Architecture: scoring & search

**Data ingestion** (`update:data` at `:05`/`:35` in `routes/console.php` → `fetch:metars`, `fetch:tafs`, `fetch:vatsim`; `fetch:bookings` separately every 30 min): METARs and TAFs come from the aviationweather.gov bulk cache files (`{metars,tafs}.cache.xml.gz` — download, gunzip, parse, delete; see `AviationWeatherHelper`), never per-airport API calls. Stored METAR text starts at the time group (`040720Z ...`) — the report-type token and station id are stripped on ingest. TAFs are two tables: `tafs` (one row per airport — the document's raw text, `issued_at` for change detection, validity) and `taf_forecasts` (one row per period, structured fields, ceiling pre-computed to a plain `ceiling_ft_agl` int — no JSON; `TafForecast` mirrors `Metar`'s condition-method names). `bookings` stores the next 24h of VATSIM ATC bookings (primary key = the API's own booking id, no local auto-increment) resolved to airports via `AirportCallsignHelper::resolveIcao()` (shared with `fetch:vatsim`; unresolvable/CTR/FSS/ATIS positions are dropped, never stored with a null FK).

**Scoring**: every `airport_scores` row is a tagged event (`reason`) carrying a confidence weight (`score` decimal: 1.00 for certain signals, less for uncertain TAF periods — `AirportScore::forecastWeight()`: TEMPO 0.7, PROB40 0.5, PROB30 0.3, PROBnn TEMPO slightly below the bare PROB), a validity window (`valid_from`/`valid_to`) and a `source` (`metar`, `taf`, `vatsim`, `event`, `booking`, `logon_estimate`). **Each fetch command owns its sources** and deletes+rebuilds only those on each run: `fetch:metars` → `metar` (incl. runway-dependent `METAR_RVR`/`METAR_CROSSWIND`), `fetch:vatsim` → `vatsim`/`event`/`logon_estimate` (plus `VATSIM_POPULAR` from the pilots section of the same vatsim-data.json download), `fetch:bookings` → `booking`, and `fetch:tafs` maintains `taf` rows incrementally (only when a TAF's `issued_at` advances). There is no separate calc command. Adding a new score signal means picking a `source` tag, a window, and which fetch command owns its rebuild — don't reintroduce a blind full truncate. Bookings and logon-estimates materialize *predicted* `VATSIM_ATC` rows (events only produce `VATSIM_EVENT` — an event alone doesn't assert ATC presence); the shared weather-reason checks live in `WeatherScoreHelper::reasons()` (works on `Metar` or `TafForecast`). **All** TAF periods score, including bare TEMPO — TEMPO/PROB rows carry `probability`/`tempo` in `data` (badge + tooltip line) and a `score` weight below 1, which fades the icon to 50% (`score-uncertain`).

**ETA matching**: score lookups are time-scoped. `AirportScore::coversEta()` (SQL) is the single definition — exact window containment for `metar`/`taf`/`vatsim`/`logon_estimate`, asymmetric overlap for the scheduled-presence signals `booking`/`event` — 1h before the window opens (`OVERLAP_MATCH_EARLY_MINUTES`, so a booking starting shortly after the ETA still shows and the pilot can adjust their flight time) but only 15min after it closes (`OVERLAP_MATCH_LATE_MINUTES`, since a controller who has signed off is not something the pilot can fly around), and a METAR-fallback branch (a `metar` row matches regardless of window when no TAF period covers the ETA). Online-controller semantics: the live `vatsim` row covers "now" views for as long as they're online; forecasts predict an unbooked controller present strictly until `logon + 2h` (`logon_estimate` — a session already past 2h yields a never-matching window on purpose). Its `$metarOnlyWeather` mode (departure candidates) ignores TAF rows entirely and always trusts the current METAR. It has a PHP twin, `coversEtaAt()`/`Airport::scoresAtEta()`, used to filter loaded collections per candidate — **keep the two in sync when changing matching rules**. Search binds a per-candidate ETA computed inside SQL (`CalculationHelper::forecastEtaSql()`, distance-derived) — but **only when the candidates are arrivals**; departure suggestions are evaluated at `now()` (the pilot leaves there soon, so the current METAR applies and `forecastSource` is `metar`). "Now" views also pass `now()`. Rankings (`sortByScores`) sum each reason's single best weight (a `MAX(CASE WHEN reason = ? …)` pivot — several sources can assert the same reason, and a certain row beats an uncertain TAF row for the same reason, never adds to it); rendering dedupes via `Airport::displayScores()` (same best-weight-first preference) and each icon (tooltip lines, uncertainty badge, facility dots) renders through the `App\View\Components\ScoreIcon` component — view logic belongs there, not in `@php` blocks.

**Search** (`app/Http/Controllers/API/SearchController.php`): validates a large filter payload (departure/arrival, destination continents/countries/states, aircraft codeletter, airtime range, score filters, runway/lights/airbase/size filters, temperature/elevation/runway-length ranges, arrival whitelist, limit) → resolves the anchor `Airport` → chains Eloquent query scopes defined directly on `App\Models\Airport` (`airportOpen`, `notIcao`, `isAirportSize`, `inContinent`/`inCountry`/`inState`, `withinDistance`/`withinBearing`, `filterRunwayLengths`, `filterRunwayLights`, `filterAirbases`, `filterByScores`, `filterRoutesAndAirlines`, `returnOnlyWhitelistedIcao`, `sortByScores`) → shuffles within score-count buckets and takes 20 → applies a `filterWithCriteria` collection macro for weather/temp/elevation post-filtering (which also narrows each candidate's loaded scores to its ETA and sets `forecast_source`) → returns `AirportResource`/`SuggestedAirportResource`.

Most domain logic lives as query scopes on `Airport` (large file) plus `CalculationHelper` (aircraft range/bearing math). When extending search/scoring, prefer adding a new scope on `Airport` (or a new reason in the owning fetch command) over introducing a parallel filtering path.

## Console commands: hard-won gotchas

- **Memory**: PHP `memory_limit` is 256MB and `update:data` runs all fetch commands **in one process** (`$this->call()`). `Airport::all()` materializes ~80k full models (~220MB) — for icao→id lookup maps always `Airport::select('id', 'icao', ...)`. A command that passes standalone can still OOM composed; after touching any of these commands, verify with a full `php artisan update:data` run, not just the single command.
- **Bulk writes**: chunk `insert`/`upsert` payloads (`array_chunk(..., 500)`) — MySQL's prepared-statement placeholder limit (~65k) is easy to blow with thousands of rows × several columns.
- **Carbon 3**: singular units take no argument — `addHour(1)` is invalid, use `addHours($n)`.
- **AWC cache quirks** (encoded in `FetchMetars`/`FetchTafs`/`Taf` and their tests — read those before "fixing" parsing): `wind_dir_degrees` can be the literal `VRB`; both `METAR` and `SPECI` rows are valid observations; TAF sky cover uses `OVX` for obscured sky; visibility can be `6+`/`10+` ("at or above"); TAF amendment detection must key off `issue_time`, not `bulletin_time`.

## Testing

- PHPUnit only (`phpunit.xml`), suites: `tests/Unit`, `tests/Feature`.
- Tests run against a real MySQL database (`DB_DATABASE=where2fly_test`) — there's no sqlite/in-memory fallback, so a MySQL instance must be available.
- Use the `phpunit` to test the app. Invoke the `run-where2fly` where the unit test doesn't cover the case.
- `TestAirportSeeder` seeds `airport_scores` rows with deliberately wide validity windows (now−1h → now+30h) so filter/sort tests aren't sensitive to ETA windowing; tests that exercise the windowing itself (`ScorePredictionTest`) seed their own rows. New `airport_scores` fixtures must set `source`/`valid_from`/`valid_to` — they're NOT NULL.
- A schema/seeder error makes the suite crawl (~15 min instead of ~10s: every failing test re-runs `migrate:fresh`) — if tests suddenly take forever, suspect the seeder before the tests.

## Code style

- Formatter is Laravel Pint (`pint.json`, `laravel` preset + a few custom rules). Run `./vendor/bin/pint` (or via the dev container) before committing PHP changes — don't hand-format to match the preset from memory.
- Comments: max 2 lines, no restating what the code already says. Explain the *why* (the non-obvious constraint, gotcha, or measurement) and cut the rest — that includes docblocks on tests and helpers.

## Migrations

- Keep the existing convention: one timestamped file per schema change, descriptive snake_case name (e.g. `add_feedback_votes_table`, `index_runways`), placed in `database/migrations/`. Don't bundle unrelated schema changes into a single migration.
