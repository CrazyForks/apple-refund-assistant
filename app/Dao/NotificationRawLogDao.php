<?php

namespace App\Dao;

use App\Enums\NotificationTypeEnum;
use App\Models\App;
use App\Models\NotificationRawLog;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class NotificationRawLogDao
{
    public function storeRawLog(string $content, App $app, ResponseBodyV2 $payload): NotificationRawLog
    {
        $model = new NotificationRawLog();
        $model->app_id = $app->getKey();
        $model->bundle_id = $payload->getAppMetadata()->getBundleId();
        $model->environment = $payload->getAppMetadata()->getEnvironment();
        $model->notification_type = $payload->getNotificationType();
        $model->notification_uuid = $payload->getNotificationUUID();

        $model->request_body = $content;
        $model->payload = json_encode($payload);
        $model->save();

        return $model;
    }
}
