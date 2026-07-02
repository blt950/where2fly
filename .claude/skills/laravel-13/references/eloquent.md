# Eloquent (Laravel 13.x)

Source: https://laravel.com/docs/13.x/eloquent (model config, scopes) and
https://laravel.com/docs/13.x/eloquent-mutators (casts). Fetch
`/docs/13.x/eloquent-relationships` and `/docs/13.x/eloquent-factories`
directly for relationship/factory syntax beyond the basics below — they
were not pulled into this reference and their APIs are effectively
unchanged from Laravel 12, but confirm before relying on memory for
anything non-trivial (polymorphic relations, custom pivot models, etc.).

## Model definition: attribute style vs. property style

Laravel 13 lets you configure a model either the traditional way
(protected properties) or via first-party PHP attributes on the class.
**Both work; neither is deprecated.** Attributes read better when a model
has several non-default settings; properties are fine for one-offs and are
what most existing Laravel 12 code already uses — don't rewrite working
models just to switch style.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Connection;
use Illuminate\Database\Eloquent\Attributes\DateFormat;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Model;

#[Table('my_flights', key: 'flight_id')]
#[Connection('mysql')]
#[DateFormat('U')]
#[Fillable(['name', 'destination'])]
class Flight extends Model
{
    // ...
}
```

Equivalent property-based version:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    protected $table = 'my_flights';
    protected $primaryKey = 'flight_id';
    protected $connection = 'mysql';
    protected $dateFormat = 'U';
    protected $fillable = ['name', 'destination'];
}
```

### Attribute-by-attribute reference

| Attribute | Purpose | Equivalent property |
|---|---|---|
| `#[Table('name', key: ..., keyType: ..., incrementing: ..., timestamps: ..., dateFormat: ...)]` | Table name + primary key + timestamp config, all in one place | `$table`, `$primaryKey`, `$keyType`, `$incrementing`, `$timestamps`, `$dateFormat` |
| `#[WithoutIncrementing]` | Shortcut for non-auto-incrementing PK | `$incrementing = false` |
| `#[WithoutTimestamps]` | Disable `created_at`/`updated_at` management | `$timestamps = false` |
| `#[DateFormat('U')]` | Just the date format, without touching table config | `$dateFormat` |
| `#[Connection('mysql')]` | Non-default DB connection | `$connection` |
| `#[Fillable([...])]` | Mass-assignable attributes (required to use `create()`/`fill()`) | `$fillable` |
| `#[Guarded([...])]` | Inverse of Fillable — guard specific attributes, allow the rest | `$guarded` |
| `#[Unguarded]` | Disable mass-assignment protection entirely (hand-craft all input arrays if used) | `$guarded = []` (special-cased) |
| `#[ScopedBy([SomeScope::class])]` | Attach one or more global scopes | `static::addGlobalScope(...)` in `booted()` |

`Fillable` also supports JSON column paths: `#[Fillable(['options->enabled'])]`.

UUID/ULID primary keys still use traits, not attributes:

```php
use Illuminate\Database\Eloquent\Concerns\HasUuids; // or HasUlids
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasUuids;
}

$article = Article::create(['title' => 'Traveling to Europe']);
$article->id; // "018f2b5c-6a7f-7b12-9d6f-2f8a4e0c9c11" — UUIDv7 by default
```

## Query scopes

### Local scopes: `#[Scope]` attribute (new style) replaces `scopeXxx()`

Laravel 13's preferred style: a `protected` method with `#[Scope]`, named
after the scope itself (no `scope` prefix):

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    #[Scope]
    protected function popular(Builder $query): void
    {
        $query->where('votes', '>', 100);
    }

    #[Scope]
    protected function ofType(Builder $query, string $type): void
    {
        $query->where('type', $type);
    }
}
```

```php
$users = User::popular()->ofType('admin')->orderBy('created_at')->get();
```

Scope methods must return `void` or the same builder — don't `return $query;`
explicitly when using `void`. When calling an attributed scope *from inside
the model class itself*, go through `static::query()->popular()`, not a bare
`static::popular()`, so the call is routed through Eloquent's scope handling.

Chaining scopes with `or` needs a closure (or the higher-order `orWhere`):

```php
$users = User::popular()->orWhere(function (Builder $query) {
    $query->active();
})->get();

