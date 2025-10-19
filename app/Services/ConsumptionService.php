<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\App;
use App\Models\AppleUser;
use App\Models\ConsumptionLog;
use App\Models\TransactionLog;
use App\Repositories\AppleUserRepository;
use App\Repositories\AppRepository;
use App\Repositories\TransactionLogRepository;
use Carbon\Carbon;
use Readdle\AppStoreServerAPI\RequestBody\ConsumptionRequestBody;

class ConsumptionService
{
    public function __construct(
        protected AppleUserRepository $appleUserRepo,
        protected AppRepository $appRepo,
        protected TransactionLogRepository $transactionRepo,
    ) {}

    public function makeConsumptionRequest(ConsumptionLog $log): array
    {
        // Get user data
        $app = $log->app;
        $transaction = $this->transactionRepo->findTransactionByConsumption($log);
        $user = $this->appleUserRepo->find($log->app_account_token, $log->app_id);

        return [
            'accountTenure' => $this->accountTenure($user),
            'appAccountToken' => $log->app_account_token ?: '',
            'consumptionStatus' => $this->consumptionStatus($transaction),
            'customerConsented' => true,
            'deliveryStatus' => ConsumptionRequestBody::DELIVERY_STATUS__DELIVERED,
            'lifetimeDollarsPurchased' => $this->lifetimeDollarsPurchased($user),
            'lifetimeDollarsRefunded' => $this->lifetimeDollarsRefunded($user),
            'platform' => ConsumptionRequestBody::PLATFORM__APPLE,
            'playTime' => $this->playtime($user),
            'refundPreference' => $this->refundPreference($transaction),
            'sampleContentProvided' => $this->sampleContentProvided($app),
            'userStatus' => ConsumptionRequestBody::USER_STATUS__ACTIVE,
        ];
    }

    private function sampleContentProvided(App $app): bool
    {
        return boolval($app->sample_content_provided);
    }

    private function playTime(?AppleUser $user): int
    {
        if (is_null($user?->play_seconds)) {
            return ConsumptionRequestBody::PLAY_TIME__UNDECLARED;
        }

        $playMinutes = ($user->play_seconds) / 60;

        return match (true) {
            $playMinutes > 16 * 24 * 60 => ConsumptionRequestBody::PLAY_TIME__OVER_16_DAYS,
            $playMinutes > 4 * 24 * 60 => ConsumptionRequestBody::PLAY_TIME__16_DAYS,
            $playMinutes > 24 * 60 => ConsumptionRequestBody::PLAY_TIME__4_DAYS,
            $playMinutes > 6 * 60 => ConsumptionRequestBody::PLAY_TIME__1_DAY,
            $playMinutes > 60 => ConsumptionRequestBody::PLAY_TIME__6_HOURS,
            $playMinutes > 5 => ConsumptionRequestBody::PLAY_TIME__1_HOUR,
            default => ConsumptionRequestBody::PLAY_TIME__5_MINUTES,
        };
    }

    private function lifetimeDollarsPurchased(?AppleUser $user): int
    {
        if (is_null($user)) {
            return ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__0;
        }

        $dollars = $user->purchased_dollars ?? 0;

        // https://developer.apple.com/documentation/appstoreserverapi/lifetimedollarspurchased
        return match (true) {
            $dollars >= 2000 => ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__OVER_2000,
            $dollars >= 1000 => ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__2000,
            $dollars >= 500 => ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__1000,
            $dollars > 100 => ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__500,
            $dollars > 50 => ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__100,
            $dollars > 0.01 => ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__50,
            default => ConsumptionRequestBody::LIFETIME_DOLLARS_PURCHASED__0,
        };
    }

    private function lifetimeDollarsRefunded(?AppleUser $user): int
    {
        if (is_null($user)) {
            return ConsumptionRequestBody::LIFETIME_DOLLARS_REFUNDED__0;
        }

        $dollars = $user->refunded_dollars ?? 0;

        // https://developer.apple.com/documentation/appstoreserverapi/lifetimedollarspurchased
        return match (true) {
            $dollars >= 2000 => ConsumptionRequestBody::LIFETIME_DOLLARS_REFUNDED__OVER_2000,
            $dollars >= 1000 => ConsumptionRequestBody::LIFETIME_DOLLARS_REFUNDED__2000,
            $dollars >= 500 => ConsumptionRequestBody::LIFETIME_DOLLARS_REFUNDED__1000,
            $dollars > 100 => ConsumptionRequestBody::LIFETIME_DOLLARS_REFUNDED__500,
            $dollars > 50 => ConsumptionRequestBody::LIFETIME_DOLLARS_REFUNDED__100,
            $dollars > 0.01 => ConsumptionRequestBody::LIFETIME_DOLLARS_REFUNDED__50,
            default => ConsumptionRequestBody::LIFETIME_DOLLARS_REFUNDED__0,
        };
    }

    private function accountTenure(?AppleUser $user): int
    {
        // https://developer.apple.com/documentation/appstoreserverapi/accounttenure
        $registerAt = Carbon::make($user->register_at ?? null);
        if (! $registerAt) {
            return ConsumptionRequestBody::ACCOUNT_TENURE__UNDECLARED;
        }

        $diffDays = Carbon::now()->diff($registerAt)->days ?? 0;

        return match (true) {
            $diffDays > 365 => ConsumptionRequestBody::ACCOUNT_TENURE__OVER_365,
            $diffDays > 180 => ConsumptionRequestBody::ACCOUNT_TENURE__365,
            $diffDays > 90 => ConsumptionRequestBody::ACCOUNT_TENURE__180,
            $diffDays > 30 => ConsumptionRequestBody::ACCOUNT_TENURE__90,
            $diffDays > 10 => ConsumptionRequestBody::ACCOUNT_TENURE__30,
            $diffDays > 3 => ConsumptionRequestBody::ACCOUNT_TENURE__10,
            default => ConsumptionRequestBody::ACCOUNT_TENURE__3,
        };
    }

    private function consumptionStatus(?TransactionLog $transaction): int
    {
        $expirationDate = Carbon::make($transaction->expiration_date ?? null);
        if (is_null($expirationDate)) {
            return ConsumptionRequestBody::CONSUMPTION_STATUS__UNDECLARED;
        }

        $gtToday = Carbon::now()->gt($expirationDate);
        if ($gtToday) {
            return ConsumptionRequestBody::CONSUMPTION_STATUS__FULLY_CONSUMED;
        }

        return ConsumptionRequestBody::CONSUMPTION_STATUS__PARTIALLY_CONSUMED;
    }

    private function refundPreference(?TransactionLog $transaction): int
    {
        $expirationDate = Carbon::make($transaction->expiration_date ?? null);
        if (is_null($expirationDate)) {
            return ConsumptionRequestBody::REFUND_PREFERENCE__UNDECLARED;
        }

        if (Carbon::now()->gt($expirationDate)) {
            return ConsumptionRequestBody::REFUND_PREFERENCE__DECLINE;
        }

        return ConsumptionRequestBody::REFUND_PREFERENCE__UNDECLARED;
    }
}
