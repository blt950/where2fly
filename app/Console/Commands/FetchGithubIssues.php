<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FetchGithubIssues extends Command
{
    protected $signature = 'fetch:github';

    protected $description = 'Fetch GitHub issues and flag if new ones were added';

    public function handle()
    {
        $response = Http::withToken(config('app.github_key'))
            ->get('https://api.github.com/repos/blt950/where2fly/issues');

        $data = collect($response->json())->filter(function ($item) {
            if (isset($item['pull_request'])) {
                return false;
            }

            return collect($item['labels'] ?? [])->contains(function ($label) {
                return strtolower($label['name'] ?? '') === 'open for vote';
            });
        });

        $highestKnown = Cache::get('github_highest_issue', 0);
        $highestFetched = $data->max('number') ?? 0;

        if ($highestFetched > $highestKnown) {
            Cache::forever('github_highest_issue', $highestFetched);
            $this->info("New issue detected! Highest issue: #{$highestFetched}");
        } else {
            $this->info("No new issues. Highest issue: #{$highestFetched}");
        }

        $this->info('Fetched ' . count($data) . ' issues.');
        $this->info('Cache driver is ' . config('cache.default'));
    }
}
