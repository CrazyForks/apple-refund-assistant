<?php

declare(strict_types=1);

namespace App\Services;

use App\Dao\AppDao;
use App\Dao\AppleUserDao;
use App\Dao\ConsumptionLogDao;
use App\Dao\NotificationRawLogDao;
use App\Dao\RefundLogDao;
use App\Dao\TransactionLogDao;
use App\Enums\AppStatusEnum;
use App\Enums\ConsumptionLogStatusEnum;
use App\Enums\NotificationLogStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Exceptions\BundleIdMismatchException;
use App\Jobs\SendConsumptionInformationJob;
use App\Jobs\FinishNotificationJob;
use App\Models\App;
use App\Models\ConsumptionLog;
use App\Models\NotificationLog;
use App\Models\RefundLog;
use App\Models\TransactionLog;
use Illuminate\Support\Facades\Log;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerNotificationException;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class WebhookService
{
    public function __construct(
        protected AppDao $appDao,
        protected NotificationRawLogDao $rawLogDao,
        protected ConsumptionLogDao $consumptionLogDao,
        protected RefundLogDao $refundLogDao,
        protected TransactionLogDao $transactionLogDao,
        protected AppleUserDao $appleUserDao,
        protected IapService $iapService,
        protected AmountPriceService $priceService,
    ) {
    }


    /**
     * @throws AppStoreServerNotificationException
     * @throws BundleIdMismatchException
     */
    public function handleNotification(string $content, int $appId): NotificationLog
    {
        $payload = $this->iapService->decodePayload($content);

        $app = $this->appDao->find($appId);

        // Check for duplicate notifications using cache
        $log = $this->insertRawLog($content, $app, $payload);

        // Handle notification based on type
        switch ($log->notification_type) {
            case NotificationTypeEnum::TEST:
                $this->handleTest($app, $log);
                break;
            case NotificationTypeEnum::REFUND:
                $this->handleRefund($app, $log);
                break;
            case NotificationTypeEnum::REFUND_DECLINED:
                $this->handleRefundDeclined($app, $log);
                break;
            case NotificationTypeEnum::SUBSCRIBED:
            case NotificationTypeEnum::DID_RENEW:
            case NotificationTypeEnum::OFFER_REDEEMED:
            case NotificationTypeEnum::ONE_TIME_CHARGE:
                $this->handleTransaction($app, $log);
                break;
            case NotificationTypeEnum::CONSUMPTION_REQUEST:
                $this->handleConsumption($app, $log);
                break;

            default:
                Log::info("[{$log->notification_uuid}]{$log->notification_type}");
                break;
        }

        // NOTE: use fpm fast-cgi running in background
        dispatch(new FinishNotificationJob($log, $app))->afterResponse();

        return $log;
    }

    protected function handleConsumption(App $app, NotificationLog $log): ConsumptionLog
    {
        $dollar = $this->getTransactionDollar($log);
        $this->appDao->incrementConsumption($app->id, $dollar);

        $consumption = $this->consumptionLogDao->storeLog($app, $log);

        // NOTE: use fpm fast-cgi running in background
        dispatch(new SendConsumptionInformationJob($consumption))->afterResponse();

        return $consumption;
    }


    protected function handleTransaction(App $app, NotificationLog $log): TransactionLog
    {
        $dollar = $this->getTransactionDollar($log);
        $this->appDao->incrementTransaction($app->id, $dollar);

         // Create or get user and update purchased amount
         $transInfo = $log->getTransactionInfo();
         $appAccountToken = $transInfo?->appAccountToken;

         if (!empty($appAccountToken)) {
             // Use originalPurchaseDate as registration time (first purchase time)
             $registerTimestamp = $transInfo->getOriginalPurchaseDateTimestamp();
             $user = $this->appleUserDao->firstOrCreate($appAccountToken, $app->id, $registerTimestamp);
             $this->appleUserDao->incrementPurchased($user->id, $dollar);
         }


        return $this->transactionLogDao->storeLog($app, $log);
    }

    protected function handleRefund(App $app, NotificationLog $log): RefundLog
    {
        $dollar = $this->getTransactionDollar($log);
        $this->appDao->incrementRefund($app->id, $dollar);

        // Update user's refunded amount (only if user exists)
        $transInfo = $log->getTransactionInfo();
        $appAccountToken = $transInfo?->appAccountToken;

        if (!empty($appAccountToken)) {
            $this->appleUserDao->incrementRefundedByToken($appAccountToken, $app->id, $dollar);
        }

        $this->consumptionLogDao->updateStatus($transInfo->originalTransactionId, ConsumptionLogStatusEnum::REFUND);

        return $this->refundLogDao->storeLog($app, $log);
    }

    protected function handleTest(App $app, NotificationLog $log): void
    {
        $app->status = AppStatusEnum::NORMAL;
        $app->save();
    }

    /**
     * @throws BundleIdMismatchException
     */
    protected function insertRawLog($content, App $app, ResponseBodyV2 $payload): NotificationLog
    {
        $raw = $this->rawLogDao->storeRawLog($content, $app, $payload);
        if ($raw->status === NotificationLogStatusEnum::UN_MATCH_BUNDLE) {
            throw new BundleIdMismatchException(
                $app->bundle_id ?? 'unknown',
                $raw->bundle_id ?? 'unknown'
            );
        }

        return $raw;
    }


    protected function getTransactionDollar(NotificationLog $log): float
    {
        $transaction = $log->getTransactionInfo();
        // Use new safe method while maintaining backward compatibility
        return $this->priceService->toDollarFloat(
            $transaction?->currency ?? 'USD',
            $transaction?->price ?? 0
        );
    }

    protected function handleRefundDeclined(App $app, NotificationLog $log): void
    {
        $originalTransactionId = $log->getTransactionInfo()?->originalTransactionId;
        $this->consumptionLogDao->updateStatus($originalTransactionId, ConsumptionLogStatusEnum::REFUND_DECLINED);
    }
}
