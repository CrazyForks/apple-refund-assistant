<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum NotificationLogStatusEnum : int implements HasLabel, HasColor
{
    case PROCESSING = 1;
    case PROCESSED = 2;
    case UN_MATCH_BUNDLE = 3;


    public function getLabel(): ?string
    {
        return match ($this) {
            self::PROCESSING => __('processing'),
            self::PROCESSED => __('processed'),
            self::UN_MATCH_BUNDLE => __('un match bundle'),
        };
    }

    public function getColor():  string
    {
        return match ($this) {
            NotificationLogStatusEnum::PROCESSING => 'warning',
            NotificationLogStatusEnum::PROCESSED => 'success',
            NotificationLogStatusEnum::UN_MATCH_BUNDLE => 'danger',
            default => 'gray',
        };
    }
}
