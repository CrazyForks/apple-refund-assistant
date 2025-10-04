<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kra8\Snowflake\HasShortflakePrimary;

/**
 * @property int $id
 * @property string $app_account_token
 * @property int $app_id
 * @property string $purchased_dollars
 * @property string $refunded_dollars
 * @property int $play_seconds
 * @property \Illuminate\Support\Carbon|null $register_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\App|null $app
 * @method static \Database\Factories\AppleUserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppleUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppleUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppleUser query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppleUser whereAppAccountToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppleUser whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppleUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppleUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppleUser wherePlaySeconds($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppleUser wherePurchasedDollars($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppleUser whereRefundedDollars($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppleUser whereRegisterAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AppleUser whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class AppleUser extends Model
{
    use HasFactory, HasShortflakePrimary;

    protected $guarded = [];
    
    protected $casts = [
        'register_at' => 'datetime',
    ];
    
    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }
}
