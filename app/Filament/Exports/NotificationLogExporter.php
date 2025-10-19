<?php

namespace App\Filament\Exports;

use App\Models\NotificationLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class NotificationLogExporter extends Exporter
{
    protected static ?string $model = NotificationLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('app.name'),
            ExportColumn::make('notification_uuid'),
            ExportColumn::make('notification_type'),
            ExportColumn::make('bundle_id'),
            ExportColumn::make('bundle_version'),
            ExportColumn::make('environment'),
            ExportColumn::make('payload'),
            ExportColumn::make('status'),
            ExportColumn::make('forward_success'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your notification log export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
