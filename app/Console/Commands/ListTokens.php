<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;

class ListTokens extends Command
{
    protected $signature = 'token:list';

    protected $description = 'List API tokens (no secrets shown)';

    public function handle(): int
    {
        $rows = ApiToken::query()
            ->orderBy('id')
            ->get(['id', 'name', 'token_hash', 'expires_at', 'revoked_at', 'last_used_at', 'created_at'])
            ->map(function ($t) {
                return [
                    $t->id,
                    $t->name,
                    substr($t->token_hash, 0, 10).'…',
                    optional($t->expires_at)->toDateTimeString(),
                    optional($t->revoked_at)->toDateTimeString(),
                    optional($t->last_used_at)->toDateTimeString(),
                    optional($t->created_at)->toDateTimeString(),
                ];
            })->toArray();

        $this->table(['ID', 'Name', 'Hash', 'Expires', 'Revoked', 'Last Used', 'Created'], $rows);

        return self::SUCCESS;
    }
}
