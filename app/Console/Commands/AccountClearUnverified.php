<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class AccountClearUnverified extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'account:clear-unverified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Delete users who haven't verified their email address for 7 days
        $users = User::where('email_verified_at', null)
            ->where('created_at', '<', now()->subDays(7))
            ->get();

        $users->each->delete();

        $this->info('Deleted ' . $users->count() . ' unverified users.');
    }
}
