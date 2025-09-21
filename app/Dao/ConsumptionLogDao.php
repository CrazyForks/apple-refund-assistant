<?php

namespace App\Dao;

use App\Enums\ConsumptionLogStatusEnum;
use App\Models\App;
use App\Models\ConsumptionLog;
use App\Models\NotificationRawLog;
use App\Models\RefundLog;
use Carbon\Carbon;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class ConsumptionLogDao extends PayloadAttribute
{
    /**
     * @throws \Exception
     */
    public function storeLog(App $app, ResponseBodyV2 $payload): ConsumptionLog
    {
        $transInfo = $payload->getAppMetadata()->getTransactionInfo();
        if (is_null($transInfo)) {
            throw new \Exception('invalid transaction info');
        }


        $model = new ConsumptionLog();
        $model->app_id = $app->getKey();
        $model->bundle_id = $payload->getAppMetadata()->getBundleId();
        $model->environment = $payload->getAppMetadata()->getEnvironment();
        $model->notification_uuid = $payload->getNotificationUUID();

        $model->original_transaction_id = $transInfo->getOriginalTransactionId();
        $model->transaction_id = $transInfo->getTransactionId();
        $model->app_account_token = $transInfo->getAppAccountToken();

        $model->consumption_request_reason = $payload->getAppMetadata()->getConsumptionRequestReason();
        $model->deadline_at = Carbon::now()->addHours(12)->unix();
        $model->status = ConsumptionLogStatusEnum::PENDING;
        $model->save();

        return $model;
    }
}
