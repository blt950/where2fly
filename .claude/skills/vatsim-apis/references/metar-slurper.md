# METAR API, Slurper API

Both are public, unauthenticated, GET-only. (Looking for the Events API? See
`references/events-api.md` — it has its own file since it's used heavily.)

---

## METAR API

Base URL: `https://metar.vatsim.net/`
Docs: https://vatsim.dev/api/metar-api

### `GET /:icao`

Returns current METAR(s) for the requested airport(s).

**Path parameter** `icao` (required) — one or more ICAO codes, comma-delimited.
Special cases:
- A single code (e.g. `EGLL`) → that airport's METAR.
- Comma-delimited list (e.g. `EGLL,KJFK,ENGM`) → METARs for each, newline-delimited.
- `all` → METARs for every airport VATSIM currently has data for.
- Any string of **3 characters or fewer** is treated as a prefix wildcard —
  e.g. `EN` returns METARs for every airport starting with `EN` (all Norwegian
  ICAOs), `K` returns every US airport.

**Query parameter** `format` (optional) — `text` (default) or `json`.

**Responses:**
- `format=text` (default): `text/plain`, one raw METAR string per line, e.g.
  `EGLL 021450Z 24012KT 9999 FEW035 18/11 Q1015 NOSIG`
- `format=json`: `application/json` array of `{ "id": "<ICAO>", "metar": "<raw METAR string>" }`

```
GET https://metar.vatsim.net/EGLL
GET https://metar.vatsim.net/EGLL,KJFK
GET https://metar.vatsim.net/ENG            # wildcard: all ENG* airports
GET https://metar.vatsim.net/all
GET https://metar.vatsim.net/EGLL?format=json
```

Note: `metar.vatsim.net/all` and `metar.vatsim.net/<ICAO>` (the URLs the user
already knows) are the same public endpoint described by this documented API —
there's no separate "undocumented" version, it's just not always linked
prominently from the main API index.

---

## Slurper API

Base URL: `https://slurper.vatsim.net/`
Docs: https://vatsim.dev/api/slurper-api

Originally built for Audio for VATSIM (AFV) clients to resolve a member's
current connection/position; still useful for anything that needs a quick
"is this CID online, and where" lookup without parsing the full Data API feed.

### `GET /users/info`

**Query parameter** `cid` (required, int) — VATSIM ID to look up.

```
GET https://slurper.vatsim.net/users/info?cid=1234567
```

**Response**: `text/plain` CSV (not JSON), one line per active connection for
that CID, fields in order:
1. VATSIM ID
2. Callsign
3. Facility type — `pilot` or `atc`
4. Frequency (empty for pilots)
5. Visual range (empty for pilots)
6. Latitude
7. Longitude
8. Secondary positions — four-or-more lat/lon pairs if the controller has set
   a multi-point coverage area
9. Trailing comma

Empty response body means the CID is not currently connected.
