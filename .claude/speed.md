# speed.md — Search/MySQL performance fixes
## This doc has to be invoked by user, don't invoke it yourself.

Context: production MySQL periodically pins at 100% CPU during searches until restarted.
Root causes found by reading the search path. Constraints: **Eloquent/query-builder + standard
Laravel only — no new `DB::raw` beyond what already exists.** Follow repo conventions:
one timestamped migration per schema change, run Pint, verify with `phpunit` suite
(MySQL test DB) and a real search via the `run-where2fly` skill.

A captured production query (arrival search anchored at WADD, airtime 0–24h, 13 score
filters all required) confirms the diagnosis; facts learned from it are folded into the
items below and marked **[sample]**.

Affected files:
- `app/Http/Controllers/API/SearchController.php` (API search)
- `app/Http/Controllers/SearchController.php` (web search — random-anchor + 20-attempt retry loop)
- `app/Models/Airport.php` (scopes: `withinDistance`, `filterByScores`, `sortByScores`)
- `app/Models/AirportScore.php` (`applyCoversEta`, `getTopAirports`)
- `app/Helpers/CalculationHelper.php` (`forecastEtaSql`, `calculateSphericalDestination`)
- `database/migrations/`

Priorities are ordered by expected impact. P1–P3 are the fixes for the CPU lockups;
P4–P6 are cheaper follow-ups.

