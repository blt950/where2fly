# Where2Fly

Flight destination finder: given a departure or arrival airport, suggests nearby destinations filtered/scored by weather (METAR) and VATSIM network activity.

## Tech stack

- **Backend:** Laravel 13, PHP ^8.2, MySQL 8.4 (Oracle client, see Dockerfile), Sentry, `laravel-eloquent-spatial` for geo queries.
- **Frontend:** Blade + React 19 for the interactive map canvas only (`resources/js/components/map`) — the rest of the UI is server-rendered Blade/JS/SCSS.
- **Frontend layout:** `resources/js/components/{map,context,ui,utils}`, `resources/js/functions`.

Consult the `laravel-13` skill for Laravel 13-specific API/behavior questions (post-dates training data), and the `run-where2fly` skill to build/run the app, take screenshots, or run the PHP test suite.

## Architecture: scoring & search

**Scoring** (`app/Console/Commands/CalcScores.php`, artisan `calc:scores`, triggered hourly via `update:data` in `routes/console.php`): pulls live VATSIM pilot positions, loads open airports with `metar`/`runways`/`controllers`/`events`, and truncates + rebuilds the `airport_scores` table each run. Each row is a tagged event, not a weighted numeric score — reasons include `METAR_WINDY`, `METAR_GUSTS`, `METAR_RVR`, `METAR_CROSSWIND`, `METAR_SIGHT`, `METAR_CEILING`, `METAR_FOGGY`, `METAR_HEAVY_RAIN`, `METAR_HEAVY_SNOW`, `METAR_THUNDERSTORM`, `VATSIM_ATC`, `VATSIM_POPULAR`, `VATSIM_EVENT`. `AirportScore::getTopAirports()` aggregates these by count for the "top airports" views.

**Search** (`app/Http/Controllers/API/SearchController.php`): validates a large filter payload (departure/arrival, destination continents/countries/states, aircraft codeletter, airtime range, score filters, runway/lights/airbase/size filters, temperature/elevation/runway-length ranges, arrival whitelist, limit) → resolves the anchor `Airport` → chains Eloquent query scopes defined directly on `App\Models\Airport` (`airportOpen`, `notIcao`, `isAirportSize`, `inContinent`/`inCountry`/`inState`, `withinDistance`/`withinBearing`, `filterRunwayLengths`, `filterRunwayLights`, `filterAirbases`, `filterByScores`, `filterRoutesAndAirlines`, `returnOnlyWhitelistedIcao`, `sortByScores`) → shuffles within score-count buckets and takes 20 → applies a `filterWithCriteria` collection macro for weather/temp/elevation post-filtering → returns `AirportResource`/`SuggestedAirportResource`.

Most domain logic lives as query scopes on `Airport` (large file) plus `CalculationHelper` (aircraft range/bearing math). When extending search/scoring, prefer adding a new scope on `Airport` (or a new `SCORE_*` reason in `CalcScores`) over introducing a parallel filtering path.

## Testing

- PHPUnit only (`phpunit.xml`), suites: `tests/Unit`, `tests/Feature`.
- Tests run against a real MySQL database (`DB_DATABASE=where2fly_test`) — there's no sqlite/in-memory fallback, so a MySQL instance must be available.
- Use the `phpunit` to test the app. Invoke the `run-where2fly` where the unit test doesn't cover the case.

## Code style

- Formatter is Laravel Pint (`pint.json`, `laravel` preset + a few custom rules). Run `./vendor/bin/pint` (or via the dev container) before committing PHP changes — don't hand-format to match the preset from memory.

## Migrations

- Keep the existing convention: one timestamped file per schema change, descriptive snake_case name (e.g. `add_feedback_votes_table`, `index_runways`), placed in `database/migrations/`. Don't bundle unrelated schema changes into a single migration.
