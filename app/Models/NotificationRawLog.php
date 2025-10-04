<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\NotificationLog|null $log
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog query()
 * @mixin \Eloquent
 */
class NotificationRawLog extends Model
{
    public $incrementing = false;

    protected $guarded = [];


    public function log(): BelongsTo
    {
        return $this->belongsTo(NotificationLog::class, 'id', 'id');
    }
}

