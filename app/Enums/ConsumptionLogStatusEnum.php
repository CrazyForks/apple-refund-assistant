<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ConsumptionLogStatusEnum : string implements HasLabel, HasColor
{
    case PENDING = 'pending';
    case FAIL = 'fail';
    case SUCCESS = 'success';
    case REFUND = 'refund';
    case REFUND_DECLINED = 'refund_declined';


    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SUCCESS=> 'success',
            self::PENDING=> 'warning',
            self::FAIL => 'danger',
            self::REFUND => 'info',
            self::REFUND_DECLINED => 'danger',
        };
    }
}
