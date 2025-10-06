<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string|null $request_body
 * @property string|null $forward_msg
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\NotificationLog|null $log
 * @method static \Database\Factories\NotificationRawLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereForwardMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereRequestBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotificationRawLog extends Model
{
    use HasFactory;
    
    public $incrementing = false;

    protected $guarded = [];


    public function log(): BelongsTo
    {
        return $this->belongsTo(NotificationLog::class, 'id', 'id');
    }
}