**Amended 2026-07-07 after code verification** (markers: **[amended]**):
- **P1 found deeper than diagnosed:** the `coordinates` column had no SRID restriction
  (`point NOT NULL`, no `SRID 4326` attribute) — MySQL's optimizer *ignores* SPATIAL
  indexes on non-SRID-restricted columns entirely, so the index was unusable even for
  sargable predicates (including `withinBearing`'s existing `whereWithin`). Fixed by
  migration `2026_07_07_120000_add_srid_to_airport_coordinates` (drop index → modify
  column to `SRID 4326` → rebuild index; all rows already carried SRID 4326). Took ~10s
  on 80k rows. Verified: EXPLAIN went from `type=ALL, rows=80615` to
  `type=range, key=airports_coordinates_spatialindex, rows=643` for a 500 nm search.
- P1 box construction changed — destination-point math undersizes the longitude extent
  at higher latitudes; use the spherical-cap formula, and pad latitude for geodesic edges.
- P2.4 changed — `inRandomOrder()` is `ORDER BY RAND()`, the exact thing commit 66d1b70
  ("moving costly random process to php", 2025-07-28) removed. Random pick stays in PHP.
- P2 randomness note: phase 1 still fetches the *whole* candidate pool (thin rows) and
  the PHP bucket-shuffle is untouched, so a refresh over N identically-scored airports
  still yields a different subset — owner-confirmed requirement.
- P2 correction: nothing reads `score_count` after the shuffle (resources list fields
  explicitly, no blade/macro consumer) — copying it onto hydrated models is parity only.
- P3.4 is stale — the migration's `down()` already targets `airport_scores`. Drop it.
- Side observation (not perf, separate issue): API `sortByScores(array_flip($filterByScores))`
  collapses reasons sharing the same value — two reasons with value 1 flip to one key.

---

## P1 — Spatial index is never used: add a bounding-box pre-filter to `withinDistance`

`Airport::withinDistance` (Airport.php ~L424) uses `whereDistanceSphere` twice
(min + max). `ST_Distance_Sphere` is **not sargable** — MySQL cannot use the SPATIAL
index on `airports.coordinates` for it, so every search full-scans ~80k airports
computing spherical distance, twice per row. This is the primary CPU burner, and the
same non-indexed rows then feed the expensive score subqueries (P4).

Fix (inside the `withinDistance` scope, keep both existing `whereDistanceSphere` calls
as the precise check):

1. **[amended]** Build the box in plain PHP with the standard spherical-cap bounds —
   NOT with `calculateSphericalDestination()` E/W points: the extreme longitude of a
   circle is *poleward* of the due-east point, so destination-point math undersizes the
   box at higher latitudes and silently drops valid results (box is prune-only).
   With `D = radius/R` in radians (radius = maxDistance ×1852 ×1.05 margin, R = 6371009):
   `Δlat = D`, `Δlon = asin(sin(D) / cos(lat))`. Additionally pad *both* latitude bounds
   by the geodesic edge bulge: MySQL treats SRID-4326 polygon edges as geodesics, which
   sag poleward relative to a parallel — worst-case bulge ≈ `(lonSpanRad)²/8 × 0.5`.
   Then construct a `Polygon`/`LineString` of the 4 corners exactly like `withinBearing`
   (`MatanYadaev\EloquentSpatial\Objects\*`, `Srid::WGS84`).
2. Prepend `$query->whereWithin('coordinates', $polygon)` — the package scope; MBR
   functions DO use the spatial index. The `whereDistanceSphere` pair stays and trims
   the box corners.
3. **[amended]** Guard rails — skip the pre-filter (fall through to current behaviour)
   when: `maxDistance > ~4000` nm; the asin argument in Δlon is ≥ 1 (the circle wraps a
   pole — this IS the pole guard); the padded box reaches beyond ±85° latitude; or the
   lon bounds cross the antimeridian (west < −180 or east > 180). Correctness over
   cleverness.
4. Apply in the scope itself so **both** controllers and any future caller benefit.
5. **[sample]** Also in this scope: skip the `whereDistanceSphere(..., '>=', min)` call
   entirely when `$minDistance <= 0`. The captured query computes `ST_DISTANCE_SPHERE >= '0'`
   per row — a no-op that doubles the spherical math. (Default airtimeMin is 0, so this is
   the common case.)
6. **[sample]** Expectation-setting: the captured query had maxDistance 22,668,480 m
   (airtimeMax 24h → planet-wide), where the guard in (3) applies and the box prunes
   nothing. P1 fixes regional searches; global ones are fixed by P2 + P3.

Tests: add a Feature test asserting a search anchored at an antimeridian airport (e.g.
NZAA→ east) and a high-latitude anchor (BIRK/ENSB) still return the same airports as
before the change (seed a couple of known-distance airports via `TestAirportSeeder`
pattern). Existing search tests must stay green.

## P2 — Unbounded `->get()` hydrates the world; split into ID phase + hydrate phase

Both search controllers run the fully-filtered query with
`->with('runways','scores','metar','taf.forecasts'[, sceneries])->get()`, then bucket-
shuffle in PHP and `take(20)`. A broad search (Anywhere, 0–24h, GA) matches thousands of
airports → giant result set, giant `WHERE IN` eager loads, hundreds of MB of hydration.

**[sample]** Worse: `sortByScores` selects `airports.*`, which includes the `coordinates`
GEOMETRY column, under a `GROUP BY` + `ORDER BY score_count`. MySQL cannot keep an
internal temp table in memory when it contains a GEOMETRY/BLOB column, so **every search
materialises an on-disk temp table** of (airports × joined score rows) and filesorts it.
This is the single biggest cost in the captured query and is fully eliminated by the
phase-1 ID-only select below (id + score_count only → in-memory temp table).

Keep the current randomness semantics **exactly** (PHP bucket shuffle over the full
candidate pool — do NOT move the shuffle into SQL, per owner decision). Change only what
gets fetched:

1. **Phase 1 (pool):** same query chain, but no `->with(...)` and select only what the
   shuffle needs: `airports.id` + the `score_count` produced by `sortByScores`.
   `sortByScores` already does `selectRaw('airports.*', ...)` — parameterise/adjust so the
   pool query selects `airports.id` instead of `airports.*` (e.g. call `->select('airports.id')`
   before the scope and change the scope's `selectRaw` to `->addSelect(DB::raw(...score_count...))`
   — the `COUNT(DISTINCT ...)` aggregate select already exists; reusing it is not a new raw query).
   Keep `has('metar')`, keep all filters. `->get()` this thin pool.
2. **Phase 2 (hydrate):** bucket-shuffle + `take(20)` on the pool exactly as today, then
   `Airport::with('runways','scores','metar','taf.forecasts')->findMany($ids)`, re-order to
   the shuffled order (`->sortBy(fn ($a) => array_search($a->id, $ids))->values()`), and
   copy each airport's `score_count` from the pool rows. **[amended]** Verified: nothing
   actually reads `score_count` after the shuffle (resources enumerate fields explicitly,
   no blade/macro consumer) — the copy is attribute-parity with the old full-row select,
   not a functional requirement.
3. Apply to: API `SearchController::search`, web `SearchController::search` destination
   query (which also eager-loads `sceneryDevelopers.sceneries.simulator`). Note the web
   destination query calls `sortByScores` with an *empty* array when neither sort toggle
   is on → the scope no-ops → pool rows carry no `score_count`; the phase-2 code must not
   assume the attribute exists (the bucket shuffle already tolerates this today).
4. **[amended — do NOT use `inRandomOrder()`]** Web **random-anchor** query (web
   controller ~L189–206): it never calls `sortByScores`, so the bucket-shuffle there is
   one bucket → plain "pick one uniformly at random". `inRandomOrder()` would be
   `ORDER BY RAND()` — exactly what commit 66d1b70 moved out of MySQL after the 2025
   lockups; do not reintroduce it. Instead: run the same filtered query as
   `->pluck('airports.id')` (no eager loads, no hydration), pick `$ids->random()` in
   **PHP**, then `Airport::with('runways','scores','metar')->find($id)`. Same uniform
   distribution as today's shuffle+`->random()`. Fetch the id pool **once, before** the
   20-attempt retry loop and re-draw per attempt — the pool is identical between attempts,
   so this also delivers the random-anchor half of P5 for free.

Tests: existing Feature search tests must pass unchanged; add an assertion that a search
response still carries `score_count`-ordered results and ≤20 suggestions.

## P3 — Missing / broken indexes (one migration per change)

1. **`airports.icao` has NO index.** Anchor lookup (`where('icao')->orWhere('local_code')`),
   the `AirportExists` validation rule, `notIcao`, `returnOnlyWhitelistedIcao(whereIn icao)`,
   and `getTopAirports` whitelist all scan 80k rows. Add: plain index on `icao`
   (migration `index_airports_icao`). Add a separate index on `local_code`
   (`index_airports_local_code`). Don't make `icao` unique without first verifying no dupes.
2. **`airport_scores`**: only `(airport_id, reason)` exists. `applyCoversEta` filters on
   `source` + `valid_from`/`valid_to` in every EXISTS / join. Add composite
   `(airport_id, source, valid_from, valid_to)` (migration `index_airport_scores_source_window`).
   Do NOT add more than this one — the table is delete+rebuilt constantly by the fetch
   commands; every extra index taxes those bulk writes.
3. **`taf_forecasts`**: only the `taf_id` FK index. The METAR-fallback `whereNotExists` in
   `applyCoversEta` runs per score row per candidate and ranges on `valid_from`/`valid_to`.
   Add `(taf_id, valid_from, valid_to)` (migration `index_taf_forecasts_window`).
4. ~~**BUG — `2025_04_20_091732_index_scores.php`:** its `down()` drops the index from
   `runways` instead of `airport_scores`.~~ **[amended — stale]** Verified 2026-07-07:
   the file's `down()` already targets `airport_scores` correctly. Nothing to do.
5. **Verify, don't assume:** `metars.airport_id` was created `string->unique()`, then
   `2026_07_03_100003` did `->change()` to unsignedBigInteger + FK. Confirm the unique
   index survived (`Schema::hasIndex`/`SHOW INDEX FROM metars` via tinker). `has('metar')`
   is a correlated EXISTS on it in every search. If missing, add a unique index migration.
6. Optional, lower value (do last, skip if diff is getting large): `runways (airport_id, lighted)`
   for `filterRunwayLights`; `flights (airport_dep_id, arr_icao, seen_counter)` to mirror
   the arrival-side composites for `departureFlights`-direction route filters.

## P3b — [sample] API accepts arbitrary/unbounded score filters → guaranteed-empty full-price queries

The captured query requires a reason `METAR_VATSIM_ATC` via EXISTS — **that reason does
not exist** (valid list: `ScoreController::$score_types`; the real one is `VATSIM_ATC`).
The API route validates `scores` only as `['sometimes', 'array']`
(API/SearchController.php ~L30), so the client controls both the reason keys and how many
there are: each key becomes a correlated EXISTS/NOT-EXISTS subquery. An invalid key makes
the whole AND chain unsatisfiable — MySQL still runs the full scan + join + group-by +
filesort and returns 0 rows. Unbounded key count is also a trivial DoS lever.

Fix: validate like the web route already does — apply the existing `ValidScores` rule
(or equivalent) to the API `scores` field: keys must be in
`array_keys(ScoreController::$score_types)`, values in `{-1, 0, 1}`. This caps the EXISTS
count at 13 and makes impossible-filter queries fail fast at validation instead of in MySQL.
Check `app/Rules/ValidScores.php` fits the API payload shape; adapt if the shapes differ.
(`METAR_VATSIM_ATC` does not appear anywhere in this repo's JS/Blade — the repo's own
form sends the correct keys — so it came from an external/stale API client. Server-side
validation is the complete fix; nothing to hunt in the frontend.)

## P4 — Repeated expensive expressions in `applyCoversEta` (mitigated by P1/P2, document only)

For arrival searches, `$eta` is `forecastEtaSql()` — a `ST_DISTANCE_SPHERE(...)` DATE_ADD
expression. `applyCoversEta` inlines it 3–4×, and it is applied once per `filterByScores`
reason (each a separate `whereHas` EXISTS) plus in the `sortByScores` join plus the
correlated `taf_forecasts` NOT EXISTS. With 3 score filters that's ~12+ spherical-distance
evaluations per candidate row. There is no clean Eloquent way to compute it once per row
(MySQL can't reference a select alias in WHERE/JOIN); P1 shrinks the row count and P3.2/P3.3
make each probe indexed, which is the realistic fix. **No code change required in this pass**
— just don't add more `coversEta` call sites inside per-row subqueries.

## P5 — Web search 20-attempt retry loop re-runs everything

`SearchController::search` (web) wraps anchor selection + the full destination query in a
`for ($attempt = 1..20)` loop. With a supplied `icao` the anchor never changes, so failed
attempts re-run an **identical** destination query 20× — pure DB load, same empty result.
**[sample]** This is the amplification half of the incident pattern: an unsatisfiable
filter set (see P3b) produces a guaranteed-empty result, and this loop then re-runs the
on-disk-temp-table query (P2) twenty times back-to-back.
Fix: if `isset($data['icao'])` (anchor fixed and query deterministic apart from the shuffle),
run one attempt only — break/return after the first empty result. **[amended]** The
random-anchor half is already delivered by the revised P2.4 (id pool fetched once outside
the loop, one random draw per attempt) — only the fixed-icao early-exit remains for P5.

## P6 — `AirportScore::getTopAirports` (homepage/top lists): cache it

Groups + `coversEta(now())` over the whole `airport_scores` table with a join to airports,
on every hit of TopController (web + API). Data only changes when fetch commands run
(every 30 min, offset :05/:35). Wrap in `Cache::remember("top-airports:{$continent}:{$exclude}:{$limit}:" . ($whitelist ? md5(json_encode($whitelist)) : 'all'), 300, ...)`
— the `cache` table already exists (migration 2026_04_05). Skip caching when a whitelist
is user-supplied if hit rate would be nil; cache only the null-whitelist variants.

---

## Verification checklist (after implementing)

1. `./vendor/bin/pint` on touched PHP.
2. Full `phpunit` suite (real MySQL, `where2fly_test`) — watch for the seeder-slowdown
   symptom noted in CLAUDE.md.
3. `run-where2fly`: execute an Anywhere/GA/0–24h arrival search (worst case) and a
   departure search; confirm results render with score icons and ≤20 suggestions.
4. Tinker EXPLAIN spot-check: the phase-1 pool query must show the spatial index
   (`possible_keys`/`key` includes the SPATIAL index) and no full scan on `airports`;
   the score EXISTS probes must use the new composite indexes.
5. `php artisan migrate` on a copy of prod-sized data if available — the two new
   `airport_scores`/`taf_forecasts` indexes build on large tables; note expected
   build time in the PR description.
