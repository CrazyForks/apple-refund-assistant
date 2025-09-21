<?php

namespace App\Services;

use App\Models\App;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Readdle\AppStoreServerAPI\AppStoreServerAPI;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerNotificationException;
use Readdle\AppStoreServerAPI\Exception\WrongEnvironmentException;
use Readdle\AppStoreServerAPI\ResponseBodyV2;
use Readdle\AppStoreServerAPI\Util\Helper;

class IapService
{
    /**
     * @throws WrongEnvironmentException
     */
    public static function makeApi(App $app, $env)
    {
        return new AppStoreServerAPI(
            $env,
            $app->issuer_id ?? '',
            $app->bundle_id ?? '',
            $app->key_id ?? '',
            $app->p8_key ?? '',
        );
    }


    /**
     * @throws AppStoreServerNotificationException
     */
    public function decodePayload($body): ResponseBodyV2
    {
        return ResponseBodyV2::createFromRawNotification(
            $body,
            $this->rootCertificate(),
        );
    }

    protected function rootCertificate()
    {
        return Cache::remember(
            'apple_root_certificate',
            Carbon::now()->addDay(),
            function () {
                return Helper::toPEM(file_get_contents('https://www.apple.com/certificateauthority/AppleRootCA-G3.cer'));
            }
        );
    }
}
