# Common Helpers & Facades Quick Reference (Laravel 13.x)

These APIs are stable across recent Laravel major versions — nothing here
is 13.x-specific except where noted (`Str::uuid7()`, `Str::of()->toEmbeddings()`).
Fetch `/docs/13.x/strings`, `/docs/13.x/helpers`, `/docs/13.x/collections`,
`/docs/13.x/http-client`, or `/docs/13.x/validation` directly for anything
not listed.

## `Str` (`Illuminate\Support\Str`)

| Method | Purpose |
|---|---|
| `Str::slug($title)` | URL-friendly slug |
| `Str::limit($str, 20)` | Truncate to length |
| `Str::random(32)` | Random string |
| `Str::uuid()` | UUIDv4 |
| `Str::uuid7()` | UUIDv7 (time-ordered, sortable — used by `HasUuids` by default) |
| `Str::ulid()` | ULID (compact, time-ordered) |
| `Str::plural($word)` / `Str::singular($word)` | Pluralize / singularize |
| `Str::camel($str)` / `Str::snake($str)` / `Str::kebab($str)` / `Str::studly($str)` | Case conversion |
| `Str::headline($str)` / `Str::title($str)` / `Str::apa($str)` | Title-casing variants |
| `Str::contains($haystack, $needle)` / `startsWith()` / `endsWith()` | Substring checks |
| `Str::replace($search, $replace, $subject)` | Replace |
| `Str::upper()` / `Str::lower()` | Case |
| `Str::password(32)` | Secure random password |
| `Str::isJson()` / `isUrl()` / `isUuid()` / `isUlid()` | Validity checks |
| `Str::after($subject, $search)` / `before()` / `between()` | Substring extraction |
| `Str::of($string)` | Fluent `Stringable` instance — chain `->trim()->slug()->limit(20)` etc. |

**New in 13 — AI SDK integration:**
`Str::of($text)->toEmbeddings()` generates vector embeddings directly from
a string via the Laravel AI SDK — pairs with `whereVectorSimilarTo` in
`references/database.md`. Fetch `/docs/13.x/ai-sdk#embeddings` for
provider/config details before using this in application code.

## `Arr` (`Illuminate\Support\Arr`)

Prefer `Arr::` methods over legacy global `array_*()` helpers, especially
`Arr::first()`/`Arr::last()` — Laravel 13 ships a PHP 8.5 polyfill
(`symfony/polyfill-php85`) that can define global `array_first()`/
`array_last()` with different (callback-less) semantics than
`laravel/helpers`' historical versions. See
`references/upgrade-12-to-13.md`.

```php
use Illuminate\Support\Arr;

Arr::get($array, 'products.desk.price', 0);       // dot-notation get w/ default
Arr::set($array, 'products.desk.price', 200);     // dot-notation set
Arr::has($array, 'products.desk');
Arr::only($array, ['name', 'email']);
Arr::except($array, ['password']);
Arr::first($array, fn ($value) => $value > 100);  // callback supported
Arr::last($array);
Arr::flatten($array);
Arr::collapse($arrayOfArrays);
Arr::pluck($array, 'name');
```

## `collect()` / `Illuminate\Support\Collection`

```php
$collection = collect([1, 2, 3, 4]);

$collection->map(fn ($n) => $n * 2);
$collection->filter(fn ($n) => $n > 2);
$collection->reject(fn ($n) => $n > 2);   // inverse of filter
$collection->reduce(fn ($carry, $n) => $carry + $n, 0);
$collection->groupBy('type');
$collection->pluck('name');
$collection->sortBy('name');
$collection->chunk(200);
$collection->each(fn ($item) => /* ... */ null);
```

Eloquent's `Collection` (returned by `->get()`, `::all()`) extends this
base class and adds model-specific helpers (`modelKeys()`, `load()`, etc.)
— see `references/eloquent.md`.

## `Http` (`Illuminate\Support\Facades\Http`)

```php
use Illuminate\Support\Facades\Http;

$response = Http::get('https://api.example.com/users');
$response = Http::post('https://api.example.com/users', ['name' => 'Steve']);

$response = Http::withToken($token)
    ->withHeaders(['X-Custom' => 'value'])
    ->timeout(10)
    ->retry(3, 100)
    ->post(/* ... */);

$response->json();
$response->status();
$response->successful();
$response->throw();   // throws on 4xx/5xx — signature is explicit in 13.x
```

## `Validator` / form validation

```php
use Illuminate\Support\Facades\Validator;

$validator = Validator::make($request->all(), [
    'title' => 'required|max:255',
    'body' => 'required',
    'email' => 'required|email|unique:users,email',
]);

if ($validator->fails()) {
    return redirect('/create')
        ->withErrors($validator)
        ->withInput();
}
```

Controller-level convenience (throws `ValidationException` automatically,
302-redirects with errors on failure in a web context):

```php
$validated = $request->validate([
    'title' => 'required|max:255',
    'body' => 'required',
]);
```

For Form Request classes (`php artisan make:request StoreFlightRequest`)
and custom validation rule objects, fetch `/docs/13.x/validation` directly
— the class shape (`authorize()`, `rules()`) is unchanged from Laravel 12.

## React + SCSS stack notes

- Laravel API resources (`php artisan make:resource`) are the typical
  contract between Eloquent models and React components — return
  `SomeResource::collection($models)` or `new SomeResource($model)` from
  controllers consumed by the frontend. New JSON:API resources (see
  `SKILL.md`) are an alternative when the frontend needs JSON:API-shaped
  responses specifically (sparse fieldsets, `included`, etc.) — not needed
  for a plain internal API.
- SCSS compiles through Vite (`resources/sass` → `laravel-vite-plugin`),
  unrelated to any Laravel 13 change — Vite/asset-pipeline config is
  independent of the framework major version.
