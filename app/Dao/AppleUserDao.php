<?php

namespace App\Dao;

use App\Models\App;
use App\Models\AppleUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AppleUserDao
{

    public function find(?string $appAccountToken, int $appId) : ?AppleUser
    {
        if (empty($appAccountToken)) {
            return null;
        }

        return AppleUser::query()
            ->where('app_account_token', $appAccountToken)
            ->where('app_id', $appId)
            ->first();
    }

    /**
     * Find or create AppleUser (using Laravel's firstOrCreate for atomic operation)
     * Sets register_at on first creation
     */
    public function firstOrCreate(string $appAccountToken, int $appId, ?int $registerTimestamp = null): AppleUser
    {
        $registerAt = $registerTimestamp 
            ? Carbon::createFromTimestamp($registerTimestamp)
            : Carbon::now();

        // Use Laravel's firstOrCreate for atomic operation and race condition prevention
        return AppleUser::firstOrCreate(
            [
                'app_account_token' => $appAccountToken,
                'app_id' => $appId,
            ],
            [
                'purchased_dollars' => 0,
                'refunded_dollars' => 0,
                'play_seconds' => 0,
                'register_at' => $registerAt,
            ]
        );
    }

    /**
     * Increment user's purchased dollars
     */
    public function incrementPurchased(int $userId, float $dollars): void
    {
        AppleUser::query()
            ->where('id', $userId)
            ->increment('purchased_dollars', $dollars);
    }

    /**
     * Increment user's refunded dollars
     */
    public function incrementRefunded(int $userId, float $dollars): void
    {
        AppleUser::query()
            ->where('id', $userId)
            ->increment('refunded_dollars', $dollars);
    }

    /**
     * Increment refunded dollars by appAccountToken (optimized for refunds)
     * Returns the number of affected rows
     */
    public function incrementRefundedByToken(string $appAccountToken, int $appId, float $dollars): int
    {
        return AppleUser::query()
            ->where('app_account_token', $appAccountToken)
            ->where('app_id', $appId)
            ->increment('refunded_dollars', $dollars);
    }
}
