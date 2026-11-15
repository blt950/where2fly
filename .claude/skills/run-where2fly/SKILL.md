---
name: run-where2fly
description: Build, run, and drive Where2Fly (Laravel + React flight-destination finder). Use when asked to start the app, take a screenshot of it, run its PHP test suite, or interact with the running search/map UI.
---

Where2Fly is a Laravel app. The map pages are Inertia + React pages in a
persistent shell with a MapLibre map, as are login, register, account and the info pages;
only admin and scenery create/edit are Blade (a simple map-less layout). Drive it by starting `php artisan serve`
and scripting a headless Chromium session via
`.claude/skills/run-where2fly/driver.mjs` (a small chromium-cli-alike
built for this skill — `chromium-cli` itself isn't installed in this
container). All paths below are relative to the repo root (`/app`).

## Prerequisites

PHP 8.3, MySQL client, Node 20+, and Composer/npm deps are already
installed in this container. The driver needs Playwright + a headless
Chromium, installed once inside the skill directory:

```bash
cd .claude/skills/run-where2fly
npm install                              # installs playwright (see package.json)
npx playwright install --with-deps chromium   # ~300MB download, one-time
```

## Setup

The repo already has a working `.env` (DB creds, `APP_KEY`, API keys)
and a MySQL server reachable at `host.docker.internal` with the
`where2fly` and `where2fly_test` databases already migrated. If
starting fresh, or if `migrate:status` shows pending migrations:

```bash
php artisan migrate
```

Frontend assets are already built (`public/build/` exists). If you
change anything under `resources/`, rebuild:

```bash
npm install   # repo root, not the skill dir
npm run build
```

## Run (agent path)

Start the Laravel dev server, then drive it with the skill's Playwright
script.

```bash
cd /app
nohup php artisan serve --host=0.0.0.0 --port=8000 > /tmp/artisan-serve.log 2>&1 &
timeout 20 bash -c 'until curl -sf http://127.0.0.1:8000/ >/dev/null; do sleep 1; done'
```

```bash
cd /app/.claude/skills/run-where2fly
node driver.mjs <<'EOF'
nav http://127.0.0.1:8000/
wait-for text=SEARCH 5000
screenshot home
click button.submitBtn:visible
sleep 3000
screenshot search-results
console --errors
EOF
```

Screenshots land in `.claude/skills/run-where2fly/screenshots/` (also
copied to `screenshot.png` as a "latest" pointer). Stop the server with
`pkill -f "artisan serve"`.

Driver commands (`driver.mjs`, read from stdin or a script file):

| command | what it does |
|---|---|
| `nav <url>` | navigate |
| `wait-for text=<text>` or `wait-for <selector>` `[timeoutMs]` | wait until visible |
| `click <selector>` | click (selectors support `:visible`, `text=`) |
| `fill <selector> <text...>` | fill an input (goes through React's input pipeline) |
| `press <key>` | keyboard press |
| `screenshot [name]` | full-page screenshot |
| `screenshot-element <selector> [name]` | crop to one element |
| `console --errors` | print captured console/page errors |
| `eval <js>` | `page.evaluate` and print the result |
| `sleep <ms>` | pause |
| `quit` / `exit` | close browser |

The homepage's search form has **three tabs** (Find Arrival / Find
Departure / Find Flights), each with its own hidden `button.submitBtn`
— plain `click text=SEARCH` matches Playwright's first (possibly
hidden) match and silently does nothing. Use `button.submitBtn:visible`
to hit the active tab's button.

## Run (human path)

`php artisan serve` then open `http://localhost:8000` in a browser.
Ctrl-C to stop. (`docker-compose.dev.yml` exists for a containerized
setup, but this container already has PHP/MySQL/Node natively, so
`artisan serve` is simpler and is what was verified.)

## Test

```bash
php artisan test
```

~120 tests / ~265 assertions, ~10s, all passing against the
`where2fly_test` database defined in `phpunit.xml`. If the suite
suddenly takes ~15 minutes instead, the seeder is broken (every failing
test re-runs `migrate:fresh`) — fix `TestAirportSeeder` first, don't
debug individual tests.

