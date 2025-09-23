<?php

namespace App\Models;

use App\Casts\SafeEnumCast;
use App\Enums\ConsumptionLogStatusEnum;
use App\Enums\EnvironmentEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Kra8\Snowflake\HasShortflakePrimary;


/**
 * @property int $id
 * @property string|null $apple_account_token
 * @property int $app_id
 * @property string|null $notification_uuid
 * @property string|null $bundle_id
 * @property EnvironmentEnum|null $environment
 * @property string $original_transaction_id
 * @property string|null $app_account_token
 * @property string $transaction_id
 * @property string|null $consumption_request_reason
 * @property string|null $deadline_at
 * @property ConsumptionLogStatusEnum $status
 * @property string|null $status_msg
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereAppAccountToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereAppleAccountToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereBundleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereConsumptionRequestReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereDeadlineAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereEnvironment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereNotificationUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereOriginalTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereStatusMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ConsumptionLog extends Model
{
    use HasShortflakePrimary;

    protected $casts = [
        'environment' => EnvironmentEnum::class,
        'status' => ConsumptionLogStatusEnum::class,
    ];
}
