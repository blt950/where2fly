<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

/**
 * Text pages shown inside the shell, so opening them keeps the map. Each body stays a Blade
 * partial (resources/views/content) and is sent to the page rendered.
 */
class ContentController extends Controller
{
    private const PAGES = [
        'changelog' => ['title' => 'Changelog', 'description' => 'Check out the details of the new updates'],
        'privacy' => ['title' => 'Privacy Policy', 'description' => "We're only using strictly neccesarry service cookies to make the page and search work for your visit."],
        'api' => ['title' => 'API Documentation', 'tab' => 'API Docs', 'description' => 'Documentation of the API'],
    ];

    /** `page` comes from the route's defaults (routes/web.php), one route per page so each keeps its name */
    public function show(string $page): Response
    {
        $meta = self::PAGES[$page];
        $tab = $meta['tab'] ?? $meta['title'];

        return Inertia::render('Content', [
            'title' => $meta['title'],
            'tab' => $tab,
            'html' => view("content.{$page}")->render(),
        ])->withViewData(['title' => $tab, 'metaDescription' => $meta['description']]);
    }
}
