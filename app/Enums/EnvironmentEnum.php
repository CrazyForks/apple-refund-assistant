<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EnvironmentEnum : string implements HasLabel, HasColor
{
    case PRODUCTION = 'Production';
    case SANDBOX = 'Sandbox';

    public function getLabel(): ?string
    {
        return $this->value;
    }

    public function getColor():  string
    {
        return match ($this) {
            EnvironmentEnum::PRODUCTION => 'success',
            EnvironmentEnum::SANDBOX => 'warning',
            default => 'gray',
        };
    }
}
