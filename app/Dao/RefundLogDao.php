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
        $this->setPayloadFields($model, $app, $payload);
        $this->setTransactionFields($model, $transInfo);
        $model->refund_date = $transInfo->getRevocationDate() / 1000;
        $model->refund_reason = "code[{$transInfo->getRevocationReason()}]";
        $model->save();

        return $model;
    }
}
