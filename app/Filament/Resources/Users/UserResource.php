<?php

namespace App\Filament\Resources\Users;

use App\Enums\BoolEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static ?int $navigationSort = 100;
    protected static bool $isScopedToTenant = false;

    public static function getNavigationLabel(): string
    {
        return __('Admin');
    }

    public static function getModelLabel(): string
    {
        return __('Admin');
    }


    public static function form(Schema $schema): Schema
    {
        $password = TextInput::make('password')
            ->password()
            ->dehydrated(fn ($state) => filled($state)) // Key point 1
            ->required(fn (string $operation): bool => $operation === 'create')
            ->dehydrateStateUsing(fn (string $state): string => bcrypt($state))
            ->minLength(4);
        if (app()->environment('demo')) {
            $password->disabled();
        }

        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                Radio::make('role')
                    ->options(UserRoleEnum::class)
                    ->default(UserRoleEnum::ADMIN)
                    ->required(),
                Radio::make('activate')
                    ->options(BoolEnum::class)
                    ->default(BoolEnum::YES)
                    ->required(),
                $password,
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('role')
                    ->label('Role'),
                IconColumn::make('activate')
                    ->boolean(),
                TextColumn::make('created_at'),
                TextColumn::make('updated_at')
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
            'index' => ManageUsers::route('/'),
        ];
    }
}
