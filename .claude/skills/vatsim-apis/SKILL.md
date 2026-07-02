---
name: vatsim-apis
description: Reference for VATSIM's public and authenticated APIs — the real-time network Data API (online pilots/controllers/ATIS/prefiles), the METAR API, the Events API, the Slurper API, the authenticated Core API (member info, ratings, ATC history, org rosters), and the separate ATC Bookings API. Use this skill whenever the user wants to build, debug, or call anything that talks to vatsim.net/vatsim.dev endpoints, fetch live VATSIM network data, look up METARs from metar.vatsim.net, pull VATSIM events, look up a VATSIM member/CID, or integrate VATSIM ATC booking data — even if they only mention "the VATSIM API" generically or paste one of these URLs without naming the API.
---

# VATSIM APIs

VATSIM (the Virtual Air Traffic Simulation network) exposes several independent
public HTTP APIs, plus one separately-hosted booking API. They are not one
unified service — each has its own base URL, auth model, and update cadence.
Official docs (OpenAPI-rendered, JS-required to see full schemas in a browser):
https://vatsim.dev/services/apis

## Which API do I need?

| Need | API | Auth | Base URL |
|---|---|---|---|
| Who's flying/controlling right now, positions, flight plans, ATIS | **Data API** | None | `https://data.vatsim.net/v3/` |
| METAR for one/many/all airports | **METAR API** | None | `https://metar.vatsim.net/` |
| Upcoming/current network events (used a lot — see `references/events-api.md`) | **Events API** | None | `https://my.vatsim.net/api/v2/events/` |
| Live connection status/position for one known CID (used by AFV) | **Slurper API** | None | `https://slurper.vatsim.net/users/info` |
| Member profile, ratings, flight history, org rosters, ATC history | **Core API** | API key (`X-API-Key` header) | `https://api.vatsim.net/api/` |
| Publish/manage ATC position bookings for a division/subdivision | **ATC Bookings API** | None (GET) / Bearer token (write) | `https://atc-bookings.vatsim.net/api/` |
| Discover current base URLs for the above (in case they change) | **Status endpoint** | None | `https://status.vatsim.net/status.json` |

If unsure which base URL is current, fetch `status.vatsim.net/status.json` first — it's
the canonical service-discovery document VATSIM clients (like vPilot, Euroscope) use.

Read `references/data-api.md`, `references/events-api.md`,
`references/metar-slurper.md`, `references/core-api.md`, or
`references/atc-bookings.md` for full field-level schemas, parameters, and
example requests before writing integration code — don't guess field names
from memory.

## Quick recipes

**Get everyone currently online (pilots, controllers, ATIS, prefiles):**
```
GET https://data.vatsim.net/v3/vatsim-data.json
```
No auth, no params. Regenerates every ~15s server-side; poll no more than every
15s. See `references/data-api.md` for the full response schema (pilots,
controllers, atis, prefiles, servers, facilities, ratings arrays).

**Get a METAR:**
```
GET https://metar.vatsim.net/EGLL          # single airport, plain text
GET https://metar.vatsim.net/EGLL,KJFK,ENGM  # comma-separated, multiple
GET https://metar.vatsim.net/ENG            # 3-letter-or-fewer prefix = wildcard, all matching airports
GET https://metar.vatsim.net/all            # every airport VATSIM has a METAR for
GET https://metar.vatsim.net/EGLL?format=json  # add &format=json for structured {id, metar} objects
```
Default response is `text/plain`, newline-delimited. This is an unauthenticated
convenience endpoint — same underlying data as the documented METAR API's
`GET /:icao`.

**Get upcoming/current events:**
```
GET https://my.vatsim.net/api/v2/events/latest        # all current + upcoming
GET https://my.vatsim.net/api/v2/events/view/{id}      # a single event
GET https://my.vatsim.net/api/v2/events/view/division/{division}  # by division, e.g. SEA
```
Returns `{ "data": [ {...event...} ] }` (single-event variant returns
`{ "data": {...} }`, an object not an array). See `references/events-api.md`
for the full event object schema, the region/subdivision filter variants, and
Laravel-flavored polling recipes — worth reading in full since this is used a lot.

**Look up a member (needs a Core API key):**
```
GET https://api.vatsim.net/api/ratings/{cid}/
Header: X-API-Key: <your key>
```
Core API access for anything beyond public/anonymized data requires a division
or subdivision to request a key from the VATSIM Tech Team. See
`references/core-api.md`.

**Check/manage ATC bookings for a facility:**
```
GET https://atc-bookings.vatsim.net/api/booking
```
Public read, no auth needed for GET. Creating/editing/deleting bookings
(`POST`/`PUT`/`DELETE /booking`) needs a Bearer token issued to divisions/
subdivisions/ARTCCs/vACCs that run active ATC service — request via
https://support.vatsim.net. See `references/atc-bookings.md`.

## General notes that apply across all of these

- **Rate limiting / etiquette**: the Data API regenerates every 15s — polling
  faster just returns the same cached file and wastes bandwidth. Cache
  aggressively and respect this cadence in any app you build (e.g. a Laravel
  scheduled job every 15–60s, not a tight loop).
- **No official client libraries are bundled here** — these are plain REST/JSON
  (or CSV for Slurper, plain text for METAR by default) over HTTPS. Any HTTP
  client works fine (Laravel's `Http::get()`, `fetch`, `curl`, etc.).
- **Terms of Service**: all VATSIM API use is governed by
  https://vatsim.net/docs/policy/user-agreement — don't scrape/store personal
  member data (names, emails) beyond what the public Data/Core API already
  anonymizes/exposes.
- **CIDs vs callsigns**: `cid` is a VATSIM member's permanent numeric ID;
  `callsign` is their current session identifier (aircraft callsign for
  pilots, position callsign like `EGLL_TWR` for controllers) and only exists
  while connected.
