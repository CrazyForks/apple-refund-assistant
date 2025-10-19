<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ConsumptionLogStatusEnum;
use App\Exceptions\InvalidTransactionInfoException;
use App\Models\App;
use App\Models\ConsumptionLog;
use App\Models\NotificationLog;
use Carbon\Carbon;

class ConsumptionLogRepository
{
    /**
     * @throws InvalidTransactionInfoException
     */
    public function storeLog(App $app, NotificationLog $raw): ConsumptionLog
    {
        $transInfo = $raw->getTransactionInfo();
        if (is_null($transInfo)) {
            throw new InvalidTransactionInfoException('Transaction info is null or missing');
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

    /**
     * Update the status of a ConsumptionLog
     *
     * @param $originalId
     * @param ConsumptionLogStatusEnum $status
     * @param string|null $statusMsg
     * @return void
     */
    public function updateStatus($originalId, ConsumptionLogStatusEnum $status, ?string $statusMsg = null): void
    {
        $changes = ['status' => $status];
        if ($statusMsg) {
            $changes['status_msg'] = $statusMsg;
        }

       ConsumptionLog::query()->where('original_transaction_id', $originalId)->update($changes);
    }
}

