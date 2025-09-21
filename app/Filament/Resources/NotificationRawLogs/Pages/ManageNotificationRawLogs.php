<?php

namespace App\Filament\Resources\NotificationRawLogs\Pages;

use App\Filament\Resources\NotificationRawLogs\NotificationRawLogResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Livewire;

class ManageNotificationRawLogs extends ManageRecords
{
    protected static string $resource = NotificationRawLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];

    }
}
