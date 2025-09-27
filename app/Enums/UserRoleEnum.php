<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum UserRoleEnum : string implements HasLabel, HasColor
{
    case ADMIN = 'admin';
    case EDITOR = 'editor';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::ADMIN => __('admin'),
            self::EDITOR => __('editor'),
        };
    }

    public function getColor():  string
    {
        return match ($this) {
            UserRoleEnum::ADMIN => 'success',
            UserRoleEnum::EDITOR => 'warning',
        };
    }
}