// equivalent, no closure:
$users = User::popular()->orWhere->active()->get();
```

### Global scopes

```php
<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AncientScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $builder->where('created_at', '<', now()->minus(years: 2000));
    }
}
```

Attach via attribute (preferred) or `booted()`:

```php
#[ScopedBy([AncientScope::class])]
class User extends Model {}
```

Remove per-query with `withoutGlobalScope(AncientScope::class)`,
`withoutGlobalScopes([...])`, or `withoutGlobalScopesExcept([...])`.

### Pending attributes on scopes

`withAttributes()` inside a scope both constrains the query *and* seeds
attributes on models created via that scope:

```php
#[Scope]
protected function draft(Builder $query): void
{
    $query->withAttributes(['hidden' => true]);
}

$draft = Post::draft()->create(['title' => 'In Progress']);
$draft->hidden; // true
```

Pass `asConditions: false` to seed attributes without adding `where` clauses.

## Casts

Casts are defined via a `casts()` method (not an attribute), returning an
array of `attribute => type`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected function casts(): array
    {
        return [
            'is_admin' => 'boolean',
            'options' => 'array',
            'joined_at' => 'datetime',
            'balance' => 'decimal:2',
            'settings' => 'encrypted:array',
        ];
    }
}
```

Supported built-in cast types: `array`, `AsFluent::class`,
`AsStringable::class`, `AsUri::class`, `boolean`, `collection`, `date`,
`datetime`, `immutable_date`, `immutable_datetime`, `decimal:<precision>`,
`double`, `encrypted`, `encrypted:array`, `encrypted:collection`,
`encrypted:object`, `float`, `hashed`, `integer`, `object`, `real`,
`string`, `timestamp`.

For custom cast classes (value objects, inbound-only casts, cast
parameters, `Castable`), fetch
`https://laravel.com/docs/13.x/eloquent-mutators#custom-casts` directly —
not duplicated here since it's rarely needed day-to-day and the exact
interface methods matter.

## Common query/CRUD patterns

```php
// Retrieve
$flight = Flight::find(1);
$flight = Flight::where('active', 1)->first();
$flight = Flight::firstWhere('active', 1);
$flight = Flight::findOrFail(1);          // 404s if unhandled in a route
$flight = Flight::firstOrCreate(['name' => 'London to Paris']);
$flight = Flight::updateOrCreate(
    ['departure' => 'Oakland', 'destination' => 'San Diego'],
    ['price' => 99, 'discounted' => 1]
);

// Insert / update
$flight = Flight::create(['name' => 'London to Paris']); // needs Fillable/Guarded
$flight->update(['name' => 'Paris to London']);
$flight->fill(['name' => 'Amsterdam to Frankfurt'])->save();

// Upsert (needs a primary/unique index on the uniqueBy columns)
Flight::upsert(
    [
        ['departure' => 'Oakland', 'destination' => 'San Diego', 'price' => 99],
        ['departure' => 'Chicago', 'destination' => 'New York', 'price' => 150],
    ],
    uniqueBy: ['departure', 'destination'],
    update: ['price'],
);

// Delete
Flight::destroy(1, 2, 3);
Flight::where('active', 0)->delete(); // no model events fired (mass delete)
```

### Soft deletes

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Flight extends Model
{
    use SoftDeletes; // casts deleted_at to Carbon automatically
}

Flight::withTrashed()->where('account_id', 1)->get();
Flight::onlyTrashed()->get();
$flight->restore();
$flight->forceDelete(); // permanent
```

### Pruning (periodic cleanup)

```php
use Illuminate\Database\Eloquent\Prunable; // or MassPrunable for bulk deletes

class Flight extends Model
{
    use Prunable;

    public function prunable(): Builder
    {
        return static::where('created_at', '<=', now()->minus(months: 1));
    }
}
```

Schedule with `Schedule::command('model:prune')->daily();` in
`routes/console.php`.

## Relationships and factories

Standard relationship methods (`hasOne`, `hasMany`, `belongsTo`,
`belongsToMany`, `hasManyThrough`, `morphTo`, etc.) and factory syntax
(`Model::factory()->count(3)->create()`) are unchanged from Laravel 12.
Fetch `/docs/13.x/eloquent-relationships` or `/docs/13.x/eloquent-factories`
for exact syntax on anything beyond basic `hasMany`/`belongsTo` — especially
polymorphic relations, custom pivot models (table-name pluralization
changed in 13, see `references/upgrade-12-to-13.md`), and factory states.
