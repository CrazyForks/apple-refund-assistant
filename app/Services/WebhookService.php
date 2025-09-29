<?php

namespace App\Services;

use App\Dao\AppDao;
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
    protected IapService $iapService;
    protected AmountPriceService $priceService;

    public function __construct(
        AppDao                $appDao,
        NotificationRawLogDao $rawLogDao,
        ConsumptionLogDao     $consumptionLogDao,
        RefundLogDao          $refundLogDao,
        TransactionLogDao     $transactionLogDao,
        IapService            $iapService,
        AmountPriceService    $priceService,
    )
    {
        $this->appDao = $appDao;
        $this->rawLogDao = $rawLogDao;
        $this->transactionLogDao = $transactionLogDao;
        $this->consumptionLogDao = $consumptionLogDao;
        $this->refundLogDao = $refundLogDao;
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

        // 1. raw logs
        $app = $this->appDao->find($appId);
        $raw = $this->insertRawLog($content, $app, $payload);

        // TODO handle repeat message
        switch (NotificationTypeEnum::tryFrom($payload->getNotificationType())) {
            case NotificationTypeEnum::TEST:
                $this->handleTest($app, $payload);
                break;
            case NotificationTypeEnum::REFUND:
                $this->handleRefund($app, $payload);
                break;
            case NotificationTypeEnum::SUBSCRIBED:
            case NotificationTypeEnum::DID_RENEW:
            case NotificationTypeEnum::OFFER_REDEEMED:
            case NotificationTypeEnum::ONE_TIME_CHARGE:
                $this->handleTransaction($app, $payload);
                break;
            case NotificationTypeEnum::CONSUMPTION_REQUEST:
                $this->handleConsumption($app, $payload);
                break;
            default:
                Log::info("[{$payload->getNotificationUUID()}]{$payload->getNotificationType()}");
                break;
        }

        dispatch(new SendRequestToAppNotificationUrlJob($raw, $app));

        return $raw;
    }

    /**
     * @throws \Exception
     */
    protected function handleConsumption(App $app, ResponseBodyV2 $payload): ConsumptionLog
    {
        $dollar = $this->getTransactionDollar($payload);
        $this->appDao->incrementConsumption($app->id, $dollar);

        $log = $this->consumptionLogDao->storeLog($app, $payload);

        SendConsumptionInformationJob::dispatch($log);

        return $log;
    }


    /**
     * @throws \Exception
     */
    protected function handleTransaction(App $app, ResponseBodyV2 $payload): TransactionLog
    {
        $dollar = $this->getTransactionDollar($payload);
        $this->appDao->incrementTransaction($app->id, $dollar);


        return $this->transactionLogDao->storeLog($app, $payload);
    }

    /**
     * @throws \Exception
     */
    protected function handleRefund(App $app, ResponseBodyV2 $payload): RefundLog
    {
        $dollar = $this->getTransactionDollar($payload);
        $this->appDao->incrementRefund($app->id, $dollar);

        return $this->refundLogDao->storeLog($app, $payload);
    }

    protected function handleTest(App $app, ResponseBodyV2 $payload): void
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


    protected function getTransactionDollar(ResponseBodyV2 $payload): float
    {
        $transaction = $payload->getAppMetadata()->getTransactionInfo();
        return $this->priceService->toDollar($transaction->getCurrency(), $transaction->getPrice());
    }
}
