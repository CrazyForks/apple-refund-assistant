<?php

namespace App\Services;

use App\Dao\RefundLogDao;
use App\Enums\AppStatusEnum;
use App\Models\App;
use App\Models\NotificationRawLog;
use App\Dao\AppDao;
use App\Dao\NotificationRawLogDao;
use App\Models\RefundLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Readdle\AppStoreServerAPI\Environment;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerNotificationException;
use Readdle\AppStoreServerAPI\Exception\WrongEnvironmentException;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class WebhookService
{
    protected AppDao $appRepository;
    protected NotificationRawLogDao $rawLogRepository;
    protected RefundLogDao $refundLogDao;
    protected IapService $iapService;

    public function __construct(
        AppDao                $appRepository,
        NotificationRawLogDao $rawLogRepository,
        RefundLogDao          $refundLogDao,
        IapService            $iapService,
    )
    {
        $this->appRepository = $appRepository;
        $this->rawLogRepository = $rawLogRepository;
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
        switch ($payload->getNotificationType()) {
            case ResponseBodyV2::NOTIFICATION_TYPE__TEST:
                $this->handleTest($app, $payload);
                break;
            case ResponseBodyV2::NOTIFICATION_TYPE__REFUND:
                $this->handleRefund($app, $payload);
                break;
        }

        return 'SUCCESS';
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
