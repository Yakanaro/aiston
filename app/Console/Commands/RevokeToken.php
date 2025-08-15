<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;

class RevokeToken extends Command
{
    protected $signature = 'token:revoke {id : Token ID}';

    protected $description = 'Revoke an API token by ID';

    public function handle(): int
    {
        $id = (int) $this->argument('id');
        $token = ApiToken::find($id);
        if (!$token) {
            $this->error('Not found');

            return self::FAILURE;
        }
        $token->update(['revoked_at' => now()]);
        $this->info('Revoked');

        return self::SUCCESS;
    }
}
