<?php

namespace App\Services;

use App\Dao\AppDao;
use App\Dao\AppleUserDao;
use App\Dao\ConsumptionLogDao;
use App\Dao\NotificationRawLogDao;
use App\Dao\RefundLogDao;
use App\Dao\TransactionLogDao;
use App\Enums\AppStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Jobs\SendConsumptionInformationJob;
use App\Jobs\SendRequestToAppNotificationUrlJob;
use App\Models\App;
use App\Models\ConsumptionLog;
use App\Models\NotificationRawLog;
use App\Models\RefundLog;
use App\Models\TransactionLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerNotificationException;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class WebhookService
{
    protected AppDao $appDao;
    protected NotificationRawLogDao $rawLogDao;
    protected RefundLogDao $refundLogDao;
    protected ConsumptionLogDao $consumptionLogDao;
    protected TransactionLogDao $transactionLogDao;
    protected AppleUserDao $appleUserDao;
    protected IapService $iapService;
    protected AmountPriceService $priceService;

    public function __construct(
        AppDao                $appDao,
        NotificationRawLogDao $rawLogDao,
        ConsumptionLogDao     $consumptionLogDao,
        RefundLogDao          $refundLogDao,
        TransactionLogDao     $transactionLogDao,
        AppleUserDao          $appleUserDao,
        IapService            $iapService,
        AmountPriceService    $priceService,
    )
    {
        $this->appDao = $appDao;
        $this->rawLogDao = $rawLogDao;
        $this->transactionLogDao = $transactionLogDao;
        $this->consumptionLogDao = $consumptionLogDao;
        $this->refundLogDao = $refundLogDao;
        $this->appleUserDao = $appleUserDao;
        $this->iapService = $iapService;
        $this->priceService = $priceService;
    }


    /**
     * @throws AppStoreServerNotificationException
     * @throws \Exception
     */
    public function handleNotification(string $content, int $appId): Model
    {
        $payload = $this->iapService->decodePayload($content);

        $app = $this->appDao->find($appId);
        $raw = $this->insertRawLog($content, $app, $payload);

        // TODO handle repeat message
        switch ($raw->notification_type) {
            case NotificationTypeEnum::TEST:
                $this->handleTest($app, $raw);
                break;
            case NotificationTypeEnum::REFUND:
                $this->handleRefund($app, $raw);
                break;
            case NotificationTypeEnum::SUBSCRIBED:
            case NotificationTypeEnum::DID_RENEW:
            case NotificationTypeEnum::OFFER_REDEEMED:
            case NotificationTypeEnum::ONE_TIME_CHARGE:
                $this->handleTransaction($app, $raw);
                break;
            case NotificationTypeEnum::CONSUMPTION_REQUEST:
                $this->handleConsumption($app, $raw);
                break;
            default:
                Log::info("[{$raw->notification_uuid}]{$raw->notification_type->value}");
                break;
        }

        // NOTE: use fpm fast-cgi running in background
        dispatch(new SendRequestToAppNotificationUrlJob($raw, $app))->afterResponse();

        return $raw;
    }

    /**
     * @throws \Exception
     */
    protected function handleConsumption(App $app, NotificationRawLog $raw): ConsumptionLog
    {
        $dollar = $this->getTransactionDollar($raw);
        $this->appDao->incrementConsumption($app->id, $dollar);

        $log = $this->consumptionLogDao->storeLog($app, $raw);

        dispatch(new SendConsumptionInformationJob($log))->afterResponse();

        return $log;
    }


    /**
     * @throws \Exception
     */
    protected function handleTransaction(App $app, NotificationRawLog $raw): TransactionLog
    {
        $dollar = $this->getTransactionDollar($raw);
        $this->appDao->incrementTransaction($app->id, $dollar);

         // Create or get user and update purchased amount
         $transInfo = $raw->getTransactionInfo();
         $appAccountToken = $transInfo?->appAccountToken;
         
         if (!empty($appAccountToken)) {
             // Use originalPurchaseDate as registration time (first purchase time)
             $registerTimestamp = $transInfo->getOriginalPurchaseDateTimestamp();
             
             $user = $this->appleUserDao->firstOrCreate($appAccountToken, $app->id, $registerTimestamp);
             $this->appleUserDao->incrementPurchased($user->id, $dollar);
         }


        return $this->transactionLogDao->storeLog($app, $raw);
    }

    /**
     * @throws \Exception
     */
    protected function handleRefund(App $app, NotificationRawLog $raw): RefundLog
    {
        $dollar = $this->getTransactionDollar($raw);
        $this->appDao->incrementRefund($app->id, $dollar);

        // Update user's refunded amount (only if user exists)
        $transInfo = $raw->getTransactionInfo();
        $appAccountToken = $transInfo?->appAccountToken;
        
        if (!empty($appAccountToken)) {
            // Optimized: directly update without SELECT query
            $this->appleUserDao->incrementRefundedByToken($appAccountToken, $app->id, $dollar);
        }

        return $this->refundLogDao->storeLog($app, $raw);
    }

    protected function handleTest(App $app, NotificationRawLog $raw): void
    {
        $app->status = AppStatusEnum::NORMAL;
        $app->save();
    }

    /**
     * @throws \Exception
     */
    protected function insertRawLog($content, App $app, ResponseBodyV2 $payload): NotificationRawLog
    {
        if ($app->bundle_id !== $payload->getAppMetadata()->getBundleId()) {
            throw new \Exception("bundle_id don't match");
        }

        return $this->rawLogDao->storeRawLog($content, $app, $payload);
    }


    protected function getTransactionDollar(NotificationRawLog $raw): float
    {
        $transaction = $raw->getTransactionInfo();
        // Use new safe method while maintaining backward compatibility
        return $this->priceService->toDollarFloat(
            $transaction?->currency, 
            $transaction?->price
        );
    }
}
