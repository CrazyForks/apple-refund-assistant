<?php

namespace App\Http\Controllers\Api;

use App\Enums\AppStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\NotificationRawLog;
use App\Services\ApplePayService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class WebhookController extends Controller
{
    //
    public function store(Request $request, int $id)
    {
        try {
            $payload = ResponseBodyV2::createFromRawNotification(
                $request->getContent(),
                ApplePayService::rootCertificate(),
            );
        } catch (\Exception $e) {
            $msg = 'Server notification could not be processed: ' . $e->getMessage();
            Log::error($msg);
            return response($msg, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // 1. raw logs
        $raw = $this->insertRawLog($request, $id, $payload);
        if ($resp = $this->handleTestNotification($request, $id, $payload)) {
            return $resp;
        }


        // TODO other events
        return response('SUCCESS');
    }

    protected function handleTestNotification(Request $request, int $appId, ResponseBodyV2 $payload) : ?Response
    {
        if ($payload->getNotificationType() !== ResponseBodyV2::NOTIFICATION_TYPE__TEST) {
            return null;
        }

        App::query()
            ->where('id', $appId)
            ->update([
                'status' => AppStatusEnum::NORMAL,
            ]);

        return response('SUCCESS');
    }

    protected function insertRawLog(Request $request, int $appId, ResponseBodyV2 $payload)
    {
        $raw = NotificationRawLog::query()
            ->firstOrNew([
                'notification_uuid' => $payload->getNotificationUUID(),
                'app_id' => $appId,
            ]);
        $raw->notification_type = $payload->getNotificationType();
        $raw->subtype = $payload->getSubtype();
        $raw->environment = $payload->getAppMetadata()->getEnvironment();
        $raw->bundle_id = $payload->getAppMetadata()->getBundleId();
        $raw->request_body = $request->getContent();
        $raw->payload = json_encode($payload);
        $raw->save();
        return $raw;
    }

}
