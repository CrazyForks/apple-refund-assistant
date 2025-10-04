<?php

namespace App\Dao;

use App\Models\App;
use App\Models\NotificationRawLog;
use App\Models\RefundLog;

class RefundLogDao
{
    /**
     * @throws \Exception
     */
    public function storeLog(App $app, NotificationRawLog $raw): RefundLog
    {
        $transInfo = $raw->getTransactionInfo();
        if (is_null($transInfo)) {
            throw new \Exception('invalid transaction info');
        }

        $model = new RefundLog();
        $model->app_id = $app->getKey();
        $model->bundle_id = $raw->bundle_id;
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
