<?php

namespace App\Filament\Exports;

use App\Models\RefundLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class RefundLogExporter extends Exporter
{
    protected static ?string $model = RefundLog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('app.name'),
            ExportColumn::make('transaction_id'),
            ExportColumn::make('original_transaction_id'),
            ExportColumn::make('notification_uuid'),
            ExportColumn::make('bundle_id'),
            ExportColumn::make('bundle_version'),
            ExportColumn::make('environment'),
            ExportColumn::make('purchase_date'),
            ExportColumn::make('price'),
            ExportColumn::make('currency'),
            ExportColumn::make('refund_date'),
            ExportColumn::make('refund_reason'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your refund log export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
