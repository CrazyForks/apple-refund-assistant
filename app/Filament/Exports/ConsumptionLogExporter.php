<?php

namespace App\Filament\Exports;

use App\Models\ConsumptionLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class ConsumptionLogExporter extends Exporter
{
    protected static ?string $model = ConsumptionLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('app.name'),
            ExportColumn::make('notification_uuid'),
            ExportColumn::make('bundle_id'),
            ExportColumn::make('bundle_version'),
            ExportColumn::make('environment'),
            ExportColumn::make('original_transaction_id'),
            ExportColumn::make('transaction_id'),
            ExportColumn::make('consumption_request_reason'),
            ExportColumn::make('deadline_at'),
            ExportColumn::make('status'),
            ExportColumn::make('status_msg'),
            ExportColumn::make('send_body'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your consumption log export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
