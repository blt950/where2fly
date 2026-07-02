# Routing & Controllers (Laravel 13.x)

Sources: https://laravel.com/docs/13.x/routing and
https://laravel.com/docs/13.x/controllers. Fetch those directly for rate
limiting, CORS, and route caching details not covered below.

## Basic route definitions

```php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/greeting', fn () => 'Hello World');
Route::get('/user', [UserController::class, 'index']);

Route::get($uri, $callback);
Route::post($uri, $callback);
Route::put($uri, $callback);
Route::patch($uri, $callback);
Route::delete($uri, $callback);
Route::match(['get', 'post'], '/', fn () => /* ... */ null);
Route::any('/', fn () => /* ... */ null);
```

`routes/web.php` gets the `web` middleware group (session, CSRF).
`routes/api.php` (created via `php artisan install:api`, which also installs
Sanctum) gets the `api` group and an automatic `/api` prefix.

## Route parameters

```php
Route::get('/user/{id}', fn (string $id) => 'User '.$id);
Route::get('/user/{name?}', fn (?string $name = null) => $name); // optional
Route::get('/user/{id}', fn (string $id) => /* ... */ null)
    ->where('id', '[0-9]+'); // regex constraint
```

## Named routes & groups

```php
Route::get('/user/profile', fn () => /* ... */ null)->name('profile');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', fn () => /* ... */ null);
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', fn () => /* ... */ null)->name('users'); // → admin.users
});
```

## Route model binding

```php
// Implicit — type-hint the model, Laravel resolves by route-segment key
Route::get('/users/{user}', fn (User $user) => $user);

// Custom key
Route::get('/posts/{post:slug}', fn (Post $post) => $post);

// Implicit enum binding
Route::get('/orders/{status}', fn (OrderStatus $status) => $status);
```

`findOrFail`-style 404 behavior is automatic for implicit binding; a
missing/soft-deleted model produces a 404 unless customized (see resource
`missing()`/`withTrashed()` below).

## Controller middleware — three ways (all still valid)

**Route-file style** (unchanged from 12.x):

```php
Route::get('/profile', [UserController::class, 'show'])->middleware('auth');
```

**`HasMiddleware` interface** (unchanged from 12.x):

```php
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class UserController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            'auth',
            new Middleware('log', only: ['index']),
            new Middleware('subscribed', except: ['store']),
        ];
    }
}
```

**PHP attributes** (new option in 13.x, class- and method-level, merged
together):

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Attributes\Controllers\Middleware;

#[Middleware('auth')]
#[Middleware('log', only: ['index'])]
#[Middleware('subscribed', except: ['store'])]
class UserController
{
    #[Middleware('log')]
    public function index() { /* ... */ }
}
```

## Authorization attribute: `#[Authorize]` (new in 13.x)

Shortcut for the `can` middleware, checked against a policy:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Routing\Attributes\Controllers\Authorize;

class CommentController
{
    #[Authorize('create', [Comment::class, 'post'])]
    public function store(Post $post) { /* ... */ }

    #[Authorize('delete', 'comment')]
    public function destroy(Comment $comment) { /* ... */ }
}
```

First argument is the ability; second is the model class or route
parameter(s) passed to the policy method.

Combine class-level `#[Middleware('auth')]` with per-method `#[Authorize]`
for a typical protected+authorized action, as shown in the release notes:

```php
#[Middleware('auth')]
class CommentController
{
    #[Middleware('subscribed')]
    #[Authorize('create', [Comment::class, 'post'])]
    public function store(Post $post) { /* ... */ }
}
```

## Resource controllers

```php
Route::resource('photos', PhotoController::class);
Route::apiResource('photos', PhotoController::class);   // no create/edit routes
Route::resources(['photos' => PhotoController::class, 'posts' => PostController::class]);

Route::resource('photos', PhotoController::class)->only(['index', 'show']);
Route::resource('photos', PhotoController::class)->except(['destroy']);

// nested
Route::resource('photos.comments', PhotoCommentController::class);

// soft-deletable resources in bulk
Route::softDeletableResources(['photos' => PhotoController::class]);
```

Standard CRUD verb/URI/action/name table (`index`, `create`, `store`,
`show`, `edit`, `update`, `destroy`) is unchanged from prior versions.

### Fine-grained resource middleware (`middlewareFor` / `withoutMiddlewareFor`)

```php
Route::resource('users', UserController::class)
    ->middleware(['auth', 'verified']);              // all actions

Route::resource('users', UserController::class)
    ->middlewareFor('show', 'auth');                 // one action

Route::apiResource('users', UserController::class)
    ->middlewareFor(['show', 'update'], ['auth', 'verified']); // several

Route::middleware(['auth', 'verified', 'subscribed'])->group(function () {
    Route::resource('users', UserController::class)
        ->withoutMiddlewareFor('index', ['auth', 'verified']);
});
```

## Dependency injection in controllers

```php
class UserController extends Controller
{
    public function __construct(
        protected UserRepository $users, // constructor injection
    ) {}

    public function update(Request $request, string $id): RedirectResponse
    {
        // method injection: Request auto-resolved, {id} still bound by name
    }
}
```
