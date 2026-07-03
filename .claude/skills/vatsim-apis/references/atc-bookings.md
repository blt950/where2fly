# ATC Bookings API

Base URL: `https://atc-bookings.vatsim.net/api/`
Docs (interactive): https://atc-bookings.vatsim.net/api-doc
Auth: none for reads; Bearer token for writes

This is a **separate service** from the vatsim.dev-documented APIs — it backs
https://atc-bookings.vatsim.net, the calendar controllers use to book ATC
positions in advance. It is *advisory only*: a booking is not an ATC-position
reservation guarantee, just a published schedule.

## Read (public, no auth)

### `GET /booking`
Returns all current bookings.

```json
[
  {
    "id": 1,
    "callsign": "LON_CTR",
    "cid": 1240411,
    "type": "booking",
    "start": "2022-02-12 12:00:00",
    "end": "2022-02-12 14:00:00",
    "division": "EUD",
    "subdivision": "AMS"
  }
]
```

### `GET /booking/{id}`
Returns a single booking by ID, same object shape as above.

## Write (requires Bearer token)

An API key/Bearer token is only issued to divisions, subdivisions, ARTCCs, or
vACCs that provide active ATC service — request one from the VATSIM Tech Team
via https://support.vatsim.net (must be submitted by staff responsible for
that facility's web presence). Send it as a standard `Authorization: Bearer
<token>` header.

### `POST /booking`
Create a booking.

**Payload:**
```json
{
  "callsign": "LON_CTR",
  "cid": 1240411,
  "type": "booking",
  "start": "2022-02-12 12:00:00",
  "end": "2022-02-12 14:00:00",
  "division": "EUD",
  "subdivision": "AMS"
}
```
**Responses:** `201` (created, returns the booking object incl. `id`),
`401` (unauthorized), `422` (validation errors).

### `PUT /booking/{id}`
Update an existing booking. Same payload shape as `POST`.
**Responses:** `200` (updated, returns booking object), `401`, `404` (not
found), `422` (validation errors).

### `DELETE /booking/{id}`
Delete a booking.
**Responses:** `200` on success, `401` if unauthorized.

## Field notes

- `type` — typically `"booking"`; other values may exist for training/exam
  sessions depending on the facility's setup, confirm against a live sample
  if building against a specific division's data.
- `division` / `subdivision` — VATSIM division/subdivision short codes (e.g.
  `EUD`/`AMS` for VATSIM Europe / Amsterdam FIR).
- Timestamps are plain `YYYY-MM-DD HH:MM:SS` strings (not ISO 8601 with a `Z`
  suffix like the other VATSIM APIs) — treat as UTC unless a facility
  documents otherwise, and don't assume they'll parse with the same datetime
  format string you use for Data/Events/Core API timestamps.
