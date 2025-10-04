<?php

namespace Tests\Unit\Dto;

use App\Dto\PayloadDto;
use App\Dto\TransactionInfoDto;
use App\Enums\EnvironmentEnum;
use App\Enums\NotificationTypeEnum;
use PHPUnit\Framework\TestCase;

class PayloadDtoTest extends TestCase
{
    public function test_can_create_payload_dto_from_raw_data(): void
    {
        $rawPayload = [
            'notificationType' => 'REFUND',
            'notificationUUID' => '12345-abcde',
            'data' => [
                'bundleId' => 'com.example.app',
                'environment' => 'Production',
                'signedTransactionInfo' => [
                    'decoded' => [
                        'originalTransactionId' => 'original_123',
                        'transactionId' => 'trans_456',
                        'purchaseDate' => 1609459200000,
                        'price' => 999,
                        'currency' => 'USD',
                        'appAccountToken' => 'user_token_789',
                        'productId' => 'product_123',
                        'type' => 'Auto-Renewable Subscription',
                        'quantity' => 1,
                    ]
                ]
            ]
        ];

        $dto = PayloadDto::fromRawPayload($rawPayload);

        $this->assertSame(NotificationTypeEnum::REFUND, $dto->notificationType);
        $this->assertSame('12345-abcde', $dto->notificationUuid);
        $this->assertSame('com.example.app', $dto->bundleId);
        $this->assertSame(EnvironmentEnum::PRODUCTION, $dto->environment);
        
        $this->assertInstanceOf(TransactionInfoDto::class, $dto->transactionInfo);
        $this->assertSame('original_123', $dto->transactionInfo->originalTransactionId);
        $this->assertSame('trans_456', $dto->transactionInfo->transactionId);
        $this->assertSame(999, $dto->transactionInfo->price);
        $this->assertSame('USD', $dto->transactionInfo->currency);
        $this->assertSame('user_token_789', $dto->transactionInfo->appAccountToken);
    }

    public function test_transaction_info_dto_is_readonly(): void
    {
        $dto = new TransactionInfoDto(
            originalTransactionId: 'test_123',
            transactionId: 'trans_456',
            price: 999,
            currency: 'USD'
        );

        $this->assertSame('test_123', $dto->originalTransactionId);
        $this->assertSame('trans_456', $dto->transactionId);
        $this->assertSame(999, $dto->price);
        $this->assertSame('USD', $dto->currency);
    }
}

