# Core API

Base URL: `https://api.vatsim.net/api/`
Auth: API key, required for most non-public endpoints
Docs: https://vatsim.dev/api/core-api

This is the authenticated API for member data, ratings, org rosters, and ATC
history. The public can access anonymized data (no names/emails); divisions
and subdivisions can get a key with elevated access to their own members'
personal info and roster-management endpoints. Request a key via the VATSIM
Tech Team: https://support.vatsim.net/open.php?topicId=16

## Authentication

Send your key in a header:
```
X-API-Key: <your-api-v2-key>
```
A legacy `LegacyAuth` scheme exists for backwards compatibility with API v1
integrations — don't use it for new integrations, use `X-API-Key`.

## Endpoint groups

### members
- `GET /members/{cid}/flightplans` — a member's filed flight plans
- `GET /members/{cid}/history` (or similar `history_atc`/`history_pilot`
  variants) — a member's previous pilot/ATC sessions
- Additional `members` sub-endpoints exist for ratings and profile info —
  check https://vatsim.dev/api/core-api/members-api-retrieve-member-flightplans
  for the current full list, since this group has grown over time.

### community
- `GET /v2/members/discord/{discord_user_id}` — resolve a VATSIM CID from a
  linked Discord user ID (requires the member to have linked Discord via
  https://community.vatsim.net). Returns `{ "id": "<discord_id>", "user_id": "<cid>" }`,
  or 404 with `{ "detail": "Not Found." }` if unlinked.

### orgs
- `GET /orgs/{division}/roster` — list a division's members
- `GET /orgs/{division}/{subdivision}/roster` — list a subdivision's members
(Roster endpoints require a key scoped to that division/subdivision.)

### atc
- `GET /v2/atc/online` — list all currently online controllers (mirrors the
  Data API's `controllers[]`, but as an authenticated/versioned Core API
  route with a richer per-session shape including the controller's active
  flight plan reference structure `fp`). Fields: `id`, `callsign`, `start`
  (date-time), `server`, `rating`, plus a nested `fp` object with keys like
  `vatsim_id`, `flight_type`, `callsign`, `aircraft`, `dep`, `arr`, `alt`,
  `altitude`, `route`, `rmks`, `filed`, etc.
- `GET /v2/atc/history` (or similarly named) — historical ATC sessions

## Notes

- The Core API is versioned (`v1` legacy, `v2` current) — prefer `v2` paths
  for anything new.
- Public/anonymized reads (e.g. aggregate rating counts, public roster info)
  may work without a key; anything returning a member's name, email, or
  full session history will require one.
- If you're building a division/vACC website integration (e.g. a roster page,
  ATC activity tracker, or training endorsement dashboard), this is the API
  to use — don't try to scrape the Data API for that, it only has live
  session data, not historical/administrative records.
