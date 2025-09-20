<?php

namespace App\Services;

use App\Models\App;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Readdle\AppStoreServerAPI\Exception\WrongEnvironmentException;

class ApplePayService
{
    /**
     * @throws WrongEnvironmentException
     */
    public static function make(App $app, $env)
    {
        return new \Readdle\AppStoreServerAPI\AppStoreServerAPI(
            $env,
            $app->issuer_id ?? '',
            $app->bundle_id ?? '',
            $app->key_id ?? '',
            $app->p8_key ?? '',
        );
    }

    public static function rootCertificate()
    {
        return Cache::remember(
            'apple_root_certificate',
            Carbon::now()->addDay(),
            function () {
                return \Readdle\AppStoreServerAPI\Util\Helper::toPEM(file_get_contents('https://www.apple.com/certificateauthority/AppleRootCA-G3.cer'));
            }
        );
    }
}
