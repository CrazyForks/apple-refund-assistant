<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NotificationTypeEnum : string implements HasLabel, HasColor
{
    case SUBSCRIBED = 'SUBSCRIBED';
    case DID_RENEW = 'DID_RENEW';
    case OFFER_REDEEMED = 'OFFER_REDEEMED';
    case ONE_TIME_CHARGE = 'ONE_TIME_CHARGE';

    case REFUND = 'REFUND';
    case REFUND_DECLINED = 'REFUND_DECLINED';
    case TEST = 'TEST';

    case CONSUMPTION_REQUEST = 'CONSUMPTION_REQUEST';


    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DID_RENEW, self::SUBSCRIBED, self::OFFER_REDEEMED, self::ONE_TIME_CHARGE => 'success',
            self::REFUND=> 'danger',
            self::TEST=> 'warning',
            self::CONSUMPTION_REQUEST => 'info',
        };
    }
}
