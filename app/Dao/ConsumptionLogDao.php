<?php

namespace App\Dao;

use App\Enums\ConsumptionLogStatusEnum;
use App\Models\App;
use App\Models\ConsumptionLog;
use App\Models\NotificationLog;
use App\Models\RefundLog;
use Carbon\Carbon;

class ConsumptionLogDao
{
    /**
     * @throws \Exception
     */
    public function storeLog(App $app, NotificationLog $raw): ConsumptionLog
    {
        $transInfo = $raw->getTransactionInfo();
        if (is_null($transInfo)) {
            throw new \Exception('invalid transaction info');
        }


        $model = new ConsumptionLog();
        $model->id = $raw->id;
        $model->app_id = $app->id;
        $model->bundle_id = $raw->bundle_id;
        $model->bundle_version = $raw->bundle_version;
        $model->environment = $raw->environment;
        $model->notification_uuid = $raw->notification_uuid;

        $model->original_transaction_id = $transInfo->originalTransactionId;
        $model->transaction_id = $transInfo->transactionId;
        $model->app_account_token = $transInfo->appAccountToken;

        $model->consumption_request_reason = $raw->getConsumptionRequestReason();
        $model->deadline_at = Carbon::now()->addHours(12)->unix();
        $model->status = ConsumptionLogStatusEnum::PENDING;
        $model->save();

        return $model;
    }
}
