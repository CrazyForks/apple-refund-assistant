<?php

namespace App\Dao;

use App\Models\App;
use App\Models\NotificationRawLog;
use App\Models\RefundLog;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class RefundLogDao
{
    /**
     * @throws \Exception
     */
    public function storeLog(App $app, ResponseBodyV2 $payload): RefundLog
    {
        $transInfo = $payload->getAppMetadata()->getTransactionInfo();
        if (is_null($transInfo)) {
            throw new \Exception('invalid transaction inf');
        }

        $log = new RefundLog();

        $log->app_id = $app->id;
        $log->bundle_id = $transInfo->getBundleId();
        $log->purchase_at = $transInfo->getPurchaseDate() / 1000;

        $log->notification_uuid = $payload->getNotificationUUID();
        $log->original_transaction_id = $transInfo->getOriginalTransactionId();
        $log->transaction_id = $transInfo->getTransactionId();
        $log->currency = $transInfo->getCurrency();
        $log->amount = ($transInfo->getPrice() ?? 0) / 1000;
        $log->refund_at = $transInfo->getRevocationDate() / 1000;
        $log->refund_reason = "code[{$transInfo->getRevocationReason()}]";
        $log->environment = $transInfo->getEnvironment();
        $log->save();

        return $log;
    }
}
