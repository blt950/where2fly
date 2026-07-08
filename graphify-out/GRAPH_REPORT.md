# Graph Report - /app  (2026-07-08)

## Corpus Check
- Large corpus: 2257 files · ~1,024,871 words. Semantic extraction will be expensive (many Claude tokens). Consider running on a subfolder.

## Summary
- 1219 nodes · 1838 edges · 227 communities (189 shown, 38 thin omitted)
- Extraction: 87% EXTRACTED · 13% INFERRED · 0% AMBIGUOUS · INFERRED: 247 edges (avg confidence: 0.8)
- Token cost: 43,914 input · 0 output

## Community Hubs (Navigation)
- [[_COMMUNITY_Community 0|Community 0]]
- [[_COMMUNITY_Community 1|Community 1]]
- [[_COMMUNITY_Community 2|Community 2]]
- [[_COMMUNITY_Community 3|Community 3]]
- [[_COMMUNITY_Community 4|Community 4]]
- [[_COMMUNITY_Community 5|Community 5]]
- [[_COMMUNITY_Community 6|Community 6]]
- [[_COMMUNITY_Community 7|Community 7]]
- [[_COMMUNITY_Community 8|Community 8]]
- [[_COMMUNITY_Community 9|Community 9]]
- [[_COMMUNITY_Community 10|Community 10]]
- [[_COMMUNITY_Community 11|Community 11]]
- [[_COMMUNITY_Community 12|Community 12]]
- [[_COMMUNITY_Community 13|Community 13]]
- [[_COMMUNITY_Community 14|Community 14]]
- [[_COMMUNITY_Community 15|Community 15]]
- [[_COMMUNITY_Community 16|Community 16]]
- [[_COMMUNITY_Community 17|Community 17]]
- [[_COMMUNITY_Community 19|Community 19]]
- [[_COMMUNITY_Community 20|Community 20]]
- [[_COMMUNITY_Community 21|Community 21]]
- [[_COMMUNITY_Community 22|Community 22]]
- [[_COMMUNITY_Community 23|Community 23]]
- [[_COMMUNITY_Community 24|Community 24]]
- [[_COMMUNITY_Community 25|Community 25]]
- [[_COMMUNITY_Community 26|Community 26]]
- [[_COMMUNITY_Community 27|Community 27]]
- [[_COMMUNITY_Community 28|Community 28]]
- [[_COMMUNITY_Community 29|Community 29]]
- [[_COMMUNITY_Community 30|Community 30]]
- [[_COMMUNITY_Community 31|Community 31]]
- [[_COMMUNITY_Community 32|Community 32]]
- [[_COMMUNITY_Community 33|Community 33]]
- [[_COMMUNITY_Community 34|Community 34]]
- [[_COMMUNITY_Community 35|Community 35]]
- [[_COMMUNITY_Community 36|Community 36]]
- [[_COMMUNITY_Community 37|Community 37]]
- [[_COMMUNITY_Community 38|Community 38]]
- [[_COMMUNITY_Community 39|Community 39]]
- [[_COMMUNITY_Community 40|Community 40]]
- [[_COMMUNITY_Community 41|Community 41]]
- [[_COMMUNITY_Community 42|Community 42]]
- [[_COMMUNITY_Community 43|Community 43]]
- [[_COMMUNITY_Community 44|Community 44]]
- [[_COMMUNITY_Community 45|Community 45]]
- [[_COMMUNITY_Community 69|Community 69]]
- [[_COMMUNITY_Community 108|Community 108]]
- [[_COMMUNITY_Community 109|Community 109]]
- [[_COMMUNITY_Community 110|Community 110]]
- [[_COMMUNITY_Community 111|Community 111]]
- [[_COMMUNITY_Community 112|Community 112]]
- [[_COMMUNITY_Community 113|Community 113]]
- [[_COMMUNITY_Community 114|Community 114]]
- [[_COMMUNITY_Community 115|Community 115]]
- [[_COMMUNITY_Community 116|Community 116]]
- [[_COMMUNITY_Community 117|Community 117]]
- [[_COMMUNITY_Community 119|Community 119]]
- [[_COMMUNITY_Community 120|Community 120]]
- [[_COMMUNITY_Community 121|Community 121]]
- [[_COMMUNITY_Community 122|Community 122]]
- [[_COMMUNITY_Community 123|Community 123]]
- [[_COMMUNITY_Community 124|Community 124]]
- [[_COMMUNITY_Community 125|Community 125]]
- [[_COMMUNITY_Community 126|Community 126]]
- [[_COMMUNITY_Community 127|Community 127]]
- [[_COMMUNITY_Community 128|Community 128]]
- [[_COMMUNITY_Community 129|Community 129]]
- [[_COMMUNITY_Community 130|Community 130]]
- [[_COMMUNITY_Community 131|Community 131]]
- [[_COMMUNITY_Community 132|Community 132]]
- [[_COMMUNITY_Community 133|Community 133]]
- [[_COMMUNITY_Community 134|Community 134]]

