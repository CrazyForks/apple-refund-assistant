<?php

namespace App\Models;

use App\Casts\SafeEnumCast;
use App\Enums\EnvironmentEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kra8\Snowflake\HasShortflakePrimary;





/**
 * @property int $id
 * @property int $app_id
 * @property string|null $notification_uuid
 * @property $notification_type
 * @property string|null $bundle_id
 * @property $environment
 * @property string|null $request_body
 * @property string|null $payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\App|null $app
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereBundleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereEnvironment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereNotificationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereNotificationUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereRequestBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotificationRawLog extends Model
{
    use HasShortflakePrimary;

    protected $casts = [
        'environment' => [SafeEnumCast::class, EnvironmentEnum::class],
        'notification_type' => [SafeEnumCast::class, NotificationTypeEnum::class],
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }
}
