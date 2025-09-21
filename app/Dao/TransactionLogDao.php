<?php

namespace App\Dao;

use App\Models\App;
use App\Models\TransactionLog;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class TransactionLogDao extends PayloadAttribute
{
    /**
     * @throws \Exception
     */
    public function storeLog(App $app, ResponseBodyV2 $payload): TransactionLog
    {
        $transInfo = $payload->getAppMetadata()->getTransactionInfo();
        if (is_null($transInfo)) {
            throw new \Exception('invalid transaction info');
        }

        $model = new TransactionLog();
        $model->notification_type = $payload->getNotificationType();
        $model->app_id = $app->getKey();
        $model->bundle_id = $payload->getAppMetadata()->getBundleId();
        $model->environment = $payload->getAppMetadata()->getEnvironment();
        $model->notification_type = $payload->getNotificationType();
        $model->notification_uuid = $payload->getNotificationUUID();

        $model->original_transaction_id = $transInfo->getOriginalTransactionId();
        $model->transaction_id = $transInfo->getTransactionId();
        $model->purchase_date = $this->fixTs($transInfo->getPurchaseDate());
        $model->price = $this->formatPrice($transInfo->getPrice());
        $model->currency = $transInfo->getCurrency();

        $model->app_account_token = $transInfo->getAppAccountToken();
        $model->product_id = $transInfo->getProductId();
        $model->product_type = $transInfo->getType();
        $model->original_purchase_date = ($transInfo->getOriginalPurchaseDate() ?? 0) / 1000;
        $model->expiration_date = ($transInfo->getExpiresDate() ?? 0) / 1000;
        $model->in_app_ownership_type = $transInfo->getInAppOwnershipType();
        $model->quantity = $transInfo->getQuantity();
        $model->save();

        return $model;
    }
}
