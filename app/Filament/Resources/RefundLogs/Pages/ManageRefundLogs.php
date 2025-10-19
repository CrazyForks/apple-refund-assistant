<?php

namespace App\Filament\Resources\RefundLogs\Pages;

use App\Filament\Resources\RefundLogs\RefundLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageRefundLogs extends ManageRecords
{
    protected static string $resource = RefundLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
