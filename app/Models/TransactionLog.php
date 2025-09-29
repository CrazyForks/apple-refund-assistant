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
 * @property string $original_transaction_id
 * @property string|null $app_account_token
 * @property string $transaction_id
 * @property string|null $product_id
 * @property string|null $product_type
 * @property string|null $purchase_date
 * @property string|null $original_purchase_date
 * @property string|null $expiration_date
 * @property string|null $price
 * @property string|null $currency
 * @property string|null $in_app_ownership_type
 * @property int $quantity
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\App|null $app
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereAppAccountToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereAppId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereBundleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereEnvironment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereExpirationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereInAppOwnershipType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereNotificationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereNotificationUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereOriginalPurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereOriginalTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereProductType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog wherePurchaseDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransactionLog whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TransactionLog extends Model
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
