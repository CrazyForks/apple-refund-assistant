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
        $raw = new NotificationRawLog();
        $raw->notification_uuid = $payload->getNotificationUUID();
        $raw->app_id = $app->id;
        $raw->notification_type = NotificationTypeEnum::tryFrom($payload->getNotificationType());
        $raw->subtype = $payload->getSubtype();
        $raw->environment = $payload->getAppMetadata()->getEnvironment();
        $raw->bundle_id = $payload->getAppMetadata()->getBundleId();
        $raw->request_body = $content;
        $raw->payload = json_encode($payload);
        $raw->save();

        return $raw;
    }
}
