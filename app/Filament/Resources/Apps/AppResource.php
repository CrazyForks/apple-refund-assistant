<?php

namespace App\Filament\Resources\Apps;

use App\Filament\Actions\AppTestWebhookAction;
use App\Filament\Resources\Apps\Pages\ManageApps;
use App\Models\App;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AppResource extends Resource
{
    protected static ?string $model = App::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 99;


    public static function getModelLabel(): string
    {
        return __('App');
    }
    public static function getNavigationLabel(): string
    {
        return __('App');
    }

    public static function form(Schema $schema): Schema
    {
        $currentUserId = Auth::id();
        $isAdmin = Auth::user()?->isAdmin() || false;
        $ownerField =  Select::make('owner_id')
            ->label(__('Owner id'))
            ->relationship('owner', 'name')
            ->options(User::all()->pluck('name', 'id'))
            ->visible($isAdmin)
            ->required()
            ->default($currentUserId);
        if (!$isAdmin) {
            $ownerField = Hidden::make('owner_id')
                ->label(__('Owner id'))
                ->default(fn () => $currentUserId);
        }

        return $schema
            ->components([
                TextInput::make('name')
                    ->columnSpanFull()
                    ->required(),
                Radio::make('sample_content_provided')
                    ->options([
                        1 => __('Yes'),
                        0 => __('No'),
                    ])
                    ->default(false),
                Textarea::make('description')->columnSpanFull(),
                $ownerField,
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        // 获取基准查询构建器
        $query = parent::getEloquentQuery();

        $user = Auth::user();
        if (!$user || $user->isAdmin()) {
            return $query;
        }

        return $query->where('owner_id', $user->id);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id'),
                TextColumn::make('name')
                    ->searchable(),
                IconColumn::make('sample_content_provided')
                    ->boolean(),
                TextColumn::make('description')
                    ->searchable(),
                TextColumn::make('bundle_id')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                // 2. 所有者列 (仅管理员可见)
                TextColumn::make('owner.name') // 显示所有者（User）的名称
                ->label(__('Owner'))
                    ->visible(Auth::user()->isAdmin()),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
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
                AppTestWebhookAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageApps::route('/'),
        ];
    }
}
