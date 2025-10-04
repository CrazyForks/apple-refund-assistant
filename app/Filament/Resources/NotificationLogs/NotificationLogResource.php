<?php

namespace App\Filament\Resources\NotificationLogs;

use App\Filament\Resources\NotificationLogs\Pages\ManageNotificationLogs;
use App\Models\NotificationLog;
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
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\PaginationMode;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Phiki\Grammar\Grammar;

class NotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

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
                TextEntry::make('notification_type'),
                TextEntry::make('environment')
                    ->placeholder('-'),
                TextEntry::make('bundle_id')
                    ->placeholder('-'),
                CodeEntry::make('raw.request_body')
                    ->label('Request Body')
                    ->grammar(Grammar::Json)
                    ->copyable()
                    ->columnSpanFull(),
                CodeEntry::make('payload')
                    ->label('Payload')
                    ->grammar(Grammar::Json)
                    ->copyable()
                    ->columnSpanFull(),
                TextEntry::make('raw.forward_msg')
                    ->label('Forward msg')
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
            ->paginationMode(PaginationMode::Cursor)
            ->deferLoading()
            ->defaultSort('id', 'desc')
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('environment')
                    ->searchable(),
                TextColumn::make('notification_type'),
                TextColumn::make('notification_uuid')->searchable(),
                TextColumn::make('status'),
                IconColumn::make('forward_success')
                    ->color(fn (int $state): string => $state > 0 ? 'success' : 'danger')
                    ->icon(fn (int $state): ?string => $state > 0 ? 'heroicon-o-check-circle' : 'heroicon-o-x-circle'),
                TextColumn::make('created_at'),
                TextColumn::make('updated_at')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Action::make('refresh')
                    ->label(__('refresh'))
                    ->action(function (ManageNotificationLogs $livewire) {
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
            'index' => ManageNotificationLogs::route('/'),
        ];
    }
}
