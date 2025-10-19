<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ConsumptionLogStatusEnum: string implements HasColor, HasLabel
{
    case PENDING = 'pending';
    case FAIL = 'fail';
    case SUCCESS = 'success';
    case REFUND = 'refund';
    case REFUND_DECLINED = 'refund_declined';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => __('Pending'),
            self::FAIL => __('Failed'),
            self::SUCCESS => __('Success'),
            self::REFUND => __('Refunded'),
            self::REFUND_DECLINED => __('Refund Declined'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SUCCESS => 'success',
            self::PENDING => 'warning',
            self::FAIL => 'danger',
            self::REFUND => 'info',
            self::REFUND_DECLINED => 'danger',
        };
    }
}
