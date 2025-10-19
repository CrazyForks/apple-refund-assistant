<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\InvalidTransactionInfoException;
use App\Models\App;
use App\Models\NotificationLog;
use App\Models\RefundLog;

class RefundLogRepository
{
    /**
     * @throws InvalidTransactionInfoException
     */
    public function storeLog(App $app, NotificationLog $raw): RefundLog
    {
        $transInfo = $raw->getTransactionInfo();
        if (is_null($transInfo)) {
            throw new InvalidTransactionInfoException('Transaction info is null or missing');
        }

        $model = new RefundLog();
        $model->id = $raw->id;
        $model->app_id = $app->id;
        $model->bundle_id = $raw->bundle_id;
        $model->bundle_version = $raw->bundle_version;
        $model->environment = $raw->environment;
        $model->notification_uuid = $raw->notification_uuid;

        $model->original_transaction_id = $transInfo->originalTransactionId;
        $model->transaction_id = $transInfo->transactionId;
        $model->purchase_date = $transInfo->getPurchaseDateTimestamp();
        $model->price = $transInfo->getFormattedPrice();
        $model->currency = $transInfo->currency;

        $model->app_account_token = $transInfo->appAccountToken;
        $model->refund_date = $transInfo->getRevocationDateTimestamp();
        $model->refund_reason = $transInfo->getRefundReason();
        $model->save();

        return $model;
    }
}

