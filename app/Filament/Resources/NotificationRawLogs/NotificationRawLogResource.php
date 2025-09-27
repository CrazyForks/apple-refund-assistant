<?php

namespace App\Filament\Resources\NotificationRawLogs;

use App\Filament\Resources\NotificationRawLogs\Pages\ManageNotificationRawLogs;
use App\Models\NotificationRawLog;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Phiki\Grammar\Grammar;

class NotificationRawLogResource extends Resource
{
    protected static ?string $model = NotificationRawLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?int $navigationSort = 90;

    public static function getNavigationLabel(): string
    {
        return __('Notification Raw');
    }

    public static function getModelLabel(): string
    {
        return __('Notification Raw');
    }


    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('notification_uuid'),
                TextEntry::make('app_id'),
                TextEntry::make('notification_type'),
                TextEntry::make('environment')
                    ->placeholder('-'),
                TextEntry::make('bundle_id')
                    ->placeholder('-'),
                CodeEntry::make('request_body')
                    ->grammar(Grammar::Json)
                    ->copyable()
                    ->columnSpanFull(),
                CodeEntry::make('payload')
                    ->grammar(Grammar::Json)
                    ->copyable()
                    ->columnSpanFull(),
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
            ->deferLoading()
            ->columns([
                TextColumn::make('id')
                    ->searchable(),
                TextColumn::make('environment')
                    ->searchable(),
                TextColumn::make('notification_type')
                    ->searchable(),
                TextColumn::make('bundle_id')
                    ->searchable(),
                TextColumn::make('notification_uuid')
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
            ->headerActions([
                Action::make('refresh')
                    ->label(__('refresh'))
                    ->action(function (ManageNotificationRawLogs $livewire) {
                        $livewire->resetTable();
                    }),
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
            'index' => ManageNotificationRawLogs::route('/'),
        ];
    }
}
