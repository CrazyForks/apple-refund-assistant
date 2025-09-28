<?php

namespace App\Filament\Resources\AppleUsers\Pages;

use App\Filament\Resources\AppleUsers\AppleUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageAppleUsers extends ManageRecords
{
    protected static string $resource = AppleUserResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
