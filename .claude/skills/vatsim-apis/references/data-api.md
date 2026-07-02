# Data API

Base URL: `https://data.vatsim.net/v3/`
Auth: none. Public.
Docs: https://vatsim.dev/api/data-api

This is the real-time snapshot of everyone/everything currently on the network.
It's a static JSON file regenerated server-side roughly every 15 seconds —
treat it as a poll-and-cache target, not a streaming API.

## Endpoints

| Method | Path | Description |
|---|---|---|
| GET | `/vatsim-data.json` | Full live network data feed (pilots, controllers, ATIS, prefiles, servers, facilities, ratings) |
| GET | `/vatsim-servers.json` | List of live FSD (Flight Simulation Daemon) servers |
| GET | `/all-servers.json` | List of all FSD servers, including sweatbox/training servers |
| GET | `/sweatbox-servers.json` | List of sweatbox-only (training) servers |
| GET | `/transceivers-data.json` | Live radio transceiver data (used for e.g. VHF range/frequency tooling) |

Always confirm current file names against `https://status.vatsim.net/status.json`
before hardcoding — VATSIM has changed these paths across major versions
(v2 → v3) before.

## `GET /vatsim-data.json` — response schema

Top-level object:

- `general` — feed metadata
  - `version` (int) — major version of the data feed
  - `update_timestamp` (date-time) — when this feed was generated
  - `connected_clients` (int) — total pilots + controllers + ATIS connected
  - `unique_users` (int) — total unique users connected
  - `reload`, `update` — deprecated, ignore
- `pilots[]` — connected pilots
  - `cid` (int), `name` (string), `callsign` (string), `server` (string)
  - `pilot_rating` (int), `military_rating` (int)
  - `latitude`, `longitude` (number, deg), `altitude` (int, ft MSL)
  - `groundspeed` (int, kts), `heading` (int, deg magnetic)
  - `transponder` (string), `qnh_i_hg` (number), `qnh_mb` (int)
  - `flight_plan` (object, absent if none filed):
    - `flight_rules` (`I` or `V`), `aircraft`, `aircraft_faa`, `aircraft_short`
    - `departure`, `arrival`, `alternate` (ICAO strings)
    - `deptime`, `enroute_time`, `fuel_time` (strings)
    - `remarks`, `route`, `revision_id`, `assigned_transponder`
  - `logon_time` (date-time), `last_updated` (date-time)
- `controllers[]` — connected ATC positions
  - `cid`, `name`, `callsign`, `frequency` (MHz string), `facility` (int, see `facilities[]`)
  - `rating` (int, see `ratings[]`), `server`, `visual_range` (NM, int)
  - `text_atis` (string[]) — controller info lines
  - `last_updated`, `logon_time`
- `atis[]` — same shape as `controllers[]` plus `atis_code` (current phonetic
  letter, e.g. `"I"`)
- `servers[]` — FSD servers
  - `ident`, `hostname_or_ip`, `location`, `name`
  - `client_connections_allowed` (bool), `is_sweatbox` (bool)
- `prefiles[]` — flight plans filed but not yet connected
  - `cid`, `callsign`, `flight_plan` (same shape as pilots'), `last_updated`
- `facilities[]` — lookup table: `id` (int) → `short` (3-letter code, e.g. `TWR`,
  `CTR`) → `long_name`
- `ratings[]` — controller rating lookup: `id` → `short_name` (e.g. `S1`, `C3`) → `long_name`
- `pilot_ratings[]` — pilot rating lookup: `id` → `short_name` → `long_name`
- `military_ratings[]` — military rating lookup, same shape

### Example: filter to pilots inbound to a specific airport (pseudocode)
```php
$data = Http::get('https://data.vatsim.net/v3/vatsim-data.json')->json();
$inbound = collect($data['pilots'])
    ->filter(fn ($p) => data_get($p, 'flight_plan.arrival') === 'ENGM');
```

### Example: find all online controllers at a facility
```php
$controllers = collect($data['controllers'])
    ->filter(fn ($c) => str_starts_with($c['callsign'], 'ENGM'));
```

## `GET /vatsim-servers.json`, `/all-servers.json`, `/sweatbox-servers.json`

Each returns an array of server objects shaped like the `servers[]` entries
above (`ident`, `hostname_or_ip`, `location`, `name`,
`client_connections_allowed`, `is_sweatbox`). Useful for building a "choose a
server" UI for a client, or for status dashboards.

## `GET /transceivers-data.json`

Live per-client transceiver (radio) data — frequency, position, and
transmit/receive flags for each connected client's radios. Primarily used by
tools that need to reason about VHF range/coverage (e.g. mapping who can
actually hear whom). Field-level shape can drift; fetch a live sample and
inspect it if you need this endpoint, since it's less commonly documented than
the main feed.
