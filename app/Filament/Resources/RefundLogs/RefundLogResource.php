<?php

namespace App\Filament\Resources\RefundLogs;

use App\Filament\Resources\RefundLogs\Pages\ManageRefundLogs;
use App\Models\RefundLog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RefundLogResource extends Resource
{
    protected static ?string $model = RefundLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationLabel(): string
    {
        return __('Notification Refund');
    }

    public static function getModelLabel(): string
    {
        return __('Notification Refund');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('bundle_id')
                    ->placeholder('-'),
                TextEntry::make('notification_uuid')
                    ->placeholder('-'),
                TextEntry::make('purchase_date')
                    ->placeholder('-'),
                TextEntry::make('transaction_id'),
                TextEntry::make('original_transaction_id'),
                TextEntry::make('price')
                    ->numeric(),
                TextEntry::make('currency')
                    ->placeholder('-'),
                TextEntry::make('refund_date')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('refund_reason')
                    ->placeholder('-'),
                TextEntry::make('environment')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('environment')
                    ->searchable(),
                TextColumn::make('transaction_id')
                    ->searchable(),
                TextColumn::make('price')
                    ->numeric(),
                TextColumn::make('currency'),
                TextColumn::make('refund_date')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('refund_reason')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageRefundLogs::route('/'),
        ];
    }
}
