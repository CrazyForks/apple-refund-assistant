<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum BoolEnum: int implements HasColor, HasLabel
{
    case YES = 1;
    case NO = 0;

    public function getLabel(): ?string
    {
        return match ($this) {
            self::YES => __('Yes'),
            self::NO => __('No'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            BoolEnum::YES => 'success',
            BoolEnum::NO => 'danger',
        };
    }
}
