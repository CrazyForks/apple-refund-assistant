<?php

namespace App\Filament\Resources\TransactionLogs;

use App\Enums\EnvironmentEnum;
use App\Enums\NotificationTypeEnum;
use App\Filament\Resources\TransactionLogs\Pages\ManageTransactionLogs;
use App\Models\TransactionLog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class TransactionLogResource extends Resource
{
    protected static ?string $model = TransactionLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function getNavigationLabel(): string
    {
        return __('Notification Transaction');
    }

    public static function getModelLabel(): string
    {
        return __('Notification Transaction');
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('notification_uuid'),
                TextEntry::make('notification_type')
                    ->badge(),
                TextEntry::make('original_transaction_id'),
                TextEntry::make('transaction_id'),
                TextEntry::make('product_id')
                    ->placeholder('-'),
                TextEntry::make('product_type')
                    ->placeholder('-'),
                TextEntry::make('purchase_date')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('original_purchase_date')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('expiration_date')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('price')
                    ->money()
                    ->placeholder('-'),
                TextEntry::make('currency')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('environment')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('bundle_id')
                    ->placeholder('-'),
                TextEntry::make('in_app_ownership_type')
                    ->placeholder('-'),
                TextEntry::make('quantity')
                    ->numeric(),
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
                TextColumn::make('id')->sortable(),
                TextColumn::make('notification_type')
                    ->badge()
                    ->searchable(),
                TextColumn::make('environment')
                    ->badge()
                    ->searchable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('currency')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('notification_uuid')
                    ->searchable(),
                TextColumn::make('transaction_id')
                    ->searchable(),
                TextColumn::make('product_id')
                    ->searchable(),
                TextColumn::make('purchase_date')
                    ->dateTime(),
                TextColumn::make('expiration_date')
                    ->dateTime(),
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
            'index' => ManageTransactionLogs::route('/'),
        ];
    }
}
