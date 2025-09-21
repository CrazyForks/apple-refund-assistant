<?php

namespace App\Filament\Resources\ConsumptionLogs\Pages;

use App\Filament\Resources\ConsumptionLogs\ConsumptionLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageConsumptionLogs extends ManageRecords
{
    protected static string $resource = ConsumptionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
