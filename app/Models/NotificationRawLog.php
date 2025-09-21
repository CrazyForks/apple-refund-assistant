<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kra8\Snowflake\HasShortflakePrimary;



/**
 * @property int $id
 * @property string $notification_uuid
 * @property int $app_id
 * @property string $notification_type
 * @property string|null $environment
 * @property string|null $bundle_id
 * @property string|null $subtype
 * @property string|null $request_body
 * @property string|null $payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereSubtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationRawLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class NotificationRawLog extends Model
{
    use HasShortflakePrimary;

    protected $fillable = ['notification_uuid', 'app_id'];
}
