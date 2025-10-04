<?php

namespace App\Models;

use App\Casts\SafeEnumCast;
use App\Enums\ConsumptionLogStatusEnum;
use App\Enums\EnvironmentEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\Traits\EnvironmentTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Kra8\Snowflake\HasShortflakePrimary;


/**
 * @property int $id
 * @property string|null $app_account_token
 * @property int $app_id
 * @property string|null $notification_uuid
 * @property string|null $bundle_id
 * @property string|null $bundle_version
 * @property $environment
 * @property string $original_transaction_id
 * @property string $transaction_id
 * @property string|null $consumption_request_reason
 * @property string|null $deadline_at
 * @property $status
 * @property string|null $status_msg
 * @property array<array-key, mixed>|null $send_body
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\App|null $app
 * @method static \Database\Factories\ConsumptionLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereAppAccountToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereBundleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereBundleVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereConsumptionRequestReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereDeadlineAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereEnvironment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereNotificationUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereOriginalTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereSendBody($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereStatusMsg($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ConsumptionLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ConsumptionLog extends Model
{
    use HasFactory, HasShortflakePrimary, EnvironmentTrait;

    protected $guarded = [];

    protected $casts = [
        'environment' => [SafeEnumCast::class, EnvironmentEnum::class],
        'status' => [SafeEnumCast::class, ConsumptionLogStatusEnum::class],
        'send_body' => 'json',
    ];


    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }
}
