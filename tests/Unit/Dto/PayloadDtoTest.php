<?php

namespace Tests\Unit\Dto;

use App\Dto\PayloadDto;
use App\Enums\EnvironmentEnum;
use App\Enums\NotificationTypeEnum;
use Tests\TestCase;

class PayloadDtoTest extends TestCase
{
    public function test_from_raw_payload_with_apple_format(): void
    {
        $payload = [
            'notificationType' => 'REFUND',
            'notificationUUID' => 'uuid-123',
            'data' => [
                'bundleId' => 'com.example.app',
                'environment' => 'Sandbox',
                'signedTransactionInfo' => [
                    'decoded' => [
                        'originalTransactionId' => 'orig-123',
                        'transactionId' => 'trans-456',
                    ]
                ]
            ]
        ];

        $dto = PayloadDto::fromRawPayload($payload);

        $this->assertEquals(NotificationTypeEnum::REFUND, $dto->notificationType);
        $this->assertEquals('uuid-123', $dto->notificationUuid);
        $this->assertEquals('com.example.app', $dto->bundleId);
        $this->assertEquals(EnvironmentEnum::SANDBOX, $dto->environment);
        $this->assertNotNull($dto->transactionInfo);
        $this->assertEquals('orig-123', $dto->transactionInfo->originalTransactionId);
    }

    public function test_from_raw_payload_with_php_object_format(): void
    {
        $payload = [
            'notificationType' => 'TEST',
            'notificationUUID' => 'uuid-456',
            'appMetadata' => [
                'bundleId' => 'com.test.app',
                'environment' => 'Production',
                'transactionInfo' => [
                    'originalTransactionId' => 'orig-789',
                    'transactionId' => 'trans-012',
                ]
            ]
        ];

        $dto = PayloadDto::fromRawPayload($payload);

        $this->assertEquals(NotificationTypeEnum::TEST, $dto->notificationType);
        $this->assertEquals('uuid-456', $dto->notificationUuid);
        $this->assertEquals('com.test.app', $dto->bundleId);
        $this->assertEquals(EnvironmentEnum::PRODUCTION, $dto->environment);
        $this->assertNotNull($dto->transactionInfo);
    }

    public function test_from_raw_payload_normalizes_local_sandbox_to_sandbox(): void
    {
        $payload = [
            'notificationType' => 'TEST',
            'notificationUUID' => 'uuid-789',
            'appMetadata' => [
                'bundleId' => 'com.demo.app',
                'environment' => 'LocalSandbox',
            ]
        ];

        $dto = PayloadDto::fromRawPayload($payload);

        $this->assertEquals(EnvironmentEnum::SANDBOX, $dto->environment);
    }

    public function test_from_raw_payload_defaults_to_sandbox_when_empty_environment(): void
    {
        $payload = [
            'notificationType' => 'TEST',
            'notificationUUID' => 'uuid-999',
            'data' => [
                'bundleId' => 'com.app.test',
            ]
        ];

        $dto = PayloadDto::fromRawPayload($payload);

        $this->assertEquals(EnvironmentEnum::SANDBOX, $dto->environment);
    }

    public function test_from_raw_payload_with_consumption_request_reason(): void
    {
        $payload = [
            'notificationType' => 'CONSUMPTION_REQUEST',
            'notificationUUID' => 'uuid-consumption',
            'data' => [
                'bundleId' => 'com.app.consume',
                'environment' => 'Sandbox',
                'consumptionRequestReason' => 'UNINTENDED_PURCHASE',
            ]
        ];

        $dto = PayloadDto::fromRawPayload($payload);

        $this->assertEquals('UNINTENDED_PURCHASE', $dto->consumptionRequestReason);
    }

    public function test_from_raw_payload_without_transaction_info(): void
    {
        $payload = [
            'notificationType' => 'TEST',
            'notificationUUID' => 'uuid-no-trans',
            'data' => [
                'bundleId' => 'com.app.test',
                'environment' => 'Sandbox',
            ]
        ];

        $dto = PayloadDto::fromRawPayload($payload);

        $this->assertNull($dto->transactionInfo);
    }

    public function test_from_raw_payload_with_unknown_notification_type(): void
    {
        $payload = [
            'notificationType' => 'UNKNOWN_TYPE_XYZ',
            'notificationUUID' => 'uuid-unknown',
            'data' => [
                'bundleId' => 'com.app.test',
                'environment' => 'Sandbox',
            ]
        ];

        $dto = PayloadDto::fromRawPayload($payload);

        // Should fallback to TEST when unknown type
        $this->assertEquals(NotificationTypeEnum::TEST, $dto->notificationType);
    }

    public function test_from_raw_payload_with_empty_values(): void
    {
        $payload = [];

        $dto = PayloadDto::fromRawPayload($payload);

        $this->assertEquals('', $dto->notificationUuid);
        $this->assertEquals('', $dto->bundleId);
        $this->assertEquals(EnvironmentEnum::SANDBOX, $dto->environment);
        $this->assertNull($dto->transactionInfo);
        $this->assertNull($dto->consumptionRequestReason);
    }
}
