<?php

namespace App\Filament\Resources\ConsumptionLogs\Pages;

use App\Enums\ConsumptionLogStatusEnum;
use App\Filament\Resources\ConsumptionLogs\ConsumptionLogResource;
use App\Jobs\SendConsumptionInformationJob;
use App\Models\ConsumptionLog;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;

class ManageConsumptionLogs extends ManageRecords
{
    protected static string $resource = ConsumptionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('batchSend')
                ->label(__('Batch send latest 20'))
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(__('Batch send consumption information'))
                ->modalDescription(__('Will automatically send the latest 20 pending consumption logs to Apple server, are you sure to continue?'))
                ->modalSubmitActionLabel(__('Confirm send'))
                ->action(function () {
                    $this->batchSendConsumptionLogs();
                }),
        ];
    }

    protected function batchSendConsumptionLogs(): void
    {
        // Get the latest 20 pending consumption logs
        $logs = ConsumptionLog::where('status', '!=', ConsumptionLogStatusEnum::SUCCESS)
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        if ($logs->isEmpty()) {
            Notification::make()
                ->title(__('No pending logs'))
                ->body(__('There are currently no pending consumption logs to send.'))
                ->warning()
                ->send();
            return;
        }

        $count = 0;
        foreach ($logs as $log) {
            // Dispatch task to queue
            dispatch(new SendConsumptionInformationJob($log))->afterResponse();
            $count++;
        }

        Notification::make()
            ->title(__('Batch send task started'))
            ->body(__('Successfully added :count consumption logs to the send queue.', ['count' => $count]))
            ->success()
            ->send();
    }
}
