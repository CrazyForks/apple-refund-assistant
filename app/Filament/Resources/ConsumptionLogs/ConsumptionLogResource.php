<?php

namespace App\Filament\Resources\ConsumptionLogs;

use App\Enums\ConsumptionLogStatusEnum;
use App\Enums\EnvironmentEnum;
use App\Filament\Resources\ConsumptionLogs\Pages\ManageConsumptionLogs;
use App\Models\ConsumptionLog;
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
use Filament\Tables\Table;

class ConsumptionLogResource extends Resource
{
    protected static ?string $model = ConsumptionLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?int $navigationSort = -1;

    public static function getNavigationLabel(): string
    {
        return __('Refund apply');
    }

    public static function getModelLabel(): string
    {
        return __('Refund apply');
    }


    public static function getNavigationBadge(): ?string
    {
        return ConsumptionLog::query()->where('status', ConsumptionLogStatusEnum::PENDING)->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id'),
                TextEntry::make('app_id'),
                TextEntry::make('notification_uuid')
                    ->placeholder('-'),
                TextEntry::make('bundle_id')
                    ->placeholder('-'),
                TextEntry::make('environment')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('original_transaction_id'),
                TextEntry::make('transaction_id'),
                TextEntry::make('consumption_request_reason')
                    ->placeholder('-'),
                TextEntry::make('deadline_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('status_msg')
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
                TextColumn::make('bundle_id')
                    ->searchable(),
                TextColumn::make('environment')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->searchable(),
                TextColumn::make('deadline_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('notification_uuid')
                    ->searchable(),
                TextColumn::make('transaction_id')
                    ->searchable(),
                TextColumn::make('consumption_request_reason')
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
            'index' => ManageConsumptionLogs::route('/'),
        ];
    }
}
