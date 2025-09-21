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
        $this->setPayloadFields($model, $app, $payload);
        $this->setTransactionFields($model, $transInfo);

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
