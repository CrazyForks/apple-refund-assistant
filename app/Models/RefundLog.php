<?php

namespace App\Models;

use App\Enums\EnvironmentEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Kra8\Snowflake\HasShortflakePrimary;


/**
 * @property int $id
 * @property string|null $apple_account_token
 * @property int $app_id
 * @property string|null $notification_uuid
 * @property string|null $notification_type
 * @property string|null $subtype
 * @property string|null $bundle_id
 * @property EnvironmentEnum|null $environment
 * @property string|null $purchase_date
 * @property string $original_transaction_id
 * @property string $transaction_id
 * @property string $price
 * @property string|null $currency
 * @property string|null $refund_date
 * @property string|null $refund_reason
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereAppleAccountToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereBundleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereEnvironment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereNotificationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereNotificationUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereOriginalTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereRefundDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereRefundReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereSubtype($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RefundLog extends Model
{
    use HasShortflakePrimary;

    protected $casts = [
        'environment' => EnvironmentEnum::class,
    ];
}
