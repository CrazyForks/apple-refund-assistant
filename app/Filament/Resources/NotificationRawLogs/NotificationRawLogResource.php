<?php

namespace App\Filament\Resources\NotificationRawLogs;

use App\Filament\Resources\NotificationRawLogs\Pages\ManageNotificationRawLogs;
use App\Models\NotificationRawLog;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Phiki\Grammar\Grammar;

class NotificationRawLogResource extends Resource
{
    protected static ?string $model = NotificationRawLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('notification_uuid'),
                TextEntry::make('app_id')
                    ->numeric(),
                TextEntry::make('notification_type'),
                TextEntry::make('environment')
                    ->placeholder('-'),
                TextEntry::make('bundle_id')
                    ->placeholder('-'),
                TextEntry::make('subtype')
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
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->searchable(),
                TextColumn::make('environment')
                    ->searchable(),
                TextColumn::make('bundle_id')
                    ->searchable(),
                TextColumn::make('notification_uuid')
                    ->searchable(),
                TextColumn::make('notification_type')
                    ->searchable(),
                TextColumn::make('subtype')
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $query->select(['id', 'notification_uuid', 'notification_type', 'environment', 'bundle_id', 'subtype', 'created_at', 'updated_at']);
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageNotificationRawLogs::route('/'),
        ];
    }
}
