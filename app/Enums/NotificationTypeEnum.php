<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NotificationTypeEnum : string implements HasLabel, HasColor
{
    // A subscription was activated.
    case DID_RENEW = 'DID_RENEW';
    // A subscription expired.
    case EXPIRED = 'EXPIRED';
    // A subscription failed to renew.
    case DID_FAIL_TO_RENEW = 'DID_FAIL_TO_RENEW';
    // A user entered or left a billing grace period.
    case GRACE_PERIOD_EXPIRED = 'GRACE_PERIOD_EXPIRED';
    // The subscription’s state was changed.
    case DID_CHANGE_RENEWAL_PREF = 'DID_CHANGE_RENEWAL_PREF';
    // The subscription’s renewal status was changed.
    case DID_CHANGE_RENEWAL_STATUS = 'DID_CHANGE_RENEWAL_STATUS';
    // The subscription was voluntarily canceled by the user.
    case DID_CANCEL = 'DID_CANCEL';
    // A subscription was refunded.
    case REFUND = 'REFUND';
    // A subscription’s price was changed.
    case PRICE_INCREASE = 'PRICE_INCREASE';
    // A subscription was purchased.
    case SUBSCRIBED = 'SUBSCRIBED';
    // A pending subscription was auto-renewed.
    case RECOVERED = 'RECOVERED';
    // A subscription was transferred to another device.
    case TRANSFERRED = 'TRANSFERRED';
    // An App Store server notification was sent in a testing environment.
    case TEST = 'TEST';
    // A subscription was charged.
    case DID_CHARGE = 'DID_CHARGE';
    // A renewal that was in a billing retry period was charged.
    case BILLING_RETRY = 'BILLING_RETRY';
    // A renewal that failed was charged successfully.
    case DID_REVOKE = 'DID_REVOKE';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getColor(): string
    {
        return match ($this) {
            self::DID_RENEW, self::SUBSCRIBED, self::RECOVERED, self::BILLING_RETRY => 'success',
            self::EXPIRED, self::DID_FAIL_TO_RENEW, self::DID_CANCEL, self::REFUND => 'danger',
            self::GRACE_PERIOD_EXPIRED, self::DID_CHANGE_RENEWAL_PREF, self::DID_CHANGE_RENEWAL_STATUS, self::PRICE_INCREASE, self::TRANSFERRED, self::TEST => 'warning',
            self::DID_CHARGE, self::DID_REVOKE => 'gray',
            default => 'gray',
        };
    }
}
