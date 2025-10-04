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
        $transInfoData = $data['signedTransactionInfo']['decoded'] ?? null;

        return new self(
            notificationType: NotificationTypeEnum::from($payload['notificationType'] ?? ''),
            notificationUuid: $payload['notificationUUID'] ?? '',
            bundleId: $data['bundleId'] ?? '',
            environment: EnvironmentEnum::from($data['environment'] ?? ''),
            transactionInfo: $transInfoData ? TransactionInfoDto::fromArray($transInfoData) : null,
            consumptionRequestReason: $data['consumptionRequestReason'] ?? null,
        );
    }
}

