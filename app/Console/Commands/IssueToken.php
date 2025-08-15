<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;

class IssueToken extends Command
{
    protected $signature = 'token:issue {--name= : Token name} {--expires= : ISO8601 duration (e.g., P30D)}';

    protected $description = 'Issue a new API token';

    public function handle(): int
    {
        $name = (string) ($this->option('name') ?: 'default');
        $plain = bin2hex(random_bytes(32));
        $hash = hash('sha256', $plain);

        $expires = null;
        $expiresOpt = $this->option('expires');
        if ($expiresOpt) {
            try {
                $interval = new \DateInterval($expiresOpt);
                $expires = now()->add($interval);
            } catch (\Throwable) {
                $this->error('Invalid --expires format. Use ISO8601 duration like P30D.');

                return self::FAILURE;
            }
        }

        ApiToken::create([
            'name' => $name,
            'token_hash' => $hash,
            'abilities' => null,
            'expires_at' => $expires,
        ]);

        $this->line($plain);

        return self::SUCCESS;
    }
}
