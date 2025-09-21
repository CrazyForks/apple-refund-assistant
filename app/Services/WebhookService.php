<?php

namespace App\Services;

use App\Dao\RefundLogDao;
use App\Dao\TransactionLogDao;
use App\Enums\AppStatusEnum;
use App\Enums\NotificationTypeEnum;
use App\Models\App;
use App\Models\NotificationRawLog;
use App\Dao\AppDao;
use App\Dao\NotificationRawLogDao;
use App\Models\RefundLog;
use App\Models\TransactionLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Readdle\AppStoreServerAPI\Environment;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerNotificationException;
use Readdle\AppStoreServerAPI\Exception\WrongEnvironmentException;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class WebhookService
{
    protected AppDao $appRepository;
    protected NotificationRawLogDao $rawLogRepository;
    protected RefundLogDao $refundLogDao;
    protected TransactionLogDao $transactionLogDao;
    protected IapService $iapService;

    public function __construct(
        AppDao                $appRepository,
        NotificationRawLogDao $rawLogRepository,
        RefundLogDao          $refundLogDao,
        TransactionLogDao     $transactionLogDao,
        IapService            $iapService,
    )
    {
        $this->appRepository = $appRepository;
        $this->rawLogRepository = $rawLogRepository;
        $this->transactionLogDao = $transactionLogDao;
        $this->refundLogDao = $refundLogDao;
        $this->iapService = $iapService;
    }


    /**
     * @throws AppStoreServerNotificationException
     * @throws \Exception
     */
    public function handleNotification(string $content, int $appId): string
    {
        $payload = $this->iapService->decodePayload($content);

        // 1. raw logs
        $app = App::query()->findOrFail($appId);
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
            default:
                Log::info("[{$payload->getNotificationUUID()}]{$payload->getNotificationType()}");
                break;
        }

        return 'SUCCESS';
    }

    /**
     * @throws \Exception
     */
    protected function handleTransaction(App $app, ResponseBodyV2 $payload): TransactionLog
    {
        // TODO increment data to apps table
        return $this->transactionLogDao->storeLog($app, $payload);
    }

    /**
     * @throws \Exception
     */
    protected function handleRefund(App $app, ResponseBodyV2 $payload): RefundLog
    {
        // TODO increment data to apps table
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

        return $this->rawLogRepository->storeRawLog($content, $app, $payload);
    }
}
