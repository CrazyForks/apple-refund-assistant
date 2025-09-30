<?php

declare(strict_types=1);

namespace Tests\Support;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Ramsey\Uuid\Uuid;
use Readdle\AppStoreServerAPI\AppMetadata;
use Readdle\AppStoreServerAPI\ResponseBodyV2;
use ReflectionClass;
use RuntimeException;



class AppleSignedPayload
{

    /**
     * 根据数组构建 ResponseBodyV2 实例（测试用）
     *
     * @param array $data
     *   示例数组：
     *   [
     *       'notificationType' => 'TEST',
     *       'subtype' => null,
     *       'notificationUUID' => 'uuid-123',
     *       'version' => '2.0',
     *       'signedDate' => 1690000000000,
     *       // AppMetadata 需要的字段（至少包含 bundleId 等）
     *       'bundleId' => 'com.example.test',
     *       'bundleVersion' => '1.0.0',
     *       // 其它 AppMetadata 字段
     *   ]
     *
     * @return ResponseBodyV2
     */
    public static function buildResponseBodyV2FromArray($event, array $meta): ResponseBodyV2
    {
        $ref = new ReflectionClass(ResponseBodyV2::class);
        $instance = $ref->newInstanceWithoutConstructor();

        // 构造 AppMetadata 对象
        if (!class_exists(AppMetadata::class)) {
            throw new RuntimeException('AppMetadata class not found');
        }
        if (isset($meta['transactionInfo']) && is_array($meta['transactionInfo'])) {
            $meta['transactionInfo'] = \Readdle\AppStoreServerAPI\TransactionInfo::createFromRawTransactionInfo($meta['transactionInfo']);
        }
        if (isset($meta['renewalInfo']) && is_array($meta['renewalInfo'])) {
            if (class_exists('Readdle\\AppStoreServerAPI\\RenewalInfo')) {
                $meta['renewalInfo'] = \Readdle\AppStoreServerAPI\RenewalInfo::createFromRawRenewalInfo($meta['renewalInfo']);
            }
        }

        $appMetadata = AppMetadata::createFromRawData($meta);

        // ResponseBodyV2 的私有属性列表
        $propsToSet = [
            'appMetadata' => $appMetadata,
            'notificationType' => $event,
            'subtype' => null,
            'notificationUUID' => Uuid::uuid4()->toString(),
            'version' => '2.0',
            'signedDate' => microtime(true),
        ];

        // 赋值私有属性
        foreach ($propsToSet as $prop => $value) {
            if (! $ref->hasProperty($prop)) {
                throw new RuntimeException("ResponseBodyV2 does not have property '{$prop}'");
            }
            $propRef = $ref->getProperty($prop);
            $propRef->setAccessible(true);
            $propRef->setValue($instance, $value);
        }

        return $instance;
    }
}
