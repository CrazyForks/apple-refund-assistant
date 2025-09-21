<?php

namespace App\Models;

use App\Enums\AppStatusEnum;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Kra8\Snowflake\HasShortflakePrimary;



/**
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property string|null $bundle_id
 * @property string|null $issuer_id
 * @property string|null $key_id
 * @property string|null $p8_key
 * @property string|null $test_notification_token
 * @property AppStatusEnum $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereBundleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereIssuerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereKeyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereP8Key($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereTestNotificationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class App extends Model
{
    use HasShortflakePrimary;

    protected $guarded = [];
    protected $casts = [
        'status' => AppStatusEnum::class,
    ];

    protected function p8Key(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => empty($value) ? '' : Crypt::decryptString($value),
            set: fn (?string $value) => empty($value) ? null : Crypt::encryptString($value),
        );
    }

}
