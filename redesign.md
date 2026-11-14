# Responsive redesign: map-first on mobile

Status: concept agreed, not started. A clickable reference lives at `public/responsive.html` (mock data, real MapLibre). It is a simplified sketch of the behaviour, not a visual spec — see "What the prototype leaves out". Delete it before this ships; anything in `public/` is served.

## Why

Below the `md` breakpoint the map is hidden and the sidebar is the whole site. Scenery is unusable on a phone (it shows a "desktop or tablet only" notice) and search results come without a map. Desktop works and should stay recognisably the same.

## The concept

**One layout, two presentations of the same parts.**

| Part | Desktop / tablet | Phone |
|---|---|---|
| Map | Beside the sidebar, as today | Fills the screen under everything else |
| Panel (form, results, lists) | Fixed sidebar | Draggable drawer over the map, with a few resting heights |
| Main navigation | Header links | Bottom tab bar; secondary links collapse into one "more" entry |
| Airport, flights, scenery cards | Stacked under each other over the map, scrolling together | Tabs inside the drawer, replacing the panel content while an airport is selected |
| Map controls, attribution | As today | Stay reachable above the drawer; attribution must remain visible somewhere |

Rules that make it feel right:

- **The map never reloads** while moving between Search, results, Top List and Scenery. Only the panel content and the map's data change.
- **Opening a card never changes page.** Scenery for an airport is a card (or tab) wherever you are; the Scenery *page* is only for browsing.
- **The camera aims at the uncovered part of the map.** Fitting results or focusing an airport accounts for whatever the drawer or card stack covers. Dragging the drawer does not move the camera.
- **Tapping the map gets the panel out of the way**; selecting something brings it back.
- **Touch has no hover.** Anything that today lives in a tooltip (score reasons, flag country, airline name) needs a visible or tappable equivalent on phones.

## Where the code is today

Facts that shape the plan:

- React mounts only into `#map` (`Map.jsx`, dynamically imported from `app.js`). The sidebar (`<main>` in `layouts/app.blade.php`) is Blade, outside React.
- Every navigation is a full page load, so the map is rebuilt each time. `.map-reveal` exists to soften that.
- Pages push data into the map through `window.*` setters after a `mapReady` event (`setAirportsData`, `setPrimaryAirport`, `setReverseDirection`, `setCluster`, `setDrawRoute`, `setFocusAirport`, `setHighlightedAircrafts`), from inline `<script>` blocks in each Blade view.
- The map decides behaviour from the URL: `route().current()` / `isDefaultView()` in `Map.jsx`, `mapRoutes.js` and `airportLayerSpec.js` choose the initial view, clustering colours, whether to fit bounds, whether to save the view, and whether user lists load.
- `AirportCard` owns `FlightsCard` and `SceneryCard` as children with local state; the scenery page opens the scenery card through `window.setShowSceneryIdCard`. The flights card is per airline, opened from the airline logos.
- The mobile CSS in `app.scss` deliberately lets the page scroll so the browser chrome can hide, and `.map`, `.popup-container`, `.map-controls` are `display: none` below `md`. `MapProvider` waits for a non-zero container size before constructing the map.
- Search results duplicate airport details in expandable table rows on mobile (`data-detail-row`), because the card is not available there.
- Two layouts exist: `layouts.app` (sidebar + map) and `layouts.appStatic` (feedback: sidebar + wide content, with `mobile-focus` / `mobile-nofocus` classes).

## Target architecture

### 1. A React shell that outlives page changes

The persistent map requires that the thing owning the map is not thrown away on navigation. That means the map pages become views inside one long-lived React tree:

```
AppShell (persistent)
├─ Header / BottomNav
├─ MapProvider + map layers          ← never unmounts
├─ Panel (sidebar | drawer)
│   └─ current page: SearchForm | SearchResults | RouteResults | TopList | SceneryBrowser
└─ CardHost (stacked | tabbed)
    └─ AirportCard, FlightsCard, SceneryCard
```

