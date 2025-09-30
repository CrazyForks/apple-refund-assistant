<?php

namespace App\Dao;

use App\Models\App;
use App\Models\NotificationRawLog;
use App\Models\RefundLog;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class RefundLogDao extends PayloadAttribute
{
    /**
     * @throws \Exception
     */
    public function storeLog(App $app, ResponseBodyV2 $payload): RefundLog
    {
        $transInfo = $payload->getAppMetadata()->getTransactionInfo();
        if (is_null($transInfo)) {
            throw new \Exception('invalid transaction info');
        }

        $model = new RefundLog();
        $model->app_id = $app->getKey();
        $model->bundle_id = $payload->getAppMetadata()->getBundleId();
        $model->environment = $payload->getAppMetadata()->getEnvironment();
        $model->notification_uuid = $payload->getNotificationUUID();

        $model->original_transaction_id = $transInfo->getOriginalTransactionId();
        $model->transaction_id = $transInfo->getTransactionId();
        $model->purchase_date = $this->fixTs($transInfo->getPurchaseDate());
        $model->price = $this->formatPrice($transInfo->getPrice());
        $model->currency = $transInfo->getCurrency();

        $model->app_account_token = $transInfo->getAppAccountToken();
        $model->refund_date = $transInfo->getRevocationDate() / 1000;
        $model->refund_reason = "code[{$transInfo->getRevocationReason()}]";
        $model->save();

        return $model;
    }
}
