<?php

namespace App\Filament\Resources\NotificationRawLogs\Pages;

use App\Filament\Resources\NotificationRawLogs\NotificationRawLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageNotificationRawLogs extends ManageRecords
{
    protected static string $resource = NotificationRawLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
