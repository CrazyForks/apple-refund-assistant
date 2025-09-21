<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Kra8\Snowflake\HasShortflakePrimary;

/**
 * @property int $id
 * @property string|null $apple_account_token
 * @property string $transaction_id
 * @property string $original_transaction_id
 * @property string $amount
 * @property string $currency
 * @property string $refund_at
 * @property string|null $refund_reason
 * @property string|null $environment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereAppleAccountToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereEnvironment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereOriginalTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereRefundAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereRefundReason($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereUpdatedAt($value)
 * @property string $notification_uuid
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereNotificationUuid($value)
 * @property int $app_id
 * @property string $bundle_id
 * @property string $purchase_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog whereBundleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RefundLog wherePurchaseAt($value)
 * @mixin \Eloquent
 */
class RefundLog extends Model
{
    //
    use HasShortflakePrimary;
}
