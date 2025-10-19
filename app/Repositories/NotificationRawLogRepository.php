<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\NotificationLogStatusEnum;
use App\Models\App;
use App\Models\NotificationLog;
use App\Models\NotificationRawLog;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class NotificationRawLogRepository
{
    public function storeRawLog(string $content, App $app, ResponseBodyV2 $payload): NotificationLog
    {
        $meta = $payload->getAppMetadata();
        $status = $app->bundle_id === $meta->getBundleId() ?
            NotificationLogStatusEnum::PROCESSING :
            NotificationLogStatusEnum::UN_MATCH_BUNDLE;
        $model = new NotificationLog();
        $model->app_id = $app->getKey();
        $model->bundle_id = $meta->getBundleId();
        $model->bundle_version = $meta->getBundleVersion();
        $model->environment = $meta->getEnvironment();
        $model->notification_type = $payload->getNotificationType();
        $model->notification_uuid = $payload->getNotificationUUID();
        $model->payload = json_encode($payload);
        $model->status = $status;
        $model->save();

        // Store large request_body in separate table
        $raw = new NotificationRawLog();
        $raw->id = $model->getKey();
        $raw->request_body = $content;
        $raw->save();

        return $model;
    }
}

