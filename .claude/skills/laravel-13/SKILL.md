---
name: laravel-13
description: This skill should be used when writing, reviewing, or upgrading PHP/Laravel backend code in a Laravel 13.x application. It should be used for Eloquent models, migrations, query builder code, routing/controllers, and when upgrading a Laravel 12 app to Laravel 13. Laravel 13 released March 17, 2026, after this model's training cutoff, so this skill's references/ files and live web-fetches to laravel.com/docs/13.x/* should be treated as ground truth over training-data memory of Laravel 12 or earlier.
---

# Laravel 13

Laravel 13 (released March 17, 2026) is an intentionally low-breakage major
release — most Laravel 12 apps upgrade with minimal code changes. It requires
**PHP 8.3+** and continues Laravel's shift toward first-party PHP attributes
for declarative configuration, alongside a new AI SDK, JSON:API resources,
and native vector search.

**Do not default to Laravel 12 patterns.** Where this skill's `references/`
files don't cover something, `web_fetch` the live docs at
`https://laravel.com/docs/13.x/<page>` (e.g. `/docs/13.x/eloquent`,
`/docs/13.x/validation`, `/docs/13.x/sanctum`) rather than guessing from
training data. The 13.x docs are the only ground truth for anything
attribute-related, AI SDK–related, or vector-search–related — these are new
enough that pre-2026 training data will be wrong or silently absent.

## Stack context

This is a PHP/Laravel-primary stack: Laravel handles routing, Eloquent
models, controllers, and Blade where server-rendered views are still used.
**React** is used for interactive frontend pieces (mounted into Blade via
Vite), and **SCSS** for styling. When editing frontend code inside a Laravel
app, keep API boundaries clean — Eloquent API resources (including the new
JSON:API resources, see below) are the contract React components consume.
Practically, that means: put data-shaping logic in a Resource class rather
than serializing models ad hoc in a controller, and treat a controller
method's return type as the actual frontend contract, not an implementation
detail — a React component and a Laravel resource should be able to change
independently as long as that contract holds. SCSS changes are orthogonal to
anything in this skill — Vite's asset pipeline doesn't change with the
framework's major version.

## What changed from Laravel 12 to 13

Full detail lives in `references/upgrade-12-to-13.md`. Highlights:

- **PHP 8.3 minimum** (was 8.2 in Laravel 12). Supported range is PHP 8.3–8.5.
- **CSRF middleware renamed**: `VerifyCsrfToken` → `PreventRequestForgery`,
  now with origin-aware verification via the `Sec-Fetch-Site` header. Old
  class names remain as deprecated aliases — update direct references
  (`->withoutMiddleware([...])`, tests) to the new name.
- **`serializable_classes` cache hardening** (medium impact): `config/cache.php`
  now defaults to blocking arbitrary PHP object unserialization from cache
  unless explicitly allow-listed. If you cache PHP objects (not just arrays),
  you must list those classes explicitly or you'll get silent deserialization
  failures.
- **MySQL/MariaDB `upsert` validation**: an empty `uniqueBy` now throws
  `InvalidArgumentException` instead of generating broken SQL.
- Several **low-impact renames**: `JobAttempted::$exceptionOccurred` →
  `$exception` (now the actual exception object or `null`);
  `QueueBusy::$connection` → `$connectionName`; pagination Bootstrap-3 view
  names became explicit (`pagination::bootstrap-3`).
- Laravel Boost (`^2.0`) can drive the upgrade itself — the
  `/upgrade-laravel-v13` slash command automates most of the mechanical work
  if Boost is installed in the app.

For a real upgrade, don't stop at this summary — read
`references/upgrade-12-to-13.md` in full before touching `composer.json`.
It also lists a dozen low/very-low-impact contract additions and renames
(queue events, HTTP client method signatures, pagination view names, a
`symfony/polyfill-php85` global-function collision) that rarely bite but
are cheap to check for in a large codebase.

### New capabilities worth knowing about

- **Expanded PHP attributes across the framework.** Models can now be
  configured with `#[Table]`, `#[Fillable]`, `#[Guarded]`, `#[Unguarded]`,
  `#[Connection]`, `#[DateFormat]`, `#[WithoutTimestamps]`,
  `#[WithoutIncrementing]`, `#[ScopedBy]` instead of (or alongside) the
  traditional `protected $table`, `protected $fillable`, etc. properties.
  Local query scopes now use `#[Scope]` on a `protected` method instead of a
  `scopeXxx()`-prefixed public method. Controllers gained `#[Middleware(...)]`
  and `#[Authorize(...)]` attributes as declarative alternatives to
  route-file `->middleware()` calls and `can` middleware strings. See
  `references/eloquent.md` and `references/routing.md` — **both the
  attribute style and the older property/method style still work**, and
  existing Laravel 12 code using the old style needs no changes.
- **`Queue::route(JobClass::class, connection: ..., queue: ...)`** — central,
  class-based queue/connection routing instead of setting `$queue`/
  `$connection` on every job or calling `->onQueue()` at every dispatch site.
- **`Cache::touch($key, $seconds)`** — extend a cache entry's TTL without
  re-fetching and re-storing its value. Custom cache store implementations
  must add a `touch` method to satisfy the `Store` contract.
- **`PreventRequestForgery`** (see upgrade notes above) — also exposes a
  `preventRequestForgery(...)` method in the middleware configuration API.
- **JSON:API resources** — first-party support for the JSON:API spec
  (resource objects, relationship inclusion, sparse fieldsets, links,
  compliant headers) alongside existing Eloquent API resources. Fetch
  `/docs/13.x/eloquent-resources#jsonapi-resources` for the exact class/method
  names when building one — not covered in `references/` here since usage is
  narrow and spec-driven.
- **Vector search** — `DB::table(...)->whereVectorSimilarTo('embedding', $vector, minSimilarity: 0.4)`
  and related methods (`selectVectorDistance`, `whereVectorDistanceLessThan`,
  `orderByVectorDistance`) for cosine-similarity search, PostgreSQL +
  `pgvector` only. The vector argument can be a raw embedding array *or* a
  plain string — Laravel will generate the embedding via the AI SDK
  automatically. See `references/database.md`.
- **Laravel AI SDK** (`laravel/ai`, stable in 13) — provider-agnostic text
  generation, tool-calling agents, image/audio generation, and embeddings
  (`Str::of($text)->toEmbeddings()`). Fetch `/docs/13.x/ai-sdk` for anything
  beyond the one-liner examples in the release notes — this is an entirely
  new package, not an incremental change, so there's no prior-version
  knowledge to fall back on.

### A quick taste of the attribute style

Since this is the single biggest day-to-day change, here's the shape before
diving into `references/eloquent.md` for the full attribute list. Both of
these are equivalent and both are valid Laravel 13 code — attributes don't
replace the property style, they're an alternative for models where
declaring config at the top of the class reads better than scattering it
across several `protected` properties:

```php
// Attribute style (new option in 13)
#[Table('flights', key: 'flight_id')]
#[Fillable(['name', 'destination'])]
class Flight extends Model {}

// Property style (still fully supported, what most 12.x code already has)
class Flight extends Model
{
    protected $table = 'flights';
    protected $primaryKey = 'flight_id';
    protected $fillable = ['name', 'destination'];
}
```

Don't churn through an existing codebase converting property style to
attribute style — that's a cosmetic diff with no behavioral upside. Use
attributes for *new* models where they read more cleanly, and leave
existing 12.x-style models alone unless the user asks for a conversion.

## Where to look

| Need | File |
|---|---|
| Concrete upgrade diffs (12→13), what breaks / what's deprecated | `references/upgrade-12-to-13.md` |
| Model definitions (attribute vs. property style), relationships, scopes, casts, factories | `references/eloquent.md` |
| Query builder basics, migrations, transactions, vector search | `references/database.md` |
| Route definitions, controller attributes, route model binding, resources | `references/routing.md` |
| `Str`, `Arr`, `collect()`, `Http`, `Validator` quick reference | `references/common-helpers.md` |

If a task touches something not in this table (JSON:API resources, the AI
SDK, Sanctum, broadcasting, queues in depth, testing, etc.), `web_fetch` the
matching `https://laravel.com/docs/13.x/<topic>` page directly rather than
improvising from memory of Laravel 10/11/12 docs — the URL slugs are usually
the same as prior versions (`/docs/13.x/queues`, `/docs/13.x/validation`,
`/docs/13.x/sanctum`, `/docs/13.x/eloquent-relationships`, etc.).

## A note on confidence

Everything in the five `references/` files was pulled directly from the live
`laravel.com/docs/13.x/*` pages (plus the release notes and upgrade guide)
during this skill's creation, not reconstructed from training data. Code
examples in those files are the framework's own examples, not invented ones
— if a snippet looks slightly unusual (e.g. `now()->minus(months: 1)` instead
of the more familiar `now()->subMonth()`), that's very likely a genuine 13.x
API detail worth double-checking rather than a transcription slip, since
Laravel has been known to introduce named-argument-friendly method variants
across major versions.

That said, these five files are a curated subset — Eloquent relationships,
factories, custom cast classes, migrations column types, validation rules,
queues, broadcasting, testing, and the AI SDK/JSON:API resources were
deliberately **not** fully inlined here to keep `SKILL.md` and its
references lean and because their APIs are either unchanged from Laravel 12
(safe to reason about from memory, but verify anything unusual) or entirely
new (no safe memory to fall back on at all — must fetch). When a task needs
one of those, prefer a fresh `web_fetch` over confidence born from general
Laravel familiarity. It costs one tool call and eliminates an entire class
of "this was true in Laravel 11" mistakes.
