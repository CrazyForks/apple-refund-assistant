<?php

namespace Tests\Unit\Dto;

use App\Dto\TransactionInfoDto;
use Tests\TestCase;

class TransactionInfoDtoTest extends TestCase
{
    public function test_from_array_creates_dto_with_all_fields(): void
    {
        $data = [
            'originalTransactionId' => 'orig-123',
            'transactionId' => 'trans-456',
            'purchaseDate' => 1700000000000,
            'price' => 999,
            'currency' => 'USD',
            'appAccountToken' => 'token-abc',
            'productId' => 'product.monthly',
            'type' => 'Auto-Renewable Subscription',
            'originalPurchaseDate' => 1690000000000,
            'expiresDate' => 1710000000000,
            'inAppOwnershipType' => 'PURCHASED',
            'quantity' => 1,
            'revocationDate' => 1711000000000,
            'revocationReason' => 0,
        ];

        $dto = TransactionInfoDto::fromArray($data);

        $this->assertEquals('orig-123', $dto->originalTransactionId);
        $this->assertEquals('trans-456', $dto->transactionId);
        $this->assertEquals(1700000000000, $dto->purchaseDate);
        $this->assertEquals(999, $dto->price);
        $this->assertEquals('USD', $dto->currency);
        $this->assertEquals('token-abc', $dto->appAccountToken);
        $this->assertEquals('product.monthly', $dto->productId);
        $this->assertEquals('Auto-Renewable Subscription', $dto->type);
        $this->assertEquals(1690000000000, $dto->originalPurchaseDate);
        $this->assertEquals(1710000000000, $dto->expiresDate);
        $this->assertEquals('PURCHASED', $dto->inAppOwnershipType);
        $this->assertEquals(1, $dto->quantity);
        $this->assertEquals(1711000000000, $dto->revocationDate);
        $this->assertEquals(0, $dto->revocationReason);
    }

    public function test_from_array_creates_dto_with_null_values(): void
    {
        $dto = TransactionInfoDto::fromArray([]);

        $this->assertNull($dto->originalTransactionId);
        $this->assertNull($dto->transactionId);
        $this->assertNull($dto->purchaseDate);
        $this->assertNull($dto->price);
        $this->assertNull($dto->currency);
    }

    public function test_get_purchase_date_timestamp_converts_milliseconds_to_seconds(): void
    {
        $dto = TransactionInfoDto::fromArray(['purchaseDate' => 1700000000000]);

        $result = $dto->getPurchaseDateTimestamp();

        $this->assertEquals(1700000000, $result);
    }

    public function test_get_purchase_date_timestamp_returns_zero_when_null(): void
    {
        $dto = TransactionInfoDto::fromArray([]);

        $result = $dto->getPurchaseDateTimestamp();

        $this->assertEquals(0, $result);
    }

    public function test_get_original_purchase_date_timestamp_converts_milliseconds_to_seconds(): void
    {
        $dto = TransactionInfoDto::fromArray(['originalPurchaseDate' => 1690000000000]);

        $result = $dto->getOriginalPurchaseDateTimestamp();

        $this->assertEquals(1690000000, $result);
    }

    public function test_get_expires_date_timestamp_converts_milliseconds_to_seconds(): void
    {
        $dto = TransactionInfoDto::fromArray(['expiresDate' => 1710000000000]);

        $result = $dto->getExpiresDateTimestamp();

        $this->assertEquals(1710000000, $result);
    }

    public function test_get_revocation_date_timestamp_converts_milliseconds_to_seconds(): void
    {
        $dto = TransactionInfoDto::fromArray(['revocationDate' => 1711000000000]);

        $result = $dto->getRevocationDateTimestamp();

        $this->assertEquals(1711000000, $result);
    }

    public function test_get_formatted_price_divides_by_100(): void
    {
        $dto = TransactionInfoDto::fromArray(['price' => 999]);

        $result = $dto->getFormattedPrice();

        $this->assertEquals(9.99, $result);
    }

    public function test_get_formatted_price_returns_zero_when_null(): void
    {
        $dto = TransactionInfoDto::fromArray([]);

        $result = $dto->getFormattedPrice();

        $this->assertEquals(0, $result);
    }

    public function test_get_refund_reason_formats_reason_code(): void
    {
        $dto = TransactionInfoDto::fromArray(['revocationReason' => 1]);

        $result = $dto->getRefundReason();

        $this->assertEquals('code[1]', $result);
    }

    public function test_get_refund_reason_with_null_reason(): void
    {
        $dto = TransactionInfoDto::fromArray([]);

        $result = $dto->getRefundReason();

        $this->assertEquals('code[]', $result);
    }

    public function test_get_purchase_date_timestamp_with_explicit_null(): void
    {
        $dto = new TransactionInfoDto(purchaseDate: null);

        $result = $dto->getPurchaseDateTimestamp();

        $this->assertEquals(0, $result);
    }

    public function test_get_formatted_price_with_explicit_null_price(): void
    {
        $dto = new TransactionInfoDto(price: null);

        $result = $dto->getFormattedPrice();

        $this->assertEquals(0.0, $result);
    }

    public function test_get_purchase_date_timestamp_with_zero_value(): void
    {
        $dto = TransactionInfoDto::fromArray(['purchaseDate' => 0]);

        $result = $dto->getPurchaseDateTimestamp();

        $this->assertEquals(0, $result);
    }

    public function test_get_original_purchase_date_timestamp_with_null(): void
    {
        $dto = new TransactionInfoDto(originalPurchaseDate: null);

        $result = $dto->getOriginalPurchaseDateTimestamp();

        $this->assertEquals(0, $result);
    }

    public function test_get_expires_date_timestamp_with_null(): void
    {
        $dto = new TransactionInfoDto(expiresDate: null);

        $result = $dto->getExpiresDateTimestamp();

        $this->assertEquals(0, $result);
    }

    public function test_get_revocation_date_timestamp_with_null(): void
    {
        $dto = new TransactionInfoDto(revocationDate: null);

        $result = $dto->getRevocationDateTimestamp();

        $this->assertEquals(0, $result);
    }

    public function test_all_timestamp_methods_handle_null_consistently(): void
    {
        // Test all methods that use fixTs() with null values
        $dto = new TransactionInfoDto(
            purchaseDate: null,
            originalPurchaseDate: null,
            expiresDate: null,
            revocationDate: null
        );

        // All should return 0 for null values
        $this->assertEquals(0, $dto->getPurchaseDateTimestamp());
        $this->assertEquals(0, $dto->getOriginalPurchaseDateTimestamp());
        $this->assertEquals(0, $dto->getExpiresDateTimestamp());
        $this->assertEquals(0, $dto->getRevocationDateTimestamp());
    }

    public function test_all_timestamp_methods_handle_values_consistently(): void
    {
        // Test all methods with actual values
        $dto = new TransactionInfoDto(
            purchaseDate: 1700000000000,
            originalPurchaseDate: 1690000000000,
            expiresDate: 1710000000000,
            revocationDate: 1711000000000
        );

        // All should convert milliseconds to seconds
        $this->assertEquals(1700000000, $dto->getPurchaseDateTimestamp());
        $this->assertEquals(1690000000, $dto->getOriginalPurchaseDateTimestamp());
        $this->assertEquals(1710000000, $dto->getExpiresDateTimestamp());
        $this->assertEquals(1711000000, $dto->getRevocationDateTimestamp());
    }
}
