<?php

namespace App\Models;

use App\Casts\SafeEnumCast;
use App\Enums\AppStatusEnum;
use App\Enums\BoolEnum;
use Filament\Infolists\Components\TextEntry;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasCurrentTenantLabel;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
 * @property $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property int|null $sample_content_provided
 * @property string $transaction_dollars
 * @property string $refund_dollars
 * @property string $consumption_dollars
 * @property int $transaction_count
 * @property int $refund_count
 * @property int $consumption_count
 * @property int|null $owner_id
 * @property string|null $notification_url
 * @property int|null $pending_consumption_count
 * @property $activate
 * @property-read \App\Models\User|null $owner
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereBundleId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereConsumptionCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereConsumptionDollars($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereIssuerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereKeyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereNotificationUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereOwnerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereP8Key($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App wherePendingConsumptionCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereRefundCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereRefundDollars($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereSampleContentProvided($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereTestNotificationToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereTransactionCount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereTransactionDollars($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|App whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class App extends Model implements HasName, HasCurrentTenantLabel, HasAvatar
{
    use HasFactory, HasShortflakePrimary;

    protected $guarded = [];
    protected $casts = [
        'status' => [SafeEnumCast::class, AppStatusEnum::class],
        'activate' => [SafeEnumCast::class, BoolEnum::class],
    ];

    protected function p8Key(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => empty($value) ? '' : Crypt::decryptString($value),
            set: fn (?string $value) => empty($value) ? null : Crypt::encryptString($value),
        );
    }

    public function resolveRouteBinding($value, $field = null)
    {
        return $this->resolveRouteBindingQuery($this, intval($value), $field)->first();
    }

    public function owner(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function getCurrentTenantLabel(): string
    {
        return $this->status?->getLabel() ?? '-';
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $ok = $this->status === AppStatusEnum::NORMAL;
        if ($ok) {
            return '/assets/icon/status_ok.png';
        }

        return '/assets/icon/status_fail.png';
    }

}
