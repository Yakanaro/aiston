<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalLog query()
 *
 * @mixin \Eloquent
 */
class ExternalLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'service',
        'direction',
        'payload',
        'message',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
