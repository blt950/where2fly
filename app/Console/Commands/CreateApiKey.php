<?php

namespace App\Console\Commands;

use App\Models\ApiKey;
use Illuminate\Console\Command;
use Ramsey\Uuid\Uuid;

class CreateApiKey extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:apikey';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates an API key';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->ask('What should we name the API Key?');
        $ip = $this->ask('Which IP address can use this key?');

        // Generate key
        $secret = Uuid::uuid4();
        ApiKey::create([
            'id' => $secret,
            'name' => $name,
            'ip_address' => $ip,
        ]);

        $this->comment('API key `' . $name . '` has been created with following token: `' . $secret . '` and IP `' . $ip . '`');
    }
}
