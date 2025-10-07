<?php

namespace App\Services;

use App\Models\App;
use Readdle\AppStoreServerAPI\AppStoreServerAPI;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerAPIException;
use Readdle\AppStoreServerAPI\Exception\AppStoreServerNotificationException;
use Readdle\AppStoreServerAPI\Exception\WrongEnvironmentException;
use Readdle\AppStoreServerAPI\ResponseBodyV2;

class IapService
{

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
        // NOTE: 2035 is not valid, but it is the latest certificate from Apple
        return <<<ROOTPEM
-----BEGIN CERTIFICATE-----
MIICQzCCAcmgAwIBAgIILcX8iNLFS5UwCgYIKoZIzj0EAwMwZzEbMBkGA1UEAwwS
QXBwbGUgUm9vdCBDQSAtIEczMSYwJAYDVQQLDB1BcHBsZSBDZXJ0aWZpY2F0aW9u
IEF1dGhvcml0eTETMBEGA1UECgwKQXBwbGUgSW5jLjELMAkGA1UEBhMCVVMwHhcN
MTQwNDMwMTgxOTA2WhcNMzkwNDMwMTgxOTA2WjBnMRswGQYDVQQDDBJBcHBsZSBS
b290IENBIC0gRzMxJjAkBgNVBAsMHUFwcGxlIENlcnRpZmljYXRpb24gQXV0aG9y
aXR5MRMwEQYDVQQKDApBcHBsZSBJbmMuMQswCQYDVQQGEwJVUzB2MBAGByqGSM49
AgEGBSuBBAAiA2IABJjpLz1AcqTtkyJygRMc3RCV8cWjTnHcFBbZDuWmBSp3ZHtf
TjjTuxxEtX/1H7YyYl3J6YRbTzBPEVoA/VhYDKX1DyxNB0cTddqXl5dvMVztK517
IDvYuVTZXpmkOlEKMaNCMEAwHQYDVR0OBBYEFLuw3qFYM4iapIqZ3r6966/ayySr
MA8GA1UdEwEB/wQFMAMBAf8wDgYDVR0PAQH/BAQDAgEGMAoGCCqGSM49BAMDA2gA
MGUCMQCD6cHEFl4aXTQY2e3v9GwOAEZLuN+yRhHFD/3meoyhpmvOwgPUnPWTxnS4
at+qIxUCMG1mihDK1A3UT82NQz60imOlM27jbdoXt2QfyFMm+YhidDkLF1vLUagM
6BgD56KyKA==
-----END CERTIFICATE-----
ROOTPEM;
    }
}
