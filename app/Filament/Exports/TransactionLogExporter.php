<?php

namespace App\Filament\Exports;

use App\Models\TransactionLog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class TransactionLogExporter extends Exporter
{
    protected static ?string $model = TransactionLog::class;

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
            ExportColumn::make('original_transaction_id'),
            ExportColumn::make('transaction_id'),
            ExportColumn::make('product_id'),
            ExportColumn::make('product_type'),
            ExportColumn::make('purchase_date'),
            ExportColumn::make('original_purchase_date'),
            ExportColumn::make('expiration_date'),
            ExportColumn::make('price'),
            ExportColumn::make('currency'),
            ExportColumn::make('in_app_ownership_type'),
            ExportColumn::make('quantity'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your transaction log export has completed and '.Number::format($export->successful_rows).' '.str('row')->plural($export->successful_rows).' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' '.Number::format($failedRowsCount).' '.str('row')->plural($failedRowsCount).' failed to export.';
        }

        return $body;
    }
}
