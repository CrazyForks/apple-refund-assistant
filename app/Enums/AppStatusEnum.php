<?php

declare(strict_types=1);

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AppStatusEnum: string implements HasColor, HasLabel
{
    case UN_VERIFIED = 'unverified';
    case WEB_HOOKING = 'web_hooking';
    case NORMAL = 'normal';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::UN_VERIFIED => __('status_unverified'),
            self::WEB_HOOKING => __('status_web_hooking'),
            self::NORMAL => __('status_normal'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            AppStatusEnum::UN_VERIFIED => 'danger',
            AppStatusEnum::WEB_HOOKING => 'warning',
            AppStatusEnum::NORMAL => 'success',
            default => 'gray',
        };
    }
}