## God Nodes (most connected - your core abstractions)
1. `Airport` - 84 edges
2. `User` - 68 edges
3. `SearchTest` - 48 edges
4. `AirportScore` - 45 edges
5. `UserList` - 35 edges
6. `Simulator` - 33 edges
7. `TestCase` - 28 edges
8. `ApiTest` - 24 edges
9. `Metar` - 23 edges
10. `Scenery` - 23 edges

## Surprising Connections (you probably didn't know these)
- `up()` --calls--> `Airport`  [INFERRED]
  database/migrations/2024_07_22_072158_add_geospacial_airport_coordinates.php → app/Models/Airport.php
- `Where2Fly Application (Laravel 13 + Blade + React map canvas)` --semantically_similar_to--> `Where2Fly Project (README overview)`  [INFERRED] [semantically similar]
  CLAUDE.md → README.md
- `up()` --calls--> `FlightAircraft`  [INFERRED]
  database/migrations/2024_03_17_210345_add_aircraft_to_new_table.php → app/Models/FlightAircraft.php
- `up()` --calls--> `Scenery`  [INFERRED]
  database/migrations/2024_09_29_111847_convert_scenery_sims_to_pivot_table.php → app/Models/Scenery.php
- `up()` --calls--> `Simulator`  [INFERRED]
  database/migrations/2025_02_01_085035_transfer_sceneries_to_pivot.php → app/Models/Simulator.php

## Import Cycles
- 1-file cycle: `resources/js/bootstrap.js -> resources/js/bootstrap.js`
- 1-file cycle: `resources/js/nouislider.js -> resources/js/nouislider.js`

## Hyperedges (group relationships)
- **CI Pipeline: lint -> tests -> container build, all via shared setup-dependencies action** — _github_workflows_ci_lint_job, _github_workflows_ci_tests_job, _github_workflows_ci_build_container_job, _github_actions_setup_dependencies_action_setup_dependencies [EXTRACTED 1.00]
- **Shared where2fly_test MySQL contract between CI services and local test setup** — _github_workflows_ci_tests_job, _github_workflows_tests_phpunit_job, claude_testing_mysql [INFERRED 0.95]
- **where2fly container image lifecycle: CI builds and pushes to ghcr.io, prod compose pulls it, dev compose builds locally instead** — _github_workflows_ci_build_container_job, docker_compose_web_service, docker_compose_dev_web_service [INFERRED 0.85]

## Communities (227 total, 38 thin omitted)

### Community 0 - "Community 0"
Cohesion: 0.06
Nodes (7): FetchMetars, FetchTafs, AviationWeatherHelper, WeatherScoreHelper, Metar, TafForecast, TafForecastTest

### Community 1 - "Community 1"
Cohesion: 0.08
Nodes (25): AirportCard(), CardContext, MapContext, FlightsCard(), getInitMapPosition(), isDefaultView(), Map(), MapBound() (+17 more)

### Community 2 - "Community 2"
Cohesion: 0.06
Nodes (16): AccountClearUnverified, CalcFlights, CleanupSceneries, CreateApiKey, EnrichAirports, EnrichFlights, EnrichSceneries, FetchBookings (+8 more)

### Community 3 - "Community 3"
Cohesion: 0.08
Nodes (9): AirportScore, Builder, Carbon, Collection, View, ScoreIcon, Component, ScorePredictionTest (+1 more)

### Community 4 - "Community 4"
Cohesion: 0.07
Nodes (18): AircraftHelper, Request, SearchController, Request, TopController, ScoreController, Request, View (+10 more)

