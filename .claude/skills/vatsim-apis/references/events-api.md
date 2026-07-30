# Events API

Base URL: `https://my.vatsim.net/api/v2/`
Docs: https://vatsim.dev/api/events-api
Auth: none. Public, GET-only.

Returns info about upcoming/current VATSIM events (group flights, controller
exams, VASOPS events) — who's organizing them, when, which airports/routes
are involved, and description/banner content for display.

## Endpoints

| Method | Path | Returns |
|---|---|---|
| GET | `/events/latest` | All current + upcoming events (most common one to poll) |
| GET | `/events/view/{id}` | A single event by its numeric ID |
| GET | `/events/view/division/{division}` | Events organized by a specific division (e.g. `SEA`) |
| GET | `/events/view/region/{region}` | Events organized by a specific region — same `view/` pattern as division, confirm exact param casing against a live call before shipping |
| GET | `/events/view/subdivision/{subdivision}` | Events organized by a specific subdivision — same `view/` pattern, confirm before shipping |

The `region`/`subdivision` filter routes follow the same `/events/view/<scope>/<value>`
shape as the confirmed `division` route, but verify with a live request if
you're building against them — VATSIM's events routes have shifted before
(there's a legacy, deprecated `v1` at `my.vatsim.net/api/v1/events/all`; use
`v2` for anything new).

## Response shape

**List endpoints** (`/events/latest`, `/events/view/division/{division}`, etc.)
wrap results in a `data` array:
```json
{
  "data": [
    {
      "id": 1,
      "type": "Event",
      "name": "Example Event",
      "link": "https://my.vatsim.net/events/example-event",
      "organisers": [
        { "region": "AMAS", "division": "USA", "subdivision": null, "organised_by_vatsim": false }
      ],
      "airports": [ { "icao": "KJFK" } ],
      "routes": [
        { "departure": "KJFK", "arrival": "KATL", "route": "RBV Q430 BYRDD J48 MOL FLASK OZZZI1" }
      ],
      "start_time": "2026-08-01T00:00:00.000000Z",
      "end_time": "2026-08-01T06:00:00.000000Z",
      "short_description": "Fly with us tonight!",
      "description": "Fly with us tonight! (full text, Markdown)",
      "banner": "https://vatsim-my.nyc3.digitaloceanspaces.com/events/JpjoYKp6CRcz4V1wvdlMnQHiAtYOmT2p3DevEA7j.png"
    }
  ]
}
```

**Single-event endpoint** (`/events/view/{id}`) wraps the same object shape in
a `data` *object* instead of an array:
```json
{ "data": { "id": 1, "type": "Event", "...": "..." } }
```
On failure it returns a `success`/`message` error object instead — check for a
`data` key before assuming success.

### Field notes

- `id` (int) — stable event ID, usable in `/events/view/{id}`.
- `type` (string) — one of `Event`, `Controller Examination`, `VASOPS Event`.
- `name` (string) — event title.
- `link` (string) — public myVATSIM event page URL.
- `organisers[]` — each entry has `region`, `division`, `subdivision`
  (any of which may be `null` depending on scope) and `organised_by_vatsim`
  (bool — true for network-wide official events).
- `airports[]` — `{ icao }` objects, one per airport tagged to the event.
- `routes[]` — `{ departure, arrival, route }`, prescribed routing for
  group-flight style events; can be an empty array.
- `start_time` / `end_time` — ISO 8601 UTC timestamps with microsecond
  precision (`...000000Z`).
- `short_description` / `description` — Markdown strings; `description` is
  the full body, `short_description` a teaser — render both as Markdown, not
  plain text.
- `banner` — full URL to a hosted banner image (DigitalOcean Spaces), or
  absent/empty if the organiser didn't upload one — guard for that in UI code.

## Practical notes for polling

- No documented rate limit, but this endpoint isn't a real-time feed like the
  Data API — events don't change second-to-second, so polling every few
  minutes (or on page load) is more than enough. Don't put this in a tight
  loop.
- The dataset can include far-future events; if you only want "what's on
  soon", filter client-side on `start_time`/`end_time` after fetching rather
  than assuming the API already limits by date range.
- `/events/latest` has had transient outages in the past (VATSIM tech team
  fixed within hours) — build in basic retry/backoff and don't treat a
  failed fetch as "no events."

## Recipes

**Fetch and filter to events at your home airport:**
```php
$events = collect(Http::get('https://my.vatsim.net/api/v2/events/latest')->json('data'));
$engmEvents = $events->filter(
    fn ($e) => collect($e['airports'])->pluck('icao')->contains('ENGM')
);
```

**Fetch a single event by ID for a detail page:**
```php
$event = Http::get("https://my.vatsim.net/api/v2/events/view/{$id}")->json('data');
```

**Fetch everything organized by your division:**
```php
$events = Http::get('https://my.vatsim.net/api/v2/events/view/division/SEA')->json('data');
```
