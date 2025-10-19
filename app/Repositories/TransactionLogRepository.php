<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Exceptions\InvalidTransactionInfoException;
use App\Models\App;
use App\Models\ConsumptionLog;
use App\Models\NotificationLog;
use App\Models\TransactionLog;

class TransactionLogRepository
{
    /**
     * @throws InvalidTransactionInfoException
     */
    public function storeLog(App $app, NotificationLog $raw): TransactionLog
    {
        $transInfo = $raw->getTransactionInfo();
        if (is_null($transInfo)) {
            throw new InvalidTransactionInfoException('Transaction info is null or missing');
        }

        $model = new TransactionLog();
        $model->id = $raw->id;
        $model->notification_type = $raw->notification_type;
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
        $model->product_id = $transInfo->productId;
        $model->product_type = $transInfo->type;
        $model->original_purchase_date = $transInfo->getOriginalPurchaseDateTimestamp();
        $model->expiration_date = $transInfo->getExpiresDateTimestamp();
        $model->in_app_ownership_type = $transInfo->inAppOwnershipType;
        $model->quantity = $transInfo->quantity ?? 0;
        $model->save();

        return $model;
    }

    public function findTransactionByConsumption(ConsumptionLog $log): ?TransactionLog
    {
        return TransactionLog::query()
            ->where('original_transaction_id', $log->original_transaction_id)
            ->where('app_id', $log->app_id)
            ->orderByDesc('id')
            ->first();
    }
}

