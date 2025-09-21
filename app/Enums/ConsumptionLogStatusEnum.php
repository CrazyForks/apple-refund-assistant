<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ConsumptionLogStatusEnum : string implements HasLabel, HasColor
{
    case PENDING = 'pending';
    case FAIL = 'fail';
    case SUCCESS = 'success';


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
        };
    }
}
