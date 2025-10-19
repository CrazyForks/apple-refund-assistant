<?php

namespace App\Models;

use App\Casts\SafeEnumCast;
use App\Enums\EnvironmentEnum;
use App\Enums\NotificationLogStatusEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Kra8\Snowflake\HasShortflakePrimary;

/**
 * @property int $id
 * @property int $app_id
 * @property string|null $notification_uuid
 * @property $notification_type
 * @property string|null $bundle_id
 * @property string|null $bundle_version
 * @property $environment
 * @property string|null $payload
 * @property $status
 * @property int|null $forward_success
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\App|null $app
 * @property-read \App\Models\NotificationRawLog|null $raw
 *
 * @method static \Database\Factories\NotificationLogFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereBundleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereBundleVersion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereEnvironment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereForwardSuccess($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereNotificationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereNotificationUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog wherePayload($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|NotificationLog whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class NotificationLog extends Model
{
    use HasFactory, HasShortflakePrimary;

    protected $guarded = [];

    protected $casts = [
        'status' => [SafeEnumCast::class, NotificationLogStatusEnum::class],
        'environment' => [SafeEnumCast::class, EnvironmentEnum::class],
        'notification_type' => [SafeEnumCast::class, NotificationTypeEnum::class],
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function raw(): HasOne
    {
        return $this->hasOne(NotificationRawLog::class, 'id', 'id');
    }

    /**
     * Get decoded payload data as array
     */
    public function getPayloadData(): array
    {
        return json_decode($this->payload ?? '{}', true) ?? [];
    }

    /**
     * Get payload as DTO object (type-safe)
     */
    public function getPayloadDto(): \App\Dto\PayloadDto
    {
        return \App\Dto\PayloadDto::fromRawPayload($this->getPayloadData());
    }

    /**
     * Get transaction info from payload (type-safe)
     */
    public function getTransactionInfo(): ?\App\Dto\TransactionInfoDto
    {
        return $this->getPayloadDto()->transactionInfo;
    }

    /**
     * Get consumption request reason
     */
    public function getConsumptionRequestReason(): ?string
    {
        return $this->getPayloadDto()->consumptionRequestReason;
    }
}
