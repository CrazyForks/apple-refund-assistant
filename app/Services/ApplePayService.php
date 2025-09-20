<?php

namespace App\Services;

use App\Models\App;
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
}
