<?php

namespace App\Services;

use App\Models\App;
use Carbon\Carbon;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;
use Psr\SimpleCache\CacheInterface;
use Readdle\AppStoreServerAPI\AppStoreServerAPI;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerAPIException;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerNotificationException;
use Readdle\AppStoreServerAPI\Exception\WrongEnvironmentException;
use Readdle\AppStoreServerAPI\ResponseBodyV2;
use Readdle\AppStoreServerAPI\Util\Helper;

class IapService
{
    protected Repository $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @throws WrongEnvironmentException
     */
    protected static function api(?string $issuerId, ?string $bundleId, ?string $keyId, ?string $p8Key, $env)
    {
        return new AppStoreServerAPI(
            $env,
            $issuerId ?? '',
            $bundleId ?? '',
            $keyId ?? '',
            $p8Key ?? '',
        );
    }

    /**
     * @throws AppStoreServerAPIException
     */
    public function requestNotification($issuerId, $bundleId, $keyId, $p8Key, $env): \Readdle\AppStoreServerAPI\Response\SendTestNotificationResponse
    {
        return self::api($issuerId, $bundleId, $keyId, $p8Key, $env)->requestTestNotification();
    }

    /**
     * Send consumption information to Apple
     * 
     * @throws AppStoreServerAPIException
     */
    public function sendConsumptionInformation(App $app, string $transactionId, array $requestBody, string $environment): void
    {
        $api = self::api(
            $app->issuer_id,
            $app->bundle_id,
            $app->key_id,
            $app->p8_key,
            $environment
        );
        
        $api->sendConsumptionInformation($transactionId, $requestBody);
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
        return $this->cache->remember(
            'apple_root_certificate',
            Carbon::now()->addDay(),
            function () {
                return Helper::toPEM(file_get_contents('https://www.apple.com/certificateauthority/AppleRootCA-G3.cer'));
            }
        );
    }
}
