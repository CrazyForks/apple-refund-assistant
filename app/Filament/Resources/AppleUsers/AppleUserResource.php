<?php

namespace App\Filament\Resources\AppleUsers;

use App\Filament\Resources\AppleUsers\Pages\ManageAppleUsers;
use App\Models\AppleUser;
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

class AppleUserResource extends Resource
{
    protected static ?string $model = AppleUser::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?int $navigationSort = 98;

    public static function getModelLabel(): string
    {
        return __('Apple User');
    }

    public static function getNavigationLabel(): string
    {
        return __('Apple User');
    }


    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('purchased_dollars')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('refunded_dollars')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('play_seconds')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('register_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('purchased_dollars')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('refunded_dollars')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('play_seconds')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('register_at')
                    ->dateTime()
                    ->sortable(),
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
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAppleUsers::route('/'),
        ];
    }
}
