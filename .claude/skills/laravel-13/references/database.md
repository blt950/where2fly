# Database: Query Builder (Laravel 13.x)

Source: https://laravel.com/docs/13.x/queries. Fetch that URL directly for
anything beyond what's below (joins beyond inner/left/right, unions,
full-text search, reusable query components, debugging helpers). Migration
and transaction syntax below is standard and effectively unchanged from
Laravel 12 — fetch `/docs/13.x/migrations` for column-type specifics
(`vector`, spatial types) if working with newer column types.

## Basic queries

```php
use Illuminate\Support\Facades\DB;

$users = DB::table('users')->get();

$users = DB::table('users')
    ->select('name', 'email as user_email')
    ->where('votes', '>', 100)
    ->orderBy('name')
    ->limit(10)
    ->get();

// value shorthand — assumes `=`
$users = DB::table('users')->where('votes', 100)->get();

// array of conditions
$users = DB::table('users')->where([
    ['status', '=', '1'],
    ['subscribed', '<>', '1'],
])->get();
```

PDO parameter binding protects query *values* automatically — never
interpolate user input into column/order-by names, since PDO can't bind
those.

## Where variants worth knowing

```php
// OR, grouped to avoid clobbering global scopes
$users = DB::table('users')
    ->where('votes', '>', 100)
    ->orWhere(function ($query) {
        $query->where('name', 'Abigail')->where('votes', '>', 50);
    })
    ->get();

// negation
$products = DB::table('products')->whereNot(function ($query) {
    $query->where('clearance', true)->orWhere('price', '<', 10);
})->get();

// same condition across multiple columns
$users = DB::table('users')
    ->whereAny(['name', 'email', 'phone'], 'like', 'Example%')
    ->get();
```

## Joins

```php
$users = DB::table('users')
    ->join('contacts', 'users.id', '=', 'contacts.user_id')
    ->join('orders', 'users.id', '=', 'orders.user_id')
    ->select('users.*', 'contacts.phone', 'orders.price')
    ->get();

DB::table('users')->leftJoin('posts', 'users.id', '=', 'posts.user_id')->get();
DB::table('users')->rightJoin('posts', 'users.id', '=', 'posts.user_id')->get();
```

## Insert / update / delete

```php
DB::table('users')->insert(['email' => 'kayla@example.com', 'votes' => 0]);

DB::table('users')->insert([
    ['email' => 'picard@example.com', 'votes' => 0],
    ['email' => 'janeway@example.com', 'votes' => 0],
]);

$id = DB::table('users')->insertGetId(['email' => 'john@example.com', 'votes' => 0]);

DB::table('flights')->upsert(
    [
        ['departure' => 'Oakland', 'destination' => 'San Diego', 'price' => 99],
        ['departure' => 'Chicago', 'destination' => 'New York', 'price' => 150],
    ],
    ['departure', 'destination'], // uniqueBy — must be non-empty (13.x validates this)
    ['price'],                     // columns to update on conflict
);

$affected = DB::table('users')->where('id', 1)->update(['votes' => 1]);

// JSON column update
DB::table('users')->where('id', 1)->update(['options->enabled' => true]);

DB::table('users')->updateOrInsert(
    ['email' => 'john@example.com', 'name' => 'John'],
    ['votes' => '2'],
);

DB::table('users')->increment('votes', 5);
DB::table('users')->incrementEach(['votes' => 5, 'balance' => 100]);

$deleted = DB::table('users')->where('votes', '>', 100)->delete();
```

## Vector similarity clauses (new in 13, PostgreSQL + pgvector only)

Requires the `pgvector` PostgreSQL extension. Ensure it before creating
`vector` columns:

```php
use Illuminate\Support\Facades\Schema;

Schema::ensureVectorExtensionExists();
```

`whereVectorSimilarTo` filters by cosine similarity and orders by relevance
automatically. `minSimilarity` ranges `0.0`–`1.0` (`1.0` = identical):

```php
$documents = DB::table('documents')
    ->whereVectorSimilarTo('embedding', $queryEmbedding, minSimilarity: 0.4)
    ->limit(10)
    ->get();
```

The vector argument accepts a **plain string** too — Laravel generates the
embedding automatically via the Laravel AI SDK:

```php
$documents = DB::table('documents')
    ->whereVectorSimilarTo('embedding', 'Best wineries in Napa Valley')
    ->limit(10)
    ->get();
```

Disable the automatic relevance ordering with `order: false` to sort by
something else instead:

```php
$documents = DB::table('documents')
    ->whereVectorSimilarTo('embedding', $queryEmbedding, minSimilarity: 0.4, order: false)
    ->orderBy('created_at', 'desc')
    ->limit(10)
    ->get();
```

For finer control, compose the primitives directly:

```php
$documents = DB::table('documents')
    ->select('*')
    ->selectVectorDistance('embedding', $queryEmbedding, as: 'distance')
    ->whereVectorDistanceLessThan('embedding', $queryEmbedding, maxDistance: 0.3)
    ->orderByVectorDistance('embedding', $queryEmbedding)
    ->limit(10)
    ->get();
```

## Transactions

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () {
    DB::table('users')->update(['votes' => 1]);
    DB::table('posts')->delete();
});

// manual control
DB::beginTransaction();
try {
    // ...
    DB::commit();
} catch (\Throwable $e) {
    DB::rollBack();
    throw $e;
}
```

## Pessimistic locking

```php
DB::table('users')->where('votes', '>', 100)->sharedLock()->get();
DB::table('users')->where('votes', '>', 100)->lockForUpdate()->get();
```

## Migrations — quick reminders

Standard `Schema::create`/`Schema::table` + `Blueprint` API is unchanged.
For soft deletes:

```php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

Schema::table('flights', function (Blueprint $table) {
    $table->softDeletes();
});
```

Fetch `/docs/13.x/migrations` directly for column-type specifics (`vector`,
spatial/geo types, UUID/ULID columns) — these vary by database driver and
are easy to get subtly wrong from memory.
