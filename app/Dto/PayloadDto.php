<?php

namespace App\Dto;

use App\Enums\EnvironmentEnum;
use App\Enums\NotificationTypeEnum;

class PayloadDto
{
    public function __construct(
        public readonly NotificationTypeEnum $notificationType,
        public readonly string $notificationUuid,
        public readonly string $bundleId,
        public readonly EnvironmentEnum $environment,
        public readonly ?TransactionInfoDto $transactionInfo = null,
        public readonly ?string $consumptionRequestReason = null,
    ) {
    }

    public static function fromRawPayload(array $payload): self
    {
        $data = $payload['data'] ?? [];
        
        // Handle two formats: Apple notification format and PHP object-encoded format
        // Apple format: data.signedTransactionInfo.decoded
        // PHP object format: appMetadata.transactionInfo (already decoded)
        $transInfoData = $data['signedTransactionInfo']['decoded'] 
            ?? $payload['appMetadata']['transactionInfo'] 
            ?? null;

        // Handle environment: try multiple locations depending on format
        $environment = $data['environment'] 
            ?? $payload['environment'] 
            ?? $payload['appMetadata']['environment'] 
            ?? '';
        
        // Normalize environment values
        if (empty($environment)) {
            $environment = EnvironmentEnum::SANDBOX->value;
        } elseif (str_contains($environment, 'Sandbox')) {
            // Map LocalSandbox, Sandbox, etc. to Sandbox
            $environment = EnvironmentEnum::SANDBOX->value;
        } elseif (str_contains($environment, 'Production')) {
            $environment = EnvironmentEnum::PRODUCTION->value;
        }

        // Handle bundleId from different locations
        $bundleId = $data['bundleId'] 
            ?? $payload['appMetadata']['bundleId'] 
            ?? '';

        // Handle notification type - use from to validate it's a known type
        // Unknown types will be caught earlier by SafeEnumCast in the model
        $notificationType = $payload['notificationType'] ?? '';
        $notificationTypeEnum = NotificationTypeEnum::tryFrom($notificationType);
        
        // If notification type is unknown, use TEST as placeholder for DTO creation
        // The actual value is preserved in the database via SafeEnumCast
        if ($notificationTypeEnum === null) {
            $notificationTypeEnum = NotificationTypeEnum::TEST;
        }

        return new self(
            notificationType: $notificationTypeEnum,
            notificationUuid: $payload['notificationUUID'] ?? '',
            bundleId: $bundleId,
            environment: EnvironmentEnum::from($environment),
            transactionInfo: $transInfoData ? TransactionInfoDto::fromArray($transInfoData) : null,
            consumptionRequestReason: $data['consumptionRequestReason'] 
                ?? $payload['appMetadata']['consumptionRequestReason'] 
                ?? null,
        );
    }
}