**Recommended way to get there: Inertia.** Controllers keep routing, validation and queries and return page props instead of Blade views; a persistent layout keeps the shell (and so the map) mounted between visits. Redirect-with-errors and old input, which the search flow relies on, carry over. Ziggy's `route()` is already in place. Current releases are `inertiajs/inertia-laravel` 3.x and `@inertiajs/react` 3.x (peer-depends on React 19) — both newer than most documentation you will find, so read the current docs and confirm the Laravel 13 constraint at install time.

Alternatives, and why they are second choice:

- **Hand-rolled shell** (own fetch + History API, JSON endpoints per page): no new dependency, but you rebuild visit handling, scroll restoration, error/redirect handling and form state yourself.
- **Turbo with a permanent map element**: keeps the Blade views, but the panel stays outside React, inline page scripts must re-run on every swap, and the drawer/cards end up split across two rendering models. It solves the reload without getting you to a React setup.

Scope: only the map pages move (`front`, `front.departures`, `front.routes`, `search`, `search.routes`, `top*`, `scenery`, `scenery.filtered`). Account, lists, scenery create/edit, feedback, changelog, privacy and API docs can stay Blade; links to them are ordinary page loads. Decide per page later whether it is worth moving.

Cost to be honest about: the first paint of map pages becomes client-rendered unless Inertia SSR is added (a Node process in production). Meta description and Open Graph tags stay server-rendered in the root template, so link previews are unaffected.

### 2. Map state driven by the page, not the URL or globals

Replace both the `window.*` setters and the `route().current()` checks with one explicit description of what the map should show, supplied by the current page:

- airports to draw and how (results vs. muted, clustered or not)
- primary airport and direction, route to draw, highlighted aircraft
- camera intent: restore saved view / fit these airports / a fixed view (Top List continents)
- whether to persist the view, whether user lists are shown

`Map.jsx` already holds most of this as state; the change is where it comes from (a context fed by page props) and that it must now handle *transitions* between pages, not just an initial value. Things that were implicitly reset by a reload need explicit resets: selected airport, drawn route, bounds, clustering, the airport-data cache in `AirportCard`.

Keep the existing conventions from `CLAUDE.md`: one component per source/layer via `useMapLayer`, children keyed on `styleEpoch`, `storage.js` instead of `localStorage`, no static React/MapLibre imports in `app.js`.

### 3. Viewport insets for the camera

Introduce one value — how much of the map is covered on each edge — owned by the shell (drawer height on phones, card-stack width on desktop) and read by everything that moves the camera: `MapBound` (fit padding), `MapPan`, `MapRoute`, `MapPing`. Read it at the moment the camera moves; do not animate the camera in response to drawer drags. Remember the reduced-motion gotcha: `flyTo` becomes a synchronous jump, so register listeners first.

### 4. The drawer

A layout primitive with no knowledge of its content.

- Resting heights are derived from the available height, not hard-coded; re-derive on resize and orientation change.
- Move it with a transform driven by pointer events, with the transition off while dragging. A slow drag settles on the nearest stop, a flick goes one stop in its direction.
- The content area scrolls on its own and is sized to the visible part, so the last row is always reachable.
- Keyboard and screen-reader path: the handle is a real button that steps through the heights; focus moving into a text field raises the drawer so the on-screen keyboard does not cover it.
- Respect `prefers-reduced-motion`, safe-area insets and `dvh`.
- This replaces the current "let the page scroll" mobile CSS: the phone layout becomes a fixed, non-scrolling shell. Pages without a map (account, feedback, lists) should not get a drawer at all on phones — plain full-width content with the bottom nav.

Write the gesture logic as a plain module or hook around a DOM ref, so it does not depend on how the panel content is rendered.

### 5. Cards

Lift card state out of `AirportCard` into a host that knows which airport is selected and which cards apply in the current context (flights only when there is a route; scenery always; scenery first on the Scenery page). The card bodies are shared; only the wrapper differs:

