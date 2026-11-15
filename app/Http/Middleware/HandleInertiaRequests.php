<?php

namespace App\Http\Middleware;

use App\Helpers\AlertHelper;
use App\Helpers\MapHintHelper;
use App\Helpers\NavigationHelper;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;
use Inertia\Support\Header;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'inertia';

    /**
     * A visit from the shell can end on a page that is still Blade, e.g. a failed search redirecting
     * to the form. The client can't render that, so turn it into a full page load, and reflash so
     * the errors and old input survive the extra request.
     */
    public function handle(Request $request, Closure $next)
    {
        $response = parent::handle($request, $next);

        if ($request->header(Header::INERTIA) && $response->isSuccessful() && ! $response->headers->has(Header::INERTIA)) {
            $request->session()->reflash();

            return Inertia::location($request->fullUrl());
        }

        return $response;
    }

    /**
     * The page URL exactly as requested. The default, fullUrl(), sorts the query and re-indexes
     * arrays (destinations[0]=), which would change the shape of every bookmarked search.
     */
    public function urlResolver()
    {
        return fn (Request $request) => $request->getRequestUri();
    }

    /**
     * The navigation, alerts and map hint, from the same helpers the Blade layouts use
     * (through view composers in AppServiceProvider).
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'isProduction' => app()->isProduction(),
            'nav' => fn () => NavigationHelper::build(),
            'alerts' => fn () => AlertHelper::build(),
            'mapHint' => fn () => MapHintHelper::current(),
            // Changes whenever one of the user's lists is saved, hidden or removed: the map
            // refetches the lists it draws when it does
            'listsRevision' => fn () => ($user = $request->user())
                ? $user->lists()->count() . '@' . $user->lists()->max('updated_at')
                : null,
        ];
    }
}
