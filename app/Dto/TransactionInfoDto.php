<?php

namespace App\Dto;

class TransactionInfoDto
{
    public function __construct(
        public readonly ?string $originalTransactionId = null,
        public readonly ?string $transactionId = null,
        public readonly ?int $purchaseDate = null,
        public readonly ?int $price = null,
        public readonly ?string $currency = null,
        public readonly ?string $appAccountToken = null,
        public readonly ?string $productId = null,
        public readonly ?string $type = null,
        public readonly ?int $originalPurchaseDate = null,
        public readonly ?int $expiresDate = null,
        public readonly ?string $inAppOwnershipType = null,
        public readonly ?int $quantity = null,
        public readonly ?int $revocationDate = null,
        public readonly ?int $revocationReason = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            originalTransactionId: $data['originalTransactionId'] ?? null,
            transactionId: $data['transactionId'] ?? null,
            purchaseDate: $data['purchaseDate'] ?? null,
            price: $data['price'] ?? null,
            currency: $data['currency'] ?? null,
            appAccountToken: $data['appAccountToken'] ?? null,
            productId: $data['productId'] ?? null,
            type: $data['type'] ?? null,
            originalPurchaseDate: $data['originalPurchaseDate'] ?? null,
            expiresDate: $data['expiresDate'] ?? null,
            inAppOwnershipType: $data['inAppOwnershipType'] ?? null,
            quantity: $data['quantity'] ?? null,
            revocationDate: $data['revocationDate'] ?? null,
            revocationReason: $data['revocationReason'] ?? null,
        );
    }

    /**
     * Get purchase date as Unix timestamp (seconds)
     */
    public function getPurchaseDateTimestamp(): int
    {
        return (int)(($this->purchaseDate ?? 0) / 1000);
    }

    /**
     * Get original purchase date as Unix timestamp (seconds)
     */
    public function getOriginalPurchaseDateTimestamp(): int
    {
        return (int)(($this->originalPurchaseDate ?? 0) / 1000);
    }

    /**
     * Get expiration date as Unix timestamp (seconds)
     */
    public function getExpiresDateTimestamp(): int
    {
        return (int)(($this->expiresDate ?? 0) / 1000);
    }

    /**
     * Get revocation (refund) date as Unix timestamp (seconds)
     */
    public function getRevocationDateTimestamp(): int
    {
        return (int)(($this->revocationDate ?? 0) / 1000);
    }

    /**
     * Get formatted price (divide by 100)
     */
    public function getFormattedPrice(): float
    {
        return ($this->price ?? 0) / 100;
    }

    /**
     * Get refund reason as formatted string
     */
    public function getRefundReason(): string
    {
        return "code[{$this->revocationReason}]";
    }
}

