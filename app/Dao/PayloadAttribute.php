<?php

namespace App\Dao;

use App\Models\App;
use Illuminate\Database\Eloquent\Model;
use Readdle\AppStoreServerAPI\ResponseBodyV2;
use Readdle\AppStoreServerAPI\TransactionInfo;

abstract class PayloadAttribute
{

    protected function setPayloadFields(Model $model, App $app, ResponseBodyV2 $payload): void
    {
        $meta = $payload->getAppMetadata();
        $model->forceFill([
            'app_id' => $app->getKey(),
            'notification_uuid' => $payload->getNotificationUUID(),
            'notification_type' => $payload->getNotificationType(),
            'subtype' => $payload->getSubtype(),
            'bundle_id' => $meta->getBundleId(),
            'environment' => $meta->getEnvironment(),
        ]);
    }

    protected function setTransactionFields(Model $model, TransactionInfo $transInfo): void
    {
        $model->forceFill([
            'purchase_date' => $transInfo->getPurchaseDate() / 1000,
            'original_transaction_id' => $transInfo->getOriginalTransactionId(),
            'transaction_id' => $transInfo->getTransactionId(),
            'price' => ($transInfo->getPrice() ?? 0) / 1000,
            'currency' => $transInfo->getCurrency(),
        ]);
    }
}