- desktop: a scrolling stack, each card independently closable and reopenable
- phone: a tab strip in the drawer (`@u-elements/u-tabs` is already a dependency and already used in `SceneryCard`)

Selecting an airport should be part of navigable state (query string or history entry) so the back button closes the card and a link can open one. This also removes the need for `window.setShowSceneryIdCard` and the `airportReady` / `mapFocusAirport` events.

Open design question: flights are per airline today (logo buttons → one card). The prototype folded this into one flights view with an airline filter. Pick one before building the tabbed version, because a tab needs a single, stable destination.

### 6. Panel pages

Port in order of difficulty, each behind the same shell:

1. **Top List** — list, three filters, map data. Smallest; proves the shell, props-to-map flow and camera rules.
2. **Scenery browser** — search box, simulator filter, map of airports, cards.
3. **Route results** and **search results** — tables with sorting, "show more", TAF toggle, legend, warnings, randomise, edit filters. With the card available on phones, the duplicated mobile detail rows can go.
4. **Search form** — the big one: `u-combobox` multi-selects, noUiSlider ranges, three-state filters, whitelists, validation errors with old input, the bookmark/edit round trip (`search.edit`), and the umami metrics in `searchForm.js`. Keep field names and the query-string contract of `/search` exactly as they are so bookmarked searches keep working.

## Suggested order of work

1. **Decide** the open questions below.
2. **Shell without visual change.** Install Inertia, build the persistent layout around the existing desktop look, move Top List. The map survives navigation between migrated pages; unmigrated pages still reload. Introduce the page-driven map state here and keep the `window.*` setters only as a temporary adapter for Blade pages.
3. **Migrate the remaining map pages** (order above). Delete the adapter, the inline page scripts, `.map-reveal`, and the route checks in the map code as the last consumer goes.
4. **Card host** with the stacked wrapper (desktop behaviour stays the same, state now lifted).
5. **Phone layout**: drawer, bottom navigation, tabbed card wrapper, viewport insets, un-hiding the map and controls below `md`, touch equivalents for tooltips.
6. **Clean-up**: remove dead mobile CSS and detail rows, update `privacy.blade.php` only if a tile source changes, remove the prototype.

Steps 4–5 can be pulled ahead of 3 if getting the map onto phones matters more than removing reloads everywhere; the cost is building the phone layout while some pages are still Blade.

## What the prototype leaves out

Do not treat it as complete. Missing or faked there, real in the app:

- the full search form (multi-selects, all sliders, whitelists, validation, flight direction, destination parameters)
- real airport markers, clustering, user scenery lists, terrain / precipitation / day-night layers, light theme, saved map view
- sortable tables, "show more", legend, forecast uncertainty badges, flags, airline logos, tooltips
- session alerts, email-verification notice, hints over the map, cookie consent, logged-in navigation and notifications
- loading and error states for cards and the map (WebGL2 fallback), slow networks
- tablet widths: between `md` and `lg` the card stack covers most of the map today — decide whether the drawer layout should extend up to `lg`

## Open questions

1. Inertia (recommended) or hand-rolled shell? Is running Inertia SSR in production acceptable, or is client-rendered first paint fine?
2. Flights: per-airline cards as today, or one flights view with an airline filter?
3. Which breakpoint switches to the drawer — `md` as now, or `lg`?
4. Should non-map pages move into the shell eventually, or stay Blade for good?
5. Does the Top List need its own camera rule on phones (continent views were tuned for a wide map)?

## Testing

- Existing Vitest suites (`resources/js/__tests__`) cover map config, layers, cards and storage; extend them for the page-driven map state (transitions between pages, resets) and the card host.
- Unit-test the drawer's stop selection (nearest stop, flick direction, resize) separately from the DOM.
- PHPUnit feature tests for the migrated controllers: same routes, same validation and redirects, props instead of views.
- Manual pass on real devices — iOS Safari and Android Chrome at minimum — for drag feel, the on-screen keyboard, safe areas and browser-chrome resizing. Headless checks do not cover these.
