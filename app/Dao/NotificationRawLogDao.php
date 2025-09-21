<?php

namespace App\Dao;

use App\Enums\NotificationTypeEnum;
use App\Models\App;
use App\Models\NotificationRawLog;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class NotificationRawLogDao extends PayloadAttribute
{
    public function storeRawLog(string $content, App $app, ResponseBodyV2 $payload): NotificationRawLog
    {
        $raw = new NotificationRawLog();
        $this->setPayloadFields($raw, $app, $payload);
        $raw->request_body = $content;
        $raw->payload = json_encode($payload);
        $raw->save();

        return $raw;
    }
}