## Gotchas

- **Logging in headlessly.** Login and register carry a Cloudflare Turnstile widget. With the
  dev `.env`'s testing site key it passes in headless Chromium ("Success!"), so the real form
  works. Without it, write a session for a user instead (`login_web_<sha1 of SessionGuard>` in
  the session store, encrypted cookie via `CookieValuePrefix`), set from a page that starts no
  session (`/robots.txt`). For pages behind `auth`, prefer a scratch server on the test database
  (`APP_CONFIG_CACHE` pointed at a missing file, `DB_DATABASE=where2fly_test`) with a throwaway
  user, so the dev database stays untouched.

- **`chromium-cli` isn't installed in this container** — this skill
  ships its own minimal Playwright REPL (`driver.mjs`) instead. If a
  future environment does have `chromium-cli`, prefer that.
- **Hidden duplicate buttons.** See the `button.submitBtn:visible` note
  above — the search form renders one submit button per tab, only one
  visible at a time.
- **Umami analytics 400s are expected and harmless.** `console
  --errors` will show repeated `400` responses from
  `https://metrics.blt950.com/api/send` — the dev `.env`'s
  `UMAMI_WEBSITE_ID_DEV` is a placeholder, not a real site ID. Ignore
  these; a real API/search error would show up as a different error or
  a visibly broken results page.
- **Auth state comes from a meta tag, not an API call.** The map reads
  `<meta name="user-authenticated">` synchronously; there is no
  per-page-load `GET /api/user/authenticated` any more. If you see one
  in `performance.getEntriesByType('resource')`, the build is stale.
- **Stopping the server: never `pkill -f "artisan serve"` inside a
  compound command.** `pkill -f` matches the *full command line* of
  every process — including the `bash -c` running the very command that
  contains that string — so it kills your own shell mid-command (exit
  code 144, later steps in the same command silently skipped). Use the
  bracket trick `pkill -f "[a]rtisan serve"`, or run the pkill as its
  own standalone command with nothing after it.
- **Direct results URL beats driving the search form.** The web search
  is a plain GET; to screenshot/verify the results page, navigate
  straight to
  `/search?icao=EGLL&direction=departure&codeletter=JM&airtimeMin=0&airtimeMax=8&metcondition=ANY&destinationWithRoutesOnly=0&destinationRunwayLights=0&destinationAirbases=0&flightDirection=0&temperatureMin=-60&temperatureMax=60&elevationMin=-2000&elevationMax=18000&rwyLengthMin=0&rwyLengthMax=17000`
  (all listed params are required by validation) instead of filling the
  multi-tab form. To open an airport card directly, add `&airport=KORD`
  (or `?airport=KORD`) to a map page's URL; the selection lives in the
  URL, and there are no `window.*` map setters any more.
- **Map tiles need a moment.** Right after `nav`, the map canvas
  renders solid black until tiles load — `sleep 2000-3000` (or
  `wait-for` a marker/selector you expect) before screenshotting if you
  need the map itself, not just the form.
- **The driver's viewport is fixed at 1400×900.** The phone/tablet
  layout (below 992px: drawer, bottom nav) needs another Playwright
  context, e.g. `browser.newContext({ viewport: { width: 390, height: 844 },
  isMobile: true, hasTouch: true })`, using the playwright installed in
  this skill directory.
- **Full-page screenshots dim the map** in headless Chromium; use
  `screenshot-element #map` to judge the map itself.
- **Stale PHP after code changes.** opcache runs with
  `validate_timestamps=Off`, in Apache (which already serves the app on
  port 80) and in the CLI, which also keeps a file cache under
  `/tmp/<system-id>*`. After changing PHP: `php artisan optimize`, then
  `chown -R www-data:www-data storage bootstrap/cache` (CLI runs as root),
  then `apachectl -k graceful`. `php artisan serve` doesn't pass
  `PHP_INI_SCAN_DIR` on to its `php -S` child, so to get fresh code from
  a scratch server, run `php -S 0.0.0.0:8000
  vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php`
  from `public/`, with an ini setting `opcache.enable=0` and
  `opcache.enable_cli=0`.
