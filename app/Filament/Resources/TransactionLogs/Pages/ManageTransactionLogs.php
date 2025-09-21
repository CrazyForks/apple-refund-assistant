<?php

namespace App\Filament\Resources\TransactionLogs\Pages;

use App\Filament\Resources\TransactionLogs\TransactionLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTransactionLogs extends ManageRecords
{
    protected static string $resource = TransactionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
