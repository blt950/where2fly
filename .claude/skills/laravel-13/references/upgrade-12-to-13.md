# Upgrading From Laravel 12.x to 13.0

Source: https://laravel.com/docs/13.x/upgrade (fetch this URL directly for
anything not summarized below, or if the app being upgraded has dependencies
not mentioned here).

Estimated upgrade time: ~10 minutes for a typical app. Laravel Boost `^2.0`
can automate most of this via the `/upgrade-laravel-v13` slash command if
installed in the target app.

## High impact

### Updating dependencies

In `composer.json`:

- `laravel/framework` → `^13.0`
- `laravel/boost` → `^2.0`
- `laravel/tinker` → `^3.0`
- `phpunit/phpunit` → `^12.0`
- `pestphp/pest` → `^4.0`

If using the global Laravel installer CLI: `composer global update laravel/installer`
(or update Herd if using its bundled installer).

### CSRF middleware renamed: `PreventRequestForgery`

`VerifyCsrfToken` is renamed to `Illuminate\Foundation\Http\Middleware\PreventRequestForgery`,
and now performs origin-aware verification using the `Sec-Fetch-Site` header
in addition to token-based CSRF protection. `VerifyCsrfToken` and
`ValidateCsrfToken` remain as deprecated aliases (so old code doesn't break
outright), but update direct references — especially in tests and
`withoutMiddleware` calls:

```php
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

// Laravel <= 12.x
->withoutMiddleware([VerifyCsrfToken::class]);

// Laravel >= 13.x
->withoutMiddleware([PreventRequestForgery::class]);
```

The middleware configuration API also gained `preventRequestForgery(...)` as
a first-class method.

## Medium impact

### Cache `serializable_classes` config

The default `config/cache.php` now includes `serializable_classes` set to
`false`. This hardens cache unserialization to prevent PHP deserialization
gadget-chain attacks if `APP_KEY` leaks. If the app stores PHP objects
(not just arrays/scalars) in cache, list the allowed classes explicitly:

```php
'serializable_classes' => [
    App\Data\CachedDashboardStats::class,
    App\Support\CachedPricingSnapshot::class,
],
```

Apps that previously relied on unserializing arbitrary cached objects need
to migrate to an explicit allow-list, or switch those cache payloads to
arrays.

### MySQL/MariaDB `upsert` requires non-empty `uniqueBy`

Laravel now validates that `uniqueBy` is non-empty and throws
`InvalidArgumentException` if it isn't, instead of generating invalid SQL.
MySQL/MariaDB drivers still ignore the actual *value* of `uniqueBy` (they
always use the table's primary/unique indexes) — but the argument itself
must not be empty.

## Low impact (know these exist, fix if they bite)

- **Cache/session key prefixes** now use hyphens instead of underscores
  (`app-name-cache-` vs `app_name_cache_`) *only* when falling back to
  framework defaults (no explicit `CACHE_PREFIX`/`REDIS_PREFIX`/
  `SESSION_COOKIE` in `.env`). Set those env vars explicitly to keep old
  values.
- **`Container::call` respects nullable class defaults** — a
  `function (?Carbon $date = null)` param with no bound `Carbon` instance
  now resolves to `null` instead of an auto-resolved `Carbon` instance,
  matching Laravel 12's constructor-injection behavior.
- **Eloquent model instantiation during `boot()` now throws.** Calling
  `new static()` (or similar) from inside a model's `boot()`/trait `boot*()`
  method raises `LogicException`. Move that logic outside the boot cycle.
- **Polymorphic pivot table names now pluralize** when inferred for custom
  pivot model classes. If a model relied on the old singular inference,
  set the table name explicitly on the pivot model.
- **Eloquent collection (de)serialization restores eager-loaded relations**
  (e.g. after a queued job round-trip) — code that assumed relations were
  gone after deserialization needs review.
- **MySQL `DELETE ... JOIN ... ORDER BY ... LIMIT`** now compiles the
  `ORDER BY`/`LIMIT` into the SQL instead of silently dropping them — this
  can turn a previously-succeeding unbounded delete into a `QueryException`
  on engines that don't support the syntax.
- **`JobAttempted::$exceptionOccurred` (bool) → `$exception` (exception or `null`)**.
  Update any listener referencing the old property.
- **`QueueBusy::$connection` → `$connectionName`.**
- **Domain routes now match before non-domain routes**, regardless of
  registration order — review route-matching if the app has both.
- **`Str` factories (UUID/ULID/random) reset between tests** — set them
  per-test/per-setup instead of relying on persistence across test methods.
- **Bootstrap-3 pagination view names are explicit**:
  `pagination::default` → `pagination::bootstrap-3`,
  `pagination::simple-default` → `pagination::simple-bootstrap-3`.
- **`symfony/polyfill-php85` dependency** — on PHP < 8.5 this defines global
  `array_first()` / `array_last()` unless already defined. These can
  conflict with `laravel/helpers` or custom globals of the same name and
  have different signatures (no callback support). Prefer
  `Illuminate\Support\Arr::first($array, fn ($value) => ...)` instead of a
  bare `array_first()` to sidestep the conflict entirely.

## Very low impact (contract additions — only matters for custom implementations)

- `Illuminate\Contracts\Cache\Store` gained `touch($key, $seconds)`.
- `Illuminate\Contracts\Bus\Dispatcher` gained `dispatchAfterResponse($command, $handler = null)`.
- `Illuminate\Contracts\Routing\ResponseFactory` gained an `eventStream` signature.
- `Illuminate\Contracts\Auth\MustVerifyEmail` gained `markEmailAsUnverified()`.
- `Illuminate\Contracts\Queue\Queue` gained `pendingSize`, `delayedSize`,
  `reservedSize`, `creationTimeOfOldestPendingJob`.
- HTTP client `Response::throw`/`throwIf` now declare their `$callback`
  parameter directly in the signature (matters only if overriding these).
- Default password-reset mail subject changed: "Reset Password Notification"
  → "Reset your password". Update tests/translations asserting the old text.
- Queued notifications now respect `#[DeleteWhenMissingModels]` /
  `$deleteWhenMissingModels` reliably.
- Manager `extend(...)` closures are now bound to the manager instance as
  `$this` — capture any previously-relied-upon object via `use (...)` instead.
- `Illuminate\Support\Js::from` uses `JSON_UNESCAPED_UNICODE` by default now.
- `ApplicationBuilder::withScheduling()` registration is now deferred until
  `Schedule` is resolved (matters only if something depended on immediate
  registration during bootstrap).

## Not covered here

Config file diffs, comments, and other non-functional changes are tracked in
the `laravel/laravel` skeleton repo:
https://github.com/laravel/laravel/compare/12.x...13.x
