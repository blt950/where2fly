# Graph Report - app  (2026-08-22)

## Corpus Check
- 370 files · ~435,202 words
- Verdict: corpus is large enough that graph structure adds value.

## Summary
- 1577 nodes · 2670 edges · 255 communities (204 shown, 51 thin omitted)
- Extraction: 98% EXTRACTED · 2% INFERRED · 0% AMBIGUOUS · INFERRED: 57 edges (avg confidence: 0.85)
- Token cost: 0 input · 0 output

## Graph Freshness
- Built from commit: `4beafaf4`
- Run `git rev-parse HEAD` and compare to check if the graph is stale.
- Run `graphify update .` after code changes (no API cost).

## Community Hubs (Navigation)
- TafForecast
- AirportCard.jsx
- Illuminate\Console\Command
- AirportScore
- Controller
- SearchTest
- SceneryDeveloper
- User
- dependencies
- Simulator
- AirportFilterHelper
- Controllers/SearchController.php
- Where2Fly Project (README overview)
- Illuminate\Database\Eloquent\Model
- AirportCallsignHelper
- Airport
- ApiTest
- UserAccountTest
- UserController.php
- Illuminate\Database\Eloquent\Builder
- TestCase
- Illuminate\Http\Request
- Scenery
- require
- useMapGL
- AircraftHelper
- UserListController
- ApiKey
- CI PHPUnit Job (MySQL 8 service, where2fly_test DB)
- FeedbackController
- composer.json
- require-dev
- Illuminate\Support\Str
- FlightAircraft
- WithinDistanceTest
- Map.jsx
- config
- psr-4
- scripts
- app.blade.php
- searchForm.js
- arrivals.blade.php
- departures.blade.php
- Aircraft
- Carbon\Carbon
- extra
- Illuminate\Database\Schema\Blueprint
- Illuminate\Support\Facades\Schema
- airportLayerSpec.js
- mapConfig.js
- RouteFilterTest
- Illuminate\Database\Migrations\Migration
- 2024_07_22_072158_add_geospacial_airport_coordinates.php
- MapWeather.jsx
- Airline
- MapHelper
- Eloquent (Laravel 13.x)
- backup.php
- Flight
- Routing & Controllers (Laravel 13.x)
- Upgrading From Laravel 12.x to 13.0
- feedback/index.blade.php
- show.blade.php
- front/routes.blade.php
- appStatic.blade.php
- airports.blade.php
- top.blade.php
- entrypoint.sh
- install-npm.sh
- layouts.tracking
- combobox.js
- admin.blade.php
- login.blade.php
- register.blade.php
- resetForm.blade.php
- resetRequest.blade.php
- settings.blade.php
- api.blade.php
- changelog.blade.php
- list/create.blade.php
- list/edit.blade.php
- list/index.blade.php
- privacy.blade.php
- scenery.blade.php
- scenery/create.blade.php
- scenery/edit.blade.php
- search/routes.blade.php
- SearchController
- Database: Query Builder (Laravel 13.x)
- ATC Bookings API
- run-where2fly/package.json
- disposable-email.php
- logging.php
- Endpoint groups
- errors.icons.nosedive
- errors.icons.lost
- Illuminate\Support\Facades\Broadcast
- Illuminate\Support\Facades\Schedule
- web.php
- Common Helpers & Facades Quick Reference (Laravel 13.x)
- Laravel 13
- Data API
- renovate.json
- MapControls.jsx
- FeedbackVote
- run-where2fly/SKILL.md
- Events API
- terminatorPolygon
- driver.mjs
- METAR API, Slurper API
- VATSIM APIs
- app.js
- [3.0.4](https://github.com/blt950/where2fly/compare/v3.0.3...v3.0.4) (2026-08-17)
- release-please-config.json
- build-glyphs.mjs

## God Nodes (most connected - your core abstractions)
1. `Airport` - 110 edges
2. `User` - 81 edges
3. `AirportScore` - 61 edges
4. `SearchTest` - 51 edges
5. `Simulator` - 44 edges
6. `UserList` - 44 edges
7. `Scenery` - 30 edges
8. `ApiTest` - 26 edges
9. `TestCase` - 26 edges
10. `TafForecast` - 25 edges

## Surprising Connections (you probably didn't know these)
- `Where2Fly Application (Laravel 13 + Blade + React map canvas)` --semantically_similar_to--> `Where2Fly Project (README overview)`  [INFERRED] [semantically similar]
  CLAUDE.md → README.md
- `up()` --calls--> `Airport`  [EXTRACTED]
  database/migrations/2024_07_22_072158_add_geospacial_airport_coordinates.php → app/Models/Airport.php
- `Ko-fi Sponsorship (where2fly)` --conceptually_related_to--> `Where2Fly Project (README overview)`  [INFERRED]
  .github/FUNDING.yml → README.md
- `Bug Report Issue Template` --conceptually_related_to--> `Where2Fly Project (README overview)`  [INFERRED]
  .github/ISSUE_TEMPLATE/bug_report.md → README.md
- `Feature Request Issue Template` --conceptually_related_to--> `Where2Fly Project (README overview)`  [INFERRED]
  .github/ISSUE_TEMPLATE/feature_request.md → README.md

## Import Cycles
- None detected.

## Hyperedges (group relationships)
- **CI Pipeline: lint -> tests -> container build, all via shared setup-dependencies action** — _github_workflows_ci_lint_job, _github_workflows_ci_tests_job, _github_workflows_ci_build_container_job, _github_actions_setup_dependencies_action_setup_dependencies [EXTRACTED 1.00]
- **where2fly container image lifecycle: CI builds and pushes to ghcr.io, prod compose pulls it, dev compose builds locally instead** — _github_workflows_ci_build_container_job, docker_compose_web_service, docker_compose_dev_web_service [INFERRED 0.85]

## Communities (255 total, 51 thin omitted)

### Community 0 - "TafForecast"
Cohesion: 0.06
Nodes (10): FetchMetars, FetchTafs, AviationWeatherHelper, WeatherScoreHelper, Carbon, Metar, TafForecast, SimpleXMLElement (+2 more)

### Community 1 - "AirportCard.jsx"
Cohesion: 0.18
Nodes (12): AirportCard(), CardContext, FlightsCard(), SceneryCard(), CurrencyDropdown(), SimbriefLink(), TAF(), ExternalLinkTracker() (+4 more)

### Community 2 - "Illuminate\Console\Command"
Cohesion: 0.12
Nodes (9): AccountClearUnverified, CleanupSceneries, EnrichAirports, EnrichFlights, EnrichSceneries, FetchGithubIssues, UpdateData, Command (+1 more)

### Community 3 - "AirportScore"
Cohesion: 0.09
Nodes (7): AirportScore, ScoreIcon, Illuminate\View\Component, PHPUnit\Framework\TestCase, ScorePredictionTest, TopAirportsTest, ForecastWeightTest

### Community 4 - "Controller"
Cohesion: 0.17
Nodes (7): SearchController, TopController, Controller, AirportResource, SuggestedAirportResource, Illuminate\Foundation\Auth\Access\AuthorizesRequests, Illuminate\Http\Resources\Json\JsonResource

### Community 6 - "SceneryDeveloper"
Cohesion: 0.14
Nodes (3): SceneryHelper, MapController, SceneryDeveloper

### Community 7 - "User"
Cohesion: 0.10
Nodes (8): User, UserListPolicy, UserPolicy, Illuminate\Auth\Passwords\CanResetPassword, Illuminate\Contracts\Auth\MustVerifyEmail, Illuminate\Foundation\Auth\User, Illuminate\Notifications\Notifiable, SceneryTest

### Community 8 - "dependencies"
Cohesion: 0.05
Nodes (41): bootstrap, laravel-vite-plugin, lodash, maplibre-gl, nouislider, dependencies, bootstrap, laravel-vite-plugin (+33 more)

### Community 9 - "Simulator"
Cohesion: 0.10
Nodes (4): Simulator, UserList, Illuminate\Support\Facades\Auth, UserListTest

### Community 10 - "AirportFilterHelper"
Cohesion: 0.14
Nodes (7): AirportFilterHelper, distance(), CollectionAirportFilter, AppServiceProvider, Illuminate\Auth\Notifications\VerifyEmail, Illuminate\Notifications\Messages\MailMessage, Illuminate\Support\ServiceProvider

### Community 11 - "Controllers/SearchController.php"
Cohesion: 0.06
Nodes (28): CountryHelper, getCountryName(), rwyIdentToHeading(), ScoreHelper, AdminVariables, FeedbackVariables, UserActive, AirportExists (+20 more)

### Community 12 - "Where2Fly Project (README overview)"
Cohesion: 0.09
Nodes (24): Ko-fi Sponsorship (where2fly), Bug Report Issue Template, Issue Template Config (blank issues disabled), Feature Request Issue Template, Aircraft Type Codeletters (GA, GAT, GTP, JS, JM, JML, JL, JXL), API Attribution Requirement (Powered by Where2Fly link), Score Reason Codes (METAR_WINDY..METAR_THUNDERSTORM, VATSIM_ATC, VATSIM_EVENT, VATSIM_POPULAR), POST /api/search Endpoint (departure/arrival, destinations, codeletter, score and airport filters) (+16 more)

### Community 13 - "Illuminate\Database\Eloquent\Model"
Cohesion: 0.09
Nodes (9): ApiLog, Controller, Event, NotableAirport, NotableAirportTag, Runway, Taf, Illuminate\Database\Eloquent\Factories\HasFactory (+1 more)

### Community 14 - "AirportCallsignHelper"
Cohesion: 0.11
Nodes (8): FetchBookings, FetchVatsim, AirportCallsignHelper, Booking, Illuminate\Support\Facades\File, Illuminate\Support\Facades\Http, RuntimeException, AirportCallsignHelperTest

### Community 18 - "UserController.php"
Cohesion: 0.22
Nodes (6): Illuminate\Auth\Events\PasswordReset, Illuminate\Auth\Events\Registered, Illuminate\Foundation\Auth\EmailVerificationRequest, Illuminate\Support\Facades\Hash, Illuminate\Support\Facades\Password, RyanChandler\LaravelCloudflareTurnstile\Rules\Turnstile

### Community 20 - "TestCase"
Cohesion: 0.13
Nodes (6): CreatesApplication, Illuminate\Foundation\Testing\RefreshDatabase, Illuminate\Foundation\Testing\TestCase, AdminTest, ResponseTest, TestCase

### Community 21 - "Illuminate\Http\Request"
Cohesion: 0.17
Nodes (4): SceneryController, UserController, Illuminate\Contracts\Http\Kernel, Illuminate\Http\Request

### Community 22 - "Scenery"
Cohesion: 0.16
Nodes (4): Scenery, SceneryPolicy, up(), up()

### Community 23 - "require"
Cohesion: 0.12
Nodes (16): require, graham-campbell/markdown, laravel/framework, laravel/sanctum, laravel/tinker, laravel/ui, league/flysystem-aws-s3-v3, matanyadaev/laravel-eloquent-spatial (+8 more)

### Community 24 - "useMapGL"
Cohesion: 0.18
Nodes (16): MapContext, useMapGL(), collectAttributions(), EVENTS, MapAttribution(), MapBound(), MapPan(), MapPing() (+8 more)

### Community 25 - "AircraftHelper"
Cohesion: 0.12
Nodes (4): AircraftHelper, CalculationHelper, InvalidArgumentException, Location\Coordinate

### Community 27 - "ApiKey"
Cohesion: 0.25
Nodes (3): CreateApiKey, ApiToken, ApiKey

### Community 28 - "CI PHPUnit Job (MySQL 8 service, where2fly_test DB)"
Cohesion: 0.29
Nodes (8): Setup Dependencies Composite Action (PHP 8.3.2, composer cache, optional Node 22), CI Build Container Job (pushes ghcr.io/blt950/where2fly, injects private fonts repo), CI Lint Job (Pint --test), CI PHPUnit Job (MySQL 8 service, where2fly_test DB), Testing Against Real MySQL (where2fly_test database, PHPUnit only, no sqlite fallback), Dev Compose Web Service (builds local Dockerfile, bind-mounts repo into container where2fly), Production Compose Web Service (pulls ghcr.io/blt950/where2fly:latest), Docker Development Setup (docker-compose.dev.yml, migrate, key:generate, schedule:run cron)

### Community 30 - "composer.json"
Cohesion: 0.17
Nodes (11): autoload-dev, psr-4, description, keywords, license, name, prefer-stable, Tests\\ (+3 more)

### Community 31 - "require-dev"
Cohesion: 0.22
Nodes (9): require-dev, barryvdh/laravel-debugbar, fakerphp/faker, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit (+1 more)

### Community 32 - "Illuminate\Support\Str"
Cohesion: 0.17
Nodes (5): AirportFactory, UserFactory, Illuminate\Database\Eloquent\Factories\Factory, Illuminate\Support\Str, Pdo\Mysql

### Community 35 - "Map.jsx"
Cohesion: 0.19
Nodes (17): CONTINENT_VIEWS, getInitMapPosition(), Map(), mapElement, MapSaveView(), POSITION_KEY, supportsWebGL2(), view() (+9 more)

### Community 36 - "config"
Cohesion: 0.33
Nodes (6): php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 37 - "psr-4"
Cohesion: 0.29
Nodes (7): autoload, files, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\, app/Helpers/helpers.php

### Community 38 - "scripts"
Cohesion: 0.20
Nodes (10): scripts, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd, Illuminate\\Foundation\\ComposerScripts::postAutoloadDump, @php artisan key:generate --ansi, @php artisan package:discover --ansi (+2 more)

### Community 39 - "app.blade.php"
Cohesion: 0.40
Nodes (4): layouts.footer, parts.map, layouts.header, layouts.menu

### Community 40 - "searchForm.js"
Cohesion: 0.60
Nodes (3): contractFilters(), expandFilters(), toggleFilters()

### Community 41 - "arrivals.blade.php"
Cohesion: 0.40
Nodes (4): front.parts.form, front.parts.sliders, front.parts.tabs, layouts.title

### Community 42 - "departures.blade.php"
Cohesion: 0.40
Nodes (4): front.parts.form, front.parts.sliders, front.parts.tabs, layouts.title

### Community 44 - "Carbon\Carbon"
Cohesion: 0.17
Nodes (5): Carbon\Carbon, Illuminate\Database\Eloquent\Attributes\Scope, MatanYadaev\EloquentSpatial\Objects\LineString, MatanYadaev\EloquentSpatial\Objects\Polygon, MatanYadaev\EloquentSpatial\Traits\HasSpatial

### Community 45 - "extra"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

### Community 57 - "airportLayerSpec.js"
Cohesion: 0.21
Nodes (17): MapAirportSource(), pinnedFilter(), AIRPORT_TYPES, clusterIds(), clusterSpecs(), filtersLabelsByZoom(), focusColor(), labelIds() (+9 more)

### Community 59 - "mapConfig.js"
Cohesion: 0.17
Nodes (13): MapGLContext, applyTheme(), BASEMAP_ANCHORS, CLUSTER_COLOURS, DARK_HILLSHADE, DARK_PALETTE, GLYPHS_URL, LABEL_FONT (+5 more)

### Community 69 - "2024_07_22_072158_add_geospacial_airport_coordinates.php"
Cohesion: 0.27
Nodes (5): up(), TestAirportSeeder, Illuminate\Database\Seeder, MatanYadaev\EloquentSpatial\Enums\Srid, MatanYadaev\EloquentSpatial\Objects\Point

### Community 71 - "MapWeather.jsx"
Cohesion: 0.33
Nodes (11): beneath(), TERMINATOR_LAYER, removeSourceLayer(), useMapLayer(), MapTerminator(), MapTerrain(), frameTiles(), latestFrame() (+3 more)

### Community 76 - "Airline"
Cohesion: 0.19
Nodes (5): CalcFlights, ReuploadAirlines, Airline, Attribute, Illuminate\Database\Eloquent\Casts\Attribute

### Community 78 - "MapHelper"
Cohesion: 0.22
Nodes (4): MapHelper, TopController, Illuminate\Support\Collection, Illuminate\View\View

### Community 79 - "Eloquent (Laravel 13.x)"
Cohesion: 0.15
Nodes (12): Attribute-by-attribute reference, Casts, Common query/CRUD patterns, Eloquent (Laravel 13.x), Global scopes, Local scopes: `#[Scope]` attribute (new style) replaces `scopeXxx()`, Model definition: attribute style vs. property style, Pending attributes on scopes (+4 more)

### Community 90 - "backup.php"
Cohesion: 0.17
Nodes (11): Spatie\Backup\Notifications\Notifiable, Spatie\Backup\Notifications\Notifications\BackupHasFailedNotification, Spatie\Backup\Notifications\Notifications\BackupWasSuccessfulNotification, Spatie\Backup\Notifications\Notifications\CleanupHasFailedNotification, Spatie\Backup\Notifications\Notifications\CleanupWasSuccessfulNotification, Spatie\Backup\Notifications\Notifications\HealthyBackupWasFoundNotification, Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFoundNotification, Spatie\Backup\Tasks\Cleanup\Strategies\DefaultStrategy (+3 more)

### Community 97 - "Routing & Controllers (Laravel 13.x)"
Cohesion: 0.18
Nodes (10): Authorization attribute: `#[Authorize]` (new in 13.x), Basic route definitions, Controller middleware — three ways (all still valid), Dependency injection in controllers, Fine-grained resource middleware (`middlewareFor` / `withoutMiddlewareFor`), Named routes & groups, Resource controllers, Route model binding (+2 more)

### Community 101 - "Upgrading From Laravel 12.x to 13.0"
Cohesion: 0.18
Nodes (10): Cache `serializable_classes` config, CSRF middleware renamed: `PreventRequestForgery`, High impact, Low impact (know these exist, fix if they bite), Medium impact, MySQL/MariaDB `upsert` requires non-empty `uniqueBy`, Not covered here, Updating dependencies (+2 more)

### Community 117 - "combobox.js"
Cohesion: 0.40
Nodes (4): destination, destinationList, exclusiveDestinations, placeholderConfigs

### Community 136 - "Database: Query Builder (Laravel 13.x)"
Cohesion: 0.20
Nodes (9): Basic queries, Database: Query Builder (Laravel 13.x), Insert / update / delete, Joins, Migrations — quick reminders, Pessimistic locking, Transactions, Vector similarity clauses (new in 13, PostgreSQL + pgvector only) (+1 more)

### Community 137 - "ATC Bookings API"
Cohesion: 0.20
Nodes (9): ATC Bookings API, `DELETE /booking/{id}`, Field notes, `GET /booking`, `GET /booking/{id}`, `POST /booking`, `PUT /booking/{id}`, Read (public, no auth) (+1 more)

### Community 141 - "run-where2fly/package.json"
Cohesion: 0.22
Nodes (8): dependencies, playwright, description, name, private, type, version, playwright

### Community 145 - "logging.php"
Cohesion: 0.50
Nodes (3): Monolog\Handler\NullHandler, Monolog\Handler\StreamHandler, Monolog\Handler\SyslogUdpHandler

### Community 150 - "Endpoint groups"
Cohesion: 0.22
Nodes (8): atc, Authentication, community, Core API, Endpoint groups, members, Notes, orgs

### Community 228 - "Common Helpers & Facades Quick Reference (Laravel 13.x)"
Cohesion: 0.25
Nodes (7): `Arr` (`Illuminate\Support\Arr`), `collect()` / `Illuminate\Support\Collection`, Common Helpers & Facades Quick Reference (Laravel 13.x), `Http` (`Illuminate\Support\Facades\Http`), React + SCSS stack notes, `Str` (`Illuminate\Support\Str`), `Validator` / form validation

### Community 229 - "Laravel 13"
Cohesion: 0.25
Nodes (7): A note on confidence, A quick taste of the attribute style, Laravel 13, New capabilities worth knowing about, Stack context, What changed from Laravel 12 to 13, Where to look

### Community 230 - "Data API"
Cohesion: 0.25
Nodes (7): Data API, Endpoints, Example: filter to pilots inbound to a specific airport (pseudocode), Example: find all online controllers at a facility, `GET /transceivers-data.json`, `GET /vatsim-data.json` — response schema, `GET /vatsim-servers.json`, `/all-servers.json`, `/sweatbox-servers.json`

### Community 231 - "renovate.json"
Cohesion: 0.25
Nodes (7): config:recommended, dependencies, customManagers, extends, labels, packageRules, $schema

### Community 232 - "MapControls.jsx"
Cohesion: 0.25
Nodes (6): MAP_THEMES, LAYERS, MapControls(), PROJECTIONS, THEMES, WEATHER_STATUS

### Community 234 - "run-where2fly/SKILL.md"
Cohesion: 0.29
Nodes (6): Gotchas, Prerequisites, Run (agent path), Run (human path), Setup, Test

### Community 235 - "Events API"
Cohesion: 0.29
Nodes (6): Endpoints, Events API, Field notes, Practical notes for polling, Recipes, Response shape

### Community 236 - "terminatorPolygon"
Cohesion: 0.52
Nodes (6): eclipticObliquity(), gmst(), julian(), sunEclipticLongitude(), sunEquatorialPosition(), terminatorPolygon()

### Community 237 - "driver.mjs"
Cohesion: 0.40
Nodes (5): consoleMessages, parseSelector(), rl, runLine(), screenshotDir

### Community 238 - "METAR API, Slurper API"
Cohesion: 0.33
Nodes (5): `GET /:icao`, `GET /users/info`, METAR API, METAR API, Slurper API, Slurper API

### Community 239 - "VATSIM APIs"
Cohesion: 0.40
Nodes (4): General notes that apply across all of these, Quick recipes, VATSIM APIs, Which API do I need?

### Community 241 - "[3.0.4](https://github.com/blt950/where2fly/compare/v3.0.3...v3.0.4) (2026-08-17)"
Cohesion: 0.50
Nodes (3): [3.0.4](https://github.com/blt950/where2fly/compare/v3.0.3...v3.0.4) (2026-08-17), Bug Fixes, Changelog

## Ambiguous Edges - Review These
- `Data Ingestion Pipeline (update:data -> fetch:metars/tafs/vatsim; fetch:bookings; AWC bulk cache files, not per-airport calls)` → `External Data Sources (OurAirports, Airlabs, metar.vatsim.net, api.met.no TAF, FSAddonCompare, flagicons)`  [AMBIGUOUS]
  README.md · relation: conceptually_related_to

## Knowledge Gaps
- **237 isolated node(s):** `screenshotDir`, `consoleMessages`, `rl`, `name`, `version` (+232 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **51 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Data Ingestion Pipeline (update:data -> fetch:metars/tafs/vatsim; fetch:bookings; AWC bulk cache files, not per-airport calls)` and `External Data Sources (OurAirports, Airlabs, metar.vatsim.net, api.met.no TAF, FSAddonCompare, flagicons)`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **Why does `Airport` connect `Airport` to `TafForecast`, `Illuminate\Console\Command`, `AirportScore`, `Controller`, `SceneryDeveloper`, `SearchController`, `Simulator`, `Controllers/SearchController.php`, `Illuminate\Database\Eloquent\Model`, `AirportCallsignHelper`, `Illuminate\Database\Eloquent\Builder`, `TestCase`, `Illuminate\Http\Request`, `AircraftHelper`, `UserListController`, `WithinDistanceTest`, `Aircraft`, `Carbon\Carbon`, `RouteFilterTest`, `2024_07_22_072158_add_geospacial_airport_coordinates.php`, `MapHelper`, `Flight`?**
  _High betweenness centrality (0.151) - this node is a cross-community bridge._
- **Why does `User` connect `User` to `Illuminate\Support\Str`, `Illuminate\Console\Command`, `web.php`, `Simulator`, `Illuminate\Database\Eloquent\Model`, `MapHelper`, `ApiTest`, `UserAccountTest`, `UserController.php`, `TestCase`, `Illuminate\Http\Request`, `Scenery`?**
  _High betweenness centrality (0.048) - this node is a cross-community bridge._
- **Why does `SearchTest` connect `SearchTest` to `Simulator`, `TestCase`?**
  _High betweenness centrality (0.037) - this node is a cross-community bridge._
- **What connects `screenshotDir`, `consoleMessages`, `rl` to the rest of the system?**
  _237 weakly-connected nodes found - possible documentation gaps or missing edges._
- **Should `TafForecast` be split into smaller, more focused modules?**
  _Cohesion score 0.05513784461152882 - nodes in this community are weakly interconnected._
- **Should `Illuminate\Console\Command` be split into smaller, more focused modules?**
  _Cohesion score 0.1225296442687747 - nodes in this community are weakly interconnected._