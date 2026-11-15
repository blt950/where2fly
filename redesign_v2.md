# Responsive redesign, round 2: follow-ups to PR #402

Status: implemented on `feat/responsive-redesign`, one commit per group (A1–A4 and B1–B2; B3–B7 and D1–D3; A5–A6; C2–C6 and E; C1). D2 needed only a larger icon: the back arrow's target was already 44px. A5 and A6 still want the real-device pass.

This is the outcome of reviewing the implementation of `redesign.md` (branch `feat/responsive-redesign`, PR #402). The design landed as specified and works: the map survives navigation, cards are tabs in the drawer on phones, the selection lives in the URL, and the camera respects what covers the map. The changes below are what the review found worth changing. They are ordered by how much they affect a user.

## How it was reviewed

- PHPUnit (257 tests), Vitest (175 tests) and ESLint all pass on the branch.
- The app was driven in headless Chromium at 390×844 (phone), 820×1180 (tablet), 844×390 (phone landscape) and 1400×900 (desktop): search form, results, cards, Top List, Scenery, changelog, feedback, login, 404, deep links, Back, rotation, and a failed search.
- Each item says whether it was **seen** in the browser or comes **from reading** the code. Real-device behaviour (drag feel, on-screen keyboard, safe areas) is still untested, as `redesign.md` already notes.

## Verdict per question

| Question | Verdict |
|---|---|
| UX | Good on portrait phones and tablets. Four drawer-height problems hide the map or the content at the wrong moment (A1–A4). Phone landscape is not usable (A5). |
| Reuse and cleanliness | Good. Navigation, alerts, score icons and requests are each defined once. The remaining duplication is the Blade twin of the shell (C1) and a few small repeats (C2–C5). |
| Bugs and quirks | No crashes or console errors. Six quirks, all with small fixes (section B). |
| Desktop and phone feel alike | Mostly. Same cards, same counts, same Esc and Back behaviour. Three differences are worth closing (section D). |
| PHPUnit changes and production | Safe. Nothing in `phpunit.xml` or `tests/` is loaded in production. Two notes on the guard itself (section E). |

## A. Drawer height (UX)

All of these are in `shell/useDrawerStop.jsx`. Today the stop only changes when an airport is selected, when a `wide` page opens, or when the user drags. It should also follow the page.

**A1. Results arrive with the map hidden.** *Seen.* Focusing a form field raises the drawer to full, which is right. Submitting keeps it full, so the results page shows no map at all, on a design whose point is the map. Fix: when the scene changes (`state.sceneVersion`), settle at half, unless the page is `wide` or a reading page (A3).

**A2. The drawer stays full after leaving Feedback.** *Seen.* Feedback opens the drawer fully. Going from there to Search, Top List or Scenery leaves it full, because `raiseDrawer` only lifts a collapsed drawer. The fix for A1 covers this too, as long as it also runs when the page component changes without a new scene.

**A3. Text pages open at half over a map that has nothing to do with them.** *Seen* on Changelog. The same applies to Privacy, API docs, Account, the lists pages and the error page: a long text or a form in the lower half of the screen. Fix: give these pages a static flag (for example `Page.reads = true`, or reuse `keepsMap`) that opens the drawer fully on the compact layout, as `wide` does. Fold `wide` and this flag into one "preferred stop" per page so `useDrawerStop` has one rule instead of two.

**A4. Tapping the selected marker again does not bring the card back.** *From reading.* Tap the map background and the drawer collapses. Tap the already selected airport: `select()` returns early because the ICAO is unchanged, and the drawer only reacts to a changed selection, so it stays collapsed. Fix: have a marker click always call `raiseDrawer`, selected or not.

**A5. Phone landscape.** *Seen at 844×390.* The header, handle, tab bar and bottom nav take about 250 of 390 pixels. The map is a 140px strip and the card shows one line. Proposed: on the compact layout when the viewport is wider than tall and under about 500px high, dock the drawer as a side sheet on the left (roughly 45% wide, full height, no vertical drag) and move the bottom nav into a slim rail or the header. `getInsets()` then reports `left` instead of `bottom`, which the camera code already handles. If that is too much for this round, the cheap version is to hide the header in landscape and default to the collapsed stop.

**A6. The drawer only drags by its handle.** *From reading.* Most sheets also follow a downward drag on the content when it is scrolled to the top, and that is what people will try. Worth doing once A1–A4 are in, and only after the real-device pass, since it has to coexist with content scrolling. Keep it in `Drawer.jsx`: it still needs no knowledge of the content, only `scrollTop === 0` on `.drawer-body`.

## B. Bugs and quirks

**B1. Closing a card with the arrow leaves a dead Back step.** *Seen.* Opening a card pushes a history entry. Closing it with the arrow (or the desktop ×, or Esc) replaces that entry with the URL without `?airport=`, so the next Back press goes to an identical page and appears to do nothing. Fix in `useSelection`: remember that the card was opened by a push in this session, and close with `history.back()` in that case. Keep `replace` for a card opened by a deep link, where there is nothing to go back to.

**B2. A cancelled touch counts as a tap on the handle.** *From reading.* `onPointerCancel` shares `onPointerEnd`, which treats "not moved" as a tap and changes the stop. A system gesture or an incoming call that cancels the pointer will move the drawer. Fix: on cancel, restore `offsets[stop]` and return.

**B3. An unknown `?airport=` is dropped without a word.** *Seen* with `&airport=ZZZZ` on a results page. The parameter is removed and nothing is shown. The shell already records the miss (`airportNotFound`); only the Scenery page reads it. Fix: show it through the shared alerts, once, on every page.

**B4. The error page can fail while rendering an error.** *From reading, not reproduced.* For a 500, `bootstrap/app.php` renders `Error` with `withSharedData()`. The shared props run `NavigationHelper`, `Auth::user()` and the `listsRevision` query. If the 500 was a database outage, those throw again and the visitor gets Laravel's bare fallback. Fix: wrap the render in a `try`/`catch` that returns `null` (the Blade error view), and add a feature test that makes a shared prop throw.

**B5. Maintenance mode and proxy errors are reported to Sentry on every visit.** *From reading.* `inertia.jsx` captures an exception for each non-Inertia response before reloading. During a deploy with `artisan down` that is one event per navigation per visitor. Fix: skip the report for 503, or report it once per page load.

**B6. Attribution is covered at the half and full stops.** *Seen.* It sits on the collapsed drawer and is hidden as soon as the drawer opens, which is the default state. `redesign.md` asks for it to stay visible. Proposed: an "ⓘ" button under the layers control on the compact layout that opens the attribution, the pattern MapLibre itself uses for compact maps. It stays reachable at every stop except full, where the map is not visible either.

**B7. `listsRevision` can miss a change.** *From reading, low priority.* It is `COUNT(*)` plus `MAX(updated_at)`, to the second. Deleting one list and creating another within the same second as the newest existing timestamp leaves it unchanged. Cheap fix if it ever matters: include `SUM(id)` or `MAX(id)`.

## C. Reuse and cleanliness

**C1. Finish moving the Blade pages and delete the twin shell.** This is the largest clean-up available. Login, register, password reset and email verification are four small forms. While they stay Blade, the site carries two of everything: `layouts/menu`, `layouts/bottomnav`, `layouts/footer`, `layouts/alerts` and `parts/map` beside their React versions, the view composers in `AppServiceProvider`, `Map.jsx` and the dynamic mount in `app.js`, `functions/tooltip.js`, `functions/formSubmit.js`, and the `body.has-bottom-nav` branch of `shell.scss`. Moving the four auth pages into the shell (Turnstile has a plain script API that works from a React effect) leaves only admin and scenery create/edit, which can use a simple map-less Blade layout. Then all of the above can go. Also update decision 4 in `redesign.md`, which still says the Blade pages keep the hamburger header on phones; they have the bottom nav now.

**C2. One source for "is the layout compact".** `useCompactLayout` decides it, `AppShell` copies it to a body class, and `useDrawerStop` and `useMapInsets` read the class back from the DOM. Keep the body class for CSS only, and pass `compact` through a ref (or `MapLayoutContext`) to the two hooks.

**C3. Layout numbers duplicated between JS and SCSS.** `FOOTER_HEIGHT = 30` in `useMapInsets.jsx` and the `.popup-container` lookup there mirror CSS. Measure the footer element the same way the card stack is measured, or let `CardHost` report its width as `Drawer` reports its height (`onVisibleChange`), so `useMapInsets` stops querying the DOM by class name.

**C4. One metrics helper.** `window.umami?.track('Interactions', …)` is defined separately in `CardHost.jsx` and `SearchResults.jsx`, and written out by hand in `AirportCard.jsx`, `TAF.jsx` and `SearchForm.jsx`. Put `trackInteraction(name)` in `utils/` and use it everywhere.

**C5. One airport presenter on the server.** `TopController` builds the same `icao / name / isoCountry / countryName / scores` shape inline that `SearchController` builds through `airportSummary()` and `scoreIconProps()`. Move those two into a small presenter next to `App\View\ScoreIcon` and use it from both controllers.

**C6. Small ones.**

- `ContentController` has three identical actions. One action with a constrained route parameter (`/{page}` where `page` is `changelog|privacy|api`) removes two of them. Keep the route names.
- `MapController::getFlights` runs a separate `Airline` query for the selected airline, which is already in the `airlines` list it returns. Take it from there.
- `inertia.jsx` resolves a page with `pages[...]()`, which throws a `TypeError` for a component name with no file. Throw a clear error instead, so a typo in a controller is obvious.

## D. Desktop and phone alike

**D1. Empty Flights and Scenery.** *Seen.* With zero results, desktop shows "Flights (0)" and "Scenery (0)" as normal buttons that open an empty card, and the phone shows normal tabs with a 0 pill. Proposed for both: dim the button or tab when the count is 0. Keep Scenery clickable, because its card holds "Add missing scenery"; make Flights at 0 inert.

**D2. Closing.** Desktop closes each card with ×, the phone uses a back arrow in the handle row. That difference is fine, but the arrow is small and sits right beside the drag handle. Give it a 44px target and a little more distance from the grip. After B1, both layouts also agree on what Back does.

**D3. Airline shortcuts.** Desktop has the airline logos on the airport card as shortcuts into the flights filter. On the phone they are left out because the tab exists, so picking an airline takes two steps there. Proposed: keep the logos on the phone's airport tab too. They switch to the Flights tab with the filter set, which `openFlights(airline)` already does.

## E. PHPUnit changes and production

The changes are `phpunit.xml` (forced `APP_ENV` and `DB_DATABASE`, cache paths pointed at files that are never built) and `tests/CreatesApplication.php` (refuses any database other than `where2fly_test`). Neither file is read outside a test run, and `tests/` is `autoload-dev`, so production is unaffected. The feature tests were converted from `assertViewHas` to `assertInertia` one for one; none were dropped.

Checked while reviewing:

- The suite passes with a cached dev config present in `bootstrap/cache`, which is the case the guard exists for.
- CI uses the database name `where2fly_test`, so the hard-coded name in the guard matches.
- Route caching still works with the new closure fallback route: the cached routes file built by `php artisan optimize` on this branch contains it.

Two notes, neither blocking:

- **`force="true"` does not override a real process environment variable.** PHPUnit writes forced values to `$_ENV` and `putenv`, and Laravel reads `$_SERVER` first. If a container ever sets `DB_DATABASE` or `APP_ENV` as a real environment variable (for example in a compose file), that value wins. The guard in `CreatesApplication` then stops the run, so nothing is wiped, but the message will be confusing. Adding matching `<server name="…" force="true"/>` lines closes it.
- **Tests never run against cached config or routes any more**, by design. Production always does (`entrypoint.sh` runs `php artisan optimize`). The image smoke test in CI is what covers that path, so keep it.

## Suggested order

1. A1–A4 and B1–B2: one small change set in `useDrawerStop`, `useSelection` and `Drawer`, with Vitest cases beside the existing drawer and shell tests.
2. B3–B6 and D1–D3.
3. The real-device pass from `redesign.md`, then A6 and A5 with what it shows.
4. C1 as its own PR; C2–C6 can ride along with whatever touches those files.