### Community 6 - "Community 6"
Cohesion: 0.08
Nodes (9): getCountryName(), rwyIdentToHeading(), MapHelper, Collection, SceneryHelper, MapController, Request, Flight (+1 more)

### Community 7 - "Community 7"
Cohesion: 0.09
Nodes (9): User, UserListPolicy, UserPolicy, Authenticatable, CanResetPassword, MustVerifyEmail, Notifiable, AdminTest (+1 more)

### Community 8 - "Community 8"
Cohesion: 0.07
Nodes (29): dependencies, bootstrap, @elfalem/leaflet-curve, @joergdietrich/leaflet.terminator, laravel-vite-plugin, leaflet, leaflet.markercluster, lodash (+21 more)

### Community 9 - "Community 9"
Cohesion: 0.12
Nodes (3): Simulator, UserList, UserListTest

### Community 10 - "Community 10"
Cohesion: 0.09
Nodes (8): AirportFilterHelper, distance(), Request, UserController, CollectionAirportFilter, AppServiceProvider, EmailVerificationRequest, ServiceProvider

### Community 11 - "Community 11"
Cohesion: 0.12
Nodes (13): AirportExists, Closure, FlightDirection, Closure, Closure, ValidAircrafts, Closure, ValidAirlines (+5 more)

### Community 12 - "Community 12"
Cohesion: 0.09
Nodes (24): Ko-fi Sponsorship (where2fly), Bug Report Issue Template, Issue Template Config (blank issues disabled), Feature Request Issue Template, Aircraft Type Codeletters (GA, GAT, GTP, JS, JM, JML, JL, JXL), API Attribution Requirement (Powered by Where2Fly link), Score Reason Codes (METAR_WINDY..METAR_THUNDERSTORM, VATSIM_ATC, VATSIM_EVENT, VATSIM_POPULAR), POST /api/search Endpoint (departure/arrival, destinations, codeletter, score and airport filters) (+16 more)

### Community 13 - "Community 13"
Cohesion: 0.14
Nodes (6): Airline, Controller, Event, Taf, HasFactory, Model

### Community 14 - "Community 14"
Cohesion: 0.13
Nodes (4): Booking, Runway, TestAirportSeeder, Seeder

### Community 20 - "Community 20"
Cohesion: 0.23
Nodes (5): BaseTestCase, CreatesApplication, RefreshDatabase, ResponseTest, TestCase

### Community 21 - "Community 21"
Cohesion: 0.18
Nodes (4): LoginController, Request, Request, SceneryController

### Community 22 - "Community 22"
Cohesion: 0.13
Nodes (4): Scenery, SceneryPolicy, up(), up()

### Community 23 - "Community 23"
Cohesion: 0.12
Nodes (17): require, graham-campbell/markdown, guzzlehttp/guzzle, laravel/framework, laravel/nightwatch, laravel/sanctum, laravel/tinker, laravel/ui (+9 more)

### Community 24 - "Community 24"
Cohesion: 0.17
Nodes (9): AdminVariables, Closure, Request, FeedbackVariables, Closure, Request, Closure, Request (+1 more)

### Community 25 - "Community 25"
Cohesion: 0.16
Nodes (3): CalculationHelper, Carbon, Coordinate

### Community 26 - "Community 26"
Cohesion: 0.26
Nodes (4): Controller, Request, UserListController, AuthorizesRequests

### Community 27 - "Community 27"
Cohesion: 0.23
Nodes (5): ApiToken, Closure, Request, ApiKey, ApiLog

### Community 28 - "Community 28"
Cohesion: 0.27
Nodes (10): Setup Dependencies Composite Action (PHP 8.3.2, composer cache, optional Node 22), CI Build Container Job (pushes ghcr.io/blt950/where2fly, injects private fonts repo), CI Lint Job (Pint --test), CI PHPUnit Job (MySQL 8 service, where2fly_test DB), Legacy Linting Workflow (disabled, superseded by ci.yaml), Legacy Tests Workflow (disabled, superseded by ci.yaml), Testing Against Real MySQL (where2fly_test database, PHPUnit only, no sqlite fallback), Dev Compose Web Service (builds local Dockerfile, bind-mounts repo into container where2fly) (+2 more)

### Community 30 - "Community 30"
Cohesion: 0.20
Nodes (9): autoload-dev, psr-4, description, keywords, license, name, prefer-stable, Tests\\ (+1 more)

