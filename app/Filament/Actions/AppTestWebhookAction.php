<?php

namespace App\Filament\Actions;

use App\Enums\PayTypeEnum;
use App\Models\App;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ViewField;
use Filament\Infolists\Components\CodeEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Wizard\Step;
use Illuminate\Support\HtmlString;

class AppTestWebhookAction
{
    public static function make()
    {
        return Action::make('testWebhook')
            ->icon('heroicon-m-pencil-square')
            ->label('Test Webhook')
            ->color('success')
            ->modalSubmitActionLabel('Test')
            ->modalHeading('Check webhook address in app store')
            ->modalDescription(function (App $app) {
                $url = sprintf('%s/apps/%d/%s/webhook', config('app.url'), $app->id, PayTypeEnum::APPLE->value);
                $html = <<<HTML
1. Navigate to Keys: From the main menu, go to "Users and Access" > "Integrations" > "App Store Connect API".<br>
2. App Store Server Notifications: <span style="color: dodgerblue; font-weight: bold;">{$url}</span>
HTML;
                return new HtmlString($html);
            })
            ->fillForm(fn (App $record): array => $record->toArray())
            ->schema([
                Grid::make(2)
                    ->schema([
                        Section::make('Info')
                            ->schema([
                                TextInput::make('bundle_id'),
                                TextInput::make('issuer_id'),
                                TextInput::make('key_id'),
                            ]),
                        Section::make('Key')
                            ->schema([
                                Textarea::make('p8_key')
                                    ->columnSpanFull(),
                            ]),
                    ]),
            ])
            ->modalCloseButton(false)
            ->modalCancelAction(false)
            ->action(function (array $data, App $app, Action $action) {

                Notification::make()
                    ->title('Saved successfully')
                    ->success()
                    ->send();
                $app->forceFill($data)->save();
                $action->cancel();
            });
    }
}