### Community 31 - "Community 31"
Cohesion: 0.22
Nodes (9): require-dev, barryvdh/laravel-debugbar, fakerphp/faker, laravel/pint, laravel/sail, mockery/mockery, nunomaduro/collision, phpunit/phpunit (+1 more)

### Community 32 - "Community 32"
Cohesion: 0.28
Nodes (3): AirportFactory, UserFactory, Factory

### Community 36 - "Community 36"
Cohesion: 0.33
Nodes (6): php-http/discovery, config, allow-plugins, optimize-autoloader, preferred-install, sort-packages

### Community 37 - "Community 37"
Cohesion: 0.33
Nodes (6): autoload, files, psr-4, App\\, Database\\Factories\\, Database\\Seeders\\

### Community 38 - "Community 38"
Cohesion: 0.40
Nodes (5): scripts, post-autoload-dump, post-create-project-cmd, post-root-package-install, post-update-cmd

### Community 39 - "Community 39"
Cohesion: 0.40
Nodes (4): layouts.footer, parts.map, layouts.header, layouts.menu

### Community 40 - "Community 40"
Cohesion: 0.60
Nodes (3): contractFilters(), expandFilters(), toggleFilters()

### Community 41 - "Community 41"
Cohesion: 0.40
Nodes (4): front.parts.form, front.parts.sliders, front.parts.tabs, layouts.title

### Community 42 - "Community 42"
Cohesion: 0.40
Nodes (4): front.parts.form, front.parts.sliders, front.parts.tabs, layouts.title

### Community 45 - "Community 45"
Cohesion: 0.67
Nodes (3): extra, laravel, dont-discover

## Ambiguous Edges - Review These
- `Data Ingestion Pipeline (update:data -> fetch:metars/tafs/vatsim; fetch:bookings; AWC bulk cache files, not per-airport calls)` → `External Data Sources (OurAirports, Airlabs, metar.vatsim.net, api.met.no TAF, FSAddonCompare, flagicons)`  [AMBIGUOUS]
  README.md · relation: conceptually_related_to

## Knowledge Gaps
- **122 isolated node(s):** `name`, `type`, `description`, `keywords`, `license` (+117 more)
  These have ≤1 connection - possible missing edges or undocumented components.
- **38 thin communities (<3 nodes) omitted from report** — run `graphify query` to explore isolated nodes.

## Suggested Questions
_Questions this graph is uniquely positioned to answer:_

- **What is the exact relationship between `Data Ingestion Pipeline (update:data -> fetch:metars/tafs/vatsim; fetch:bookings; AWC bulk cache files, not per-airport calls)` and `External Data Sources (OurAirports, Airlabs, metar.vatsim.net, api.met.no TAF, FSAddonCompare, flagicons)`?**
  _Edge tagged AMBIGUOUS (relation: conceptually_related_to) - confidence is low._
- **Why does `Airport` connect `Community 15` to `Community 0`, `Community 2`, `Community 35`, `Community 4`, `Community 3`, `Community 6`, `Community 69`, `Community 34`, `Community 9`, `Community 44`, `Community 13`, `Community 14`, `Community 19`, `Community 20`, `Community 21`, `Community 25`, `Community 26`?**
  _High betweenness centrality (0.078) - this node is a cross-community bridge._
- **Why does `User` connect `Community 7` to `Community 2`, `Community 9`, `Community 10`, `Community 13`, `Community 16`, `Community 17`, `Community 18`, `Community 20`, `Community 21`, `Community 22`?**
  _High betweenness centrality (0.052) - this node is a cross-community bridge._
- **Why does `SearchTest` connect `Community 5` to `Community 9`, `Community 20`?**
  _High betweenness centrality (0.031) - this node is a cross-community bridge._
- **Are the 22 inferred relationships involving `Airport` (e.g. with `.handle()` and `.handle()`) actually correct?**
  _`Airport` has 22 INFERRED edges - model-reasoned connections that need verification._
- **Are the 46 inferred relationships involving `User` (e.g. with `.handle()` and `.showAdmin()`) actually correct?**
  _`User` has 46 INFERRED edges - model-reasoned connections that need verification._
- **Are the 13 inferred relationships involving `AirportScore` (e.g. with `.scoreBookings()` and `.scoreMetars()`) actually correct?**
  _`AirportScore` has 13 INFERRED edges - model-reasoned connections that need verification._